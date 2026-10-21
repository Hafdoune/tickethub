<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use App\Tests\Support\CreatesEntities;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TicketWebTest extends WebTestCase
{
    use CreatesEntities;

    public function testAnonymousUserIsSentToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tickets');

        self::assertResponseRedirects('/login');
    }

    public function testCustomerOnlySeesOwnTickets(): void
    {
        $client = static::createClient();
        $category = $this->createCategory();
        $me = $this->createUser('karim@example.test');
        $other = $this->createUser('lina@example.test');
        $this->createTicket($me, $category, 'Mon ticket à moi');
        $this->createTicket($other, $category, 'Le ticket de Lina');

        $client->loginUser($me);
        $client->request('GET', '/tickets');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mes tickets');
        self::assertAnySelectorTextContains('tbody', 'Mon ticket à moi');
        self::assertSelectorTextNotContains('tbody', 'Le ticket de Lina');
    }

    public function testCustomerCannotOpenSomeoneElsesTicket(): void
    {
        $client = static::createClient();
        $ticket = $this->createTicket($this->createUser('lina@example.test'), $this->createCategory());

        $client->loginUser($this->createUser('karim@example.test'));
        $client->request('GET', '/tickets/'.$ticket->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testCustomerCanCreateATicket(): void
    {
        $client = static::createClient();
        $category = $this->createCategory('Mobile');
        $client->loginUser($this->createUser('karim@example.test'));

        $client->request('GET', '/tickets/new');
        $client->submitForm('Envoyer le ticket', [
            'ticket[title]' => 'Carte SIM non activée',
            'ticket[category]' => (string) $category->getId(),
            'ticket[priority]' => 'high',
            'ticket[description]' => 'Ma nouvelle carte SIM ne capte aucun réseau.',
        ]);

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Carte SIM non activée');
        self::assertSelectorTextContains('[data-flash="success"]', 'créé');
    }

    public function testInvalidTicketShowsErrors(): void
    {
        $client = static::createClient();
        $this->createCategory();
        $client->loginUser($this->createUser('karim@example.test'));

        $client->request('GET', '/tickets/new');
        $client->submitForm('Envoyer le ticket', ['ticket[title]' => 'abc', 'ticket[description]' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('[data-slot="field-error"]');
    }

    public function testOnlyAgentsSeeTheTreatmentPanel(): void
    {
        $client = static::createClient();
        $customer = $this->createUser('karim@example.test');
        $ticket = $this->createTicket($customer, $this->createCategory());

        $client->loginUser($customer);
        $client->request('GET', '/tickets/'.$ticket->getId());
        self::assertSelectorNotExists('form[name="ticket_manage"]');

        $client->loginUser($this->createUser('sara@example.test', [User::ROLE_AGENT]));
        $client->request('GET', '/tickets/'.$ticket->getId());
        self::assertSelectorExists('form[name="ticket_manage"]');
    }

    public function testAgentCanResolveATicket(): void
    {
        $client = static::createClient();
        $ticket = $this->createTicket($this->createUser('karim@example.test'), $this->createCategory());
        $client->loginUser($this->createUser('sara@example.test', [User::ROLE_AGENT]));

        $client->request('GET', '/tickets/'.$ticket->getId());
        $client->submitForm('Mettre à jour', ['ticket_manage[status]' => 'resolved']);

        self::assertResponseRedirects('/tickets/'.$ticket->getId());
        $updated = static::getContainer()->get(TicketRepository::class)->find($ticket->getId());
        self::assertSame(TicketStatus::Resolved, $updated->getStatus());
    }

    public function testCustomerReplyReopensAResolvedTicket(): void
    {
        $client = static::createClient();
        $customer = $this->createUser('karim@example.test');
        $ticket = $this->createTicket($customer, $this->createCategory(), status: TicketStatus::Resolved);
        $client->loginUser($customer);

        $client->request('GET', '/tickets/'.$ticket->getId());
        $client->submitForm('Répondre', ['comment[content]' => 'Le problème est revenu ce soir.']);

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertAnySelectorTextContains('[data-comment]', 'Le problème est revenu ce soir.');
        self::assertSelectorTextContains('[data-ticket-header] [data-status]', 'En cours');
    }
}
