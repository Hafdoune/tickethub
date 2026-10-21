<?php

namespace App\Tests\Unit\Security;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Security\Voter\TicketVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TicketVoterTest extends TestCase
{
    private User $author;
    private User $stranger;
    private User $agent;

    protected function setUp(): void
    {
        $this->author = $this->user(1);
        $this->stranger = $this->user(2);
        $this->agent = $this->user(3, [User::ROLE_AGENT]);
    }

    /**
     * @return iterable<string, array{string, TicketStatus, int}>
     */
    public static function authorCases(): iterable
    {
        yield 'view open' => [TicketVoter::VIEW, TicketStatus::Open, VoterInterface::ACCESS_GRANTED];
        yield 'view closed' => [TicketVoter::VIEW, TicketStatus::Closed, VoterInterface::ACCESS_GRANTED];
        yield 'edit open' => [TicketVoter::EDIT, TicketStatus::Open, VoterInterface::ACCESS_GRANTED];
        yield 'edit once handled' => [TicketVoter::EDIT, TicketStatus::InProgress, VoterInterface::ACCESS_DENIED];
        yield 'comment resolved' => [TicketVoter::COMMENT, TicketStatus::Resolved, VoterInterface::ACCESS_GRANTED];
        yield 'comment closed' => [TicketVoter::COMMENT, TicketStatus::Closed, VoterInterface::ACCESS_DENIED];
        yield 'close in progress' => [TicketVoter::CLOSE, TicketStatus::InProgress, VoterInterface::ACCESS_GRANTED];
        yield 'manage' => [TicketVoter::MANAGE, TicketStatus::Open, VoterInterface::ACCESS_DENIED];
    }

    #[DataProvider('authorCases')]
    public function testAuthorPermissions(string $attribute, TicketStatus $status, int $expected): void
    {
        $vote = $this->voter(isAgent: false)->vote($this->token($this->author), $this->ticket($status), [$attribute]);

        self::assertSame($expected, $vote);
    }

    public function testStrangerCannotSeeTheTicket(): void
    {
        $vote = $this->voter(isAgent: false)->vote($this->token($this->stranger), $this->ticket(TicketStatus::Open), [TicketVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    /**
     * @return iterable<string, array{string, TicketStatus, int}>
     */
    public static function agentCases(): iterable
    {
        yield 'view' => [TicketVoter::VIEW, TicketStatus::Open, VoterInterface::ACCESS_GRANTED];
        yield 'manage closed' => [TicketVoter::MANAGE, TicketStatus::Closed, VoterInterface::ACCESS_GRANTED];
        yield 'edit in progress' => [TicketVoter::EDIT, TicketStatus::InProgress, VoterInterface::ACCESS_GRANTED];
        yield 'edit closed' => [TicketVoter::EDIT, TicketStatus::Closed, VoterInterface::ACCESS_DENIED];
        yield 'comment closed' => [TicketVoter::COMMENT, TicketStatus::Closed, VoterInterface::ACCESS_DENIED];
    }

    #[DataProvider('agentCases')]
    public function testAgentPermissions(string $attribute, TicketStatus $status, int $expected): void
    {
        $vote = $this->voter(isAgent: true)->vote($this->token($this->agent), $this->ticket($status), [$attribute]);

        self::assertSame($expected, $vote);
    }

    public function testAnonymousIsDenied(): void
    {
        $vote = $this->voter(isAgent: false)->vote(new NullToken(), $this->ticket(TicketStatus::Open), [TicketVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testUnknownAttributeIsIgnored(): void
    {
        $vote = $this->voter(isAgent: false)->vote($this->token($this->author), $this->ticket(TicketStatus::Open), ['SOMETHING_ELSE']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $vote);
    }

    private function voter(bool $isAgent): TicketVoter
    {
        $decisionManager = $this->createStub(AccessDecisionManagerInterface::class);
        $decisionManager->method('decide')->willReturn($isAgent);

        return new TicketVoter($decisionManager);
    }

    private function ticket(TicketStatus $status): Ticket
    {
        return (new Ticket())->setAuthor($this->author)->setStatus($status);
    }

    private function token(User $user): TokenInterface
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    /**
     * @param list<string> $roles
     */
    private function user(int $id, array $roles = []): User
    {
        $user = (new User())->setEmail("user{$id}@example.test")->setFullName("User {$id}")->setRoles($roles);
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }
}
