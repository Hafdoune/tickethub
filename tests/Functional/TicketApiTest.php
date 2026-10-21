<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\CreatesEntities;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TicketApiTest extends WebTestCase
{
    use CreatesEntities;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRequestsWithoutTokenAreRejected(): void
    {
        $this->client->request('GET', '/api/tickets');

        self::assertResponseStatusCodeSame(401);
    }

    public function testInvalidTokenIsRejected(): void
    {
        $this->api('GET', '/api/tickets', 'not-a-real-token');

        self::assertResponseStatusCodeSame(401);
    }

    public function testListIsPaginatedAndScopedToTheCustomer(): void
    {
        $category = $this->createCategory();
        $me = $this->createUser('karim@example.test', apiToken: 'token-karim');
        foreach (range(1, 3) as $i) {
            $this->createTicket($me, $category, "Ticket numéro {$i}");
        }
        $this->createTicket($this->createUser('lina@example.test'), $category, 'Ticket de Lina');

        $body = $this->api('GET', '/api/tickets?limit=2', 'token-karim');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $body['data']);
        self::assertSame(['page' => 1, 'limit' => 2, 'total' => 3, 'pages' => 2], $body['meta']);
        self::assertNotContains('Ticket de Lina', array_column($body['data'], 'title'));
    }

    public function testCreateTicket(): void
    {
        $category = $this->createCategory();
        $this->createUser('karim@example.test', apiToken: 'token-karim');

        $body = $this->api('POST', '/api/tickets', 'token-karim', [
            'title' => 'Plus de réseau mobile',
            'description' => 'Aucun réseau depuis ce matin dans tout le quartier.',
            'categoryId' => $category->getId(),
            'priority' => 'urgent',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('Location', '/api/tickets/'.$body['id']);
        self::assertSame('open', $body['status']);
        self::assertSame('urgent', $body['priority']);
        self::assertSame('Karim', $body['author']['fullName']);
        self::assertArrayNotHasKey('email', $body['author']);
    }

    public function testCreateTicketValidatesThePayload(): void
    {
        $this->createUser('karim@example.test', apiToken: 'token-karim');

        $body = $this->api('POST', '/api/tickets', 'token-karim', ['title' => 'abc']);

        self::assertResponseStatusCodeSame(422);
        self::assertEqualsCanonicalizing(
            ['title', 'description', 'categoryId'],
            array_values(array_unique(array_column($body['violations'], 'propertyPath'))),
        );
    }

    public function testCustomerCannotReadAnotherCustomersTicket(): void
    {
        $ticket = $this->createTicket($this->createUser('lina@example.test'), $this->createCategory());
        $this->createUser('karim@example.test', apiToken: 'token-karim');

        $this->api('GET', '/api/tickets/'.$ticket->getId(), 'token-karim');

        self::assertResponseStatusCodeSame(403);
    }

    public function testCustomerCannotChangeTheStatus(): void
    {
        $me = $this->createUser('karim@example.test', apiToken: 'token-karim');
        $ticket = $this->createTicket($me, $this->createCategory());

        $this->api('PATCH', '/api/tickets/'.$ticket->getId(), 'token-karim', ['status' => 'resolved']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAgentCanAssignAndResolve(): void
    {
        $ticket = $this->createTicket($this->createUser('karim@example.test'), $this->createCategory());
        $agent = $this->createUser('sara@example.test', [User::ROLE_AGENT], 'token-sara');

        $body = $this->api('PATCH', '/api/tickets/'.$ticket->getId(), 'token-sara', [
            'assigneeId' => $agent->getId(),
            'status' => 'resolved',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('resolved', $body['status']);
        self::assertSame($agent->getId(), $body['assignee']['id']);
    }

    public function testCommentOnAClosedTicketIsForbidden(): void
    {
        $me = $this->createUser('karim@example.test', apiToken: 'token-karim');
        $ticket = $this->createTicket($me, $this->createCategory());
        $this->api('POST', '/api/tickets/'.$ticket->getId().'/close', 'token-karim');
        self::assertResponseIsSuccessful();

        $this->api('POST', '/api/tickets/'.$ticket->getId().'/comments', 'token-karim', ['content' => 'Encore là ?']);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @param array<string, mixed>|null $json
     *
     * @return array<string, mixed>
     */
    private function api(string $method, string $uri, string $token, ?array $json = null): array
    {
        $this->client->request($method, $uri, server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], content: $json !== null ? json_encode($json, \JSON_THROW_ON_ERROR) : null);

        $content = (string) $this->client->getResponse()->getContent();

        return $content !== '' ? json_decode($content, true, flags: \JSON_THROW_ON_ERROR) : [];
    }
}
