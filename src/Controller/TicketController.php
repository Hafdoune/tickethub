<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Form\CommentType;
use App\Form\TicketManageType;
use App\Form\TicketType;
use App\Repository\TicketFilter;
use App\Repository\TicketRepository;
use App\Security\Voter\TicketVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tickets')]
class TicketController extends AbstractController
{
    private const PER_PAGE = 15;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'ticket_index', methods: ['GET'])]
    public function index(Request $request, TicketRepository $tickets, #[CurrentUser] User $user): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $paginator = $tickets->paginateVisibleFor($user, TicketFilter::fromQuery($request->query->all()), $page, self::PER_PAGE);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $paginator,
            'page' => $page,
            'pages' => max(1, (int) ceil(\count($paginator) / self::PER_PAGE)),
        ]);
    }

    #[Route('/new', name: 'ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request, #[CurrentUser] User $user): Response
    {
        $ticket = (new Ticket())->setAuthor($user);
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($ticket);
            $this->em->flush();

            $this->addFlash('success', sprintf('Ticket #%d créé.', $ticket->getId()));

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'ticket_show', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[IsGranted(TicketVoter::VIEW, 'ticket')]
    public function show(Ticket $ticket): Response
    {
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'comment_form' => $this->isGranted(TicketVoter::COMMENT, $ticket) ? $this->commentForm($ticket) : null,
            'manage_form' => $this->isGranted(TicketVoter::MANAGE, $ticket) ? $this->manageForm($ticket) : null,
        ]);
    }

    #[Route('/{id}/edit', name: 'ticket_edit', requirements: ['id' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    #[IsGranted(TicketVoter::EDIT, 'ticket')]
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Ticket mis à jour.');

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/edit.html.twig', ['ticket' => $ticket, 'form' => $form]);
    }

    #[Route('/{id}/comments', name: 'ticket_comment', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(TicketVoter::COMMENT, 'ticket')]
    public function comment(Request $request, Ticket $ticket, #[CurrentUser] User $user): Response
    {
        $comment = (new Comment())->setAuthor($user);
        $form = $this->commentForm($ticket, $comment);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('ticket/show.html.twig', [
                'ticket' => $ticket,
                'comment_form' => $form,
                'manage_form' => $this->isGranted(TicketVoter::MANAGE, $ticket) ? $this->manageForm($ticket) : null,
            ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $ticket->addComment($comment);
        if (!$user->isAgent() && $ticket->getStatus() === TicketStatus::Resolved) {
            $ticket->setStatus(TicketStatus::InProgress);
        }
        $this->em->flush();

        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId(), '_fragment' => 'comment-'.$comment->getId()]);
    }

    #[Route('/{id}/manage', name: 'ticket_manage', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(TicketVoter::MANAGE, 'ticket')]
    public function manage(Request $request, Ticket $ticket): Response
    {
        $form = $this->manageForm($ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Ticket mis à jour.');
        } else {
            $this->addFlash('error', 'Modification impossible.');
        }

        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/{id}/close', name: 'ticket_close', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(TicketVoter::CLOSE, 'ticket')]
    public function close(Request $request, Ticket $ticket): Response
    {
        if (!$this->isCsrfTokenValid('close-ticket-'.$ticket->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $ticket->close();
        $this->em->flush();
        $this->addFlash('success', 'Ticket fermé.');

        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    private function commentForm(Ticket $ticket, ?Comment $comment = null): FormInterface
    {
        return $this->createForm(CommentType::class, $comment, [
            'action' => $this->generateUrl('ticket_comment', ['id' => $ticket->getId()]),
        ]);
    }

    private function manageForm(Ticket $ticket): FormInterface
    {
        return $this->createForm(TicketManageType::class, $ticket, [
            'action' => $this->generateUrl('ticket_manage', ['id' => $ticket->getId()]),
        ]);
    }
}
