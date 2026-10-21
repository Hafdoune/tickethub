<?php

namespace App\Security\Voter;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Ticket>
 */
final class TicketVoter extends Voter
{
    public const VIEW = 'TICKET_VIEW';
    public const EDIT = 'TICKET_EDIT';
    public const COMMENT = 'TICKET_COMMENT';
    public const CLOSE = 'TICKET_CLOSE';
    public const MANAGE = 'TICKET_MANAGE';

    private const ATTRIBUTES = [self::VIEW, self::EDIT, self::COMMENT, self::CLOSE, self::MANAGE];

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Ticket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, [User::ROLE_AGENT])) {
            return $this->agentCan($attribute, $subject);
        }

        if (!$subject->isAuthoredBy($user)) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => $subject->getStatus() === TicketStatus::Open,
            self::COMMENT, self::CLOSE => !$subject->isClosed(),
            default => false,
        };
    }

    private function agentCan(string $attribute, Ticket $ticket): bool
    {
        return match ($attribute) {
            self::VIEW, self::MANAGE => true,
            self::EDIT, self::COMMENT, self::CLOSE => !$ticket->isClosed(),
            default => false,
        };
    }
}
