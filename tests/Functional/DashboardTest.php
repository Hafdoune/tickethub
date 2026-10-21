<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\CreatesEntities;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardTest extends WebTestCase
{
    use CreatesEntities;

    public function testCustomersCannotOpenTheDashboard(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('karim@example.test'));

        $client->request('GET', '/dashboard');

        self::assertResponseStatusCodeSame(403);
    }

    public function testDataEndpointReturnsCountsAndActiveTickets(): void
    {
        $client = static::createClient();
        $customer = $this->createUser('karim@example.test');
        $category = $this->createCategory();
        $this->createTicket($customer, $category, 'Ticket actif');
        $this->createTicket($customer, $category, 'Ticket fermé')->close();
        $this->em()->flush();

        $client->loginUser($this->createUser('sara@example.test', [User::ROLE_AGENT]));
        $client->request('GET', '/dashboard/data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(1, $data['counts']['open']);
        self::assertSame(1, $data['counts']['closed']);
        self::assertSame(['Ticket actif'], array_column($data['tickets'], 'title'));
        self::assertSame([['name' => 'Internet', 'total' => 1]], $data['byCategory']);
    }

    public function testAgentCanAssignATicketToHimself(): void
    {
        $client = static::createClient();
        $ticket = $this->createTicket($this->createUser('karim@example.test'), $this->createCategory());
        $agent = $this->createUser('sara@example.test', [User::ROLE_AGENT]);
        $client->loginUser($agent);

        $crawler = $client->request('GET', '/dashboard');
        $token = $crawler->filter('#dashboard')->attr('data-csrf');

        $client->request('POST', "/dashboard/tickets/{$ticket->getId()}/assign", server: ['HTTP_X_CSRF_TOKEN' => $token]);

        self::assertResponseIsSuccessful();
        $row = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame($agent->getId(), $row['assignee']['id']);
        self::assertSame('in_progress', $row['status']);
    }

    public function testAssignRequiresAValidCsrfToken(): void
    {
        $client = static::createClient();
        $ticket = $this->createTicket($this->createUser('karim@example.test'), $this->createCategory());
        $client->loginUser($this->createUser('sara@example.test', [User::ROLE_AGENT]));

        $client->request('POST', "/dashboard/tickets/{$ticket->getId()}/assign", server: ['HTTP_X_CSRF_TOKEN' => 'nope']);

        self::assertResponseStatusCodeSame(403);
    }
}
