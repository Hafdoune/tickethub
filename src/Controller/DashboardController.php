<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use App\Security\Voter\TicketVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted(User::ROLE_AGENT)]
final class DashboardController extends AbstractController
{
    private const CSRF_ID = 'dashboard';

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', ['csrf_id' => self::CSRF_ID]);
    }

    #[Route('/data', name: 'dashboard_data', methods: ['GET'])]
    public function data(TicketRepository $tickets, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'me' => $user->getId(),
            'counts' => $tickets->countByStatusFor($user),
            'byCategory' => $tickets->countActiveByCategory(),
            'tickets' => array_map($this->row(...), $tickets->findActive()),
        ]);
    }

    #[Route('/tickets/{id}/assign', name: 'dashboard_assign', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(TicketVoter::MANAGE, 'ticket')]
    public function assignToMe(Request $request, Ticket $ticket, #[CurrentUser] User $user, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $ticket->assignTo($user);
        $em->flush();

        return $this->json($this->row($ticket));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Ticket $ticket): array
    {
        return [
            'id' => $ticket->getId(),
            'title' => $ticket->getTitle(),
            'status' => $ticket->getStatus()->value,
            'statusLabel' => $ticket->getStatus()->label(),
            'priority' => $ticket->getPriority()->value,
            'priorityLabel' => $ticket->getPriority()->label(),
            'category' => $ticket->getCategory()?->getName(),
            'author' => $ticket->getAuthor()?->getFullName(),
            'assignee' => $ticket->getAssignee() ? [
                'id' => $ticket->getAssignee()->getId(),
                'name' => $ticket->getAssignee()->getFullName(),
            ] : null,
            'createdAt' => $ticket->getCreatedAt()->format(\DATE_ATOM),
            'url' => $this->generateUrl('ticket_show', ['id' => $ticket->getId()]),
            'assignUrl' => $this->generateUrl('dashboard_assign', ['id' => $ticket->getId()]),
        ];
    }
}
