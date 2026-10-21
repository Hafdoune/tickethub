<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    public function testNewTicketIsOpen(): void
    {
        $ticket = new Ticket();

        self::assertSame(TicketStatus::Open, $ticket->getStatus());
        self::assertNull($ticket->getClosedAt());
        self::assertFalse($ticket->isClosed());
    }

    public function testAssigningAnAgentStartsTheWork(): void
    {
        $ticket = (new Ticket())->assignTo(new User());

        self::assertSame(TicketStatus::InProgress, $ticket->getStatus());
    }

    public function testAssigningDoesNotChangeAResolvedTicket(): void
    {
        $ticket = (new Ticket())->setStatus(TicketStatus::Resolved)->assignTo(new User());

        self::assertSame(TicketStatus::Resolved, $ticket->getStatus());
    }

    public function testClosingRecordsTheDate(): void
    {
        $ticket = (new Ticket())->close();

        self::assertTrue($ticket->isClosed());
        self::assertNotNull($ticket->getClosedAt());
    }

    public function testSettingClosedStatusGoesThroughClose(): void
    {
        $ticket = (new Ticket())->setStatus(TicketStatus::Closed);

        self::assertNotNull($ticket->getClosedAt());
    }

    public function testReopeningClearsTheClosingDate(): void
    {
        $ticket = (new Ticket())->close()->setStatus(TicketStatus::InProgress);

        self::assertFalse($ticket->isClosed());
        self::assertNull($ticket->getClosedAt());
    }

    public function testAddCommentLinksBothSides(): void
    {
        $ticket = new Ticket();
        $comment = new Comment();

        $ticket->addComment($comment);
        $ticket->addComment($comment);

        self::assertCount(1, $ticket->getComments());
        self::assertSame($ticket, $comment->getTicket());
    }

    public function testAuthorshipDoesNotMatchAnotherUnsavedUser(): void
    {
        $author = new User();
        $ticket = (new Ticket())->setAuthor($author);

        self::assertTrue($ticket->isAuthoredBy($author));
        self::assertFalse($ticket->isAuthoredBy(new User()));
    }
}
