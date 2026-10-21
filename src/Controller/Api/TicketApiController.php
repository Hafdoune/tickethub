<?php

namespace App\Controller\Api;

use App\Dto\CommentPayload;
use App\Dto\CreateTicketPayload;
use App\Dto\UpdateTicketPayload;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Repository\CategoryRepository;
use App\Repository\TicketFilter;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Security\Voter\TicketVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_', format: 'json')]
final class TicketApiController extends AbstractController
{
    private const MAX_LIMIT = 50;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($user, context: ['groups' => ['user:me']]);
    }

    #[Route('/tickets', name: 'ticket_list', methods: ['GET'])]
    public function list(Request $request, TicketRepository $tickets, #[CurrentUser] User $user): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_LIMIT, max(1, $request->query->getInt('limit', 20)));

        $paginator = $tickets->paginateVisibleFor($user, TicketFilter::fromQuery($request->query->all()), $page, $limit);
        $total = \count($paginator);

        return $this->json([
            'data' => iterator_to_array($paginator),
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ], context: ['groups' => ['ticket:list']]);
    }

    #[Route('/tickets/{id}', name: 'ticket_show', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[IsGranted(TicketVoter::VIEW, 'ticket')]
    public function show(Ticket $ticket): JsonResponse
    {
        return $this->ticketResponse($ticket);
    }

    #[Route('/tickets', name: 'ticket_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateTicketPayload $payload,
        CategoryRepository $categories,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $ticket = (new Ticket())
            ->setTitle($payload->title)
            ->setDescription($payload->description)
            ->setPriority($payload->priority)
            ->setCategory($this->findCategory($categories, $payload->categoryId))
            ->setAuthor($user);

        $this->em->persist($ticket);
        $this->em->flush();

        return $this->ticketResponse($ticket, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_ticket_show', ['id' => $ticket->getId()]),
        ]);
    }

    #[Route('/tickets/{id}', name: 'ticket_update', requirements: ['id' => Requirement::DIGITS], methods: ['PATCH'])]
    #[IsGranted(TicketVoter::VIEW, 'ticket')]
    public function update(
        Ticket $ticket,
        #[MapRequestPayload] UpdateTicketPayload $payload,
        CategoryRepository $categories,
        UserRepository $users,
    ): JsonResponse {
        if ($payload->changesContent()) {
            $this->denyAccessUnlessGranted(TicketVoter::EDIT, $ticket);

            if ($payload->title !== null) {
                $ticket->setTitle($payload->title);
            }
            if ($payload->description !== null) {
                $ticket->setDescription($payload->description);
            }
            if ($payload->priority !== null) {
                $ticket->setPriority($payload->priority);
            }
            if ($payload->categoryId !== null) {
                $ticket->setCategory($this->findCategory($categories, $payload->categoryId));
            }
        }

        if ($payload->changesWorkflow()) {
            $this->denyAccessUnlessGranted(TicketVoter::MANAGE, $ticket);

            if ($payload->assigneeId !== null) {
                $ticket->assignTo($payload->assigneeId === 0 ? null : $this->findAgent($users, $payload->assigneeId));
            }
            if ($payload->status !== null) {
                $ticket->setStatus($payload->status);
            }
        }

        $this->em->flush();

        return $this->ticketResponse($ticket);
    }

    #[Route('/tickets/{id}/close', name: 'ticket_close', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(TicketVoter::CLOSE, 'ticket')]
    public function close(Ticket $ticket): JsonResponse
    {
        $ticket->close();
        $this->em->flush();

        return $this->ticketResponse($ticket);
    }

    #[Route('/tickets/{id}/comments', name: 'ticket_comment', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(TicketVoter::COMMENT, 'ticket')]
    public function comment(Ticket $ticket, #[MapRequestPayload] CommentPayload $payload, #[CurrentUser] User $user): JsonResponse
    {
        $comment = (new Comment())->setAuthor($user)->setContent($payload->content);
        $ticket->addComment($comment);

        if (!$user->isAgent() && $ticket->getStatus() === TicketStatus::Resolved) {
            $ticket->setStatus(TicketStatus::InProgress);
        }

        $this->em->flush();

        return $this->json($comment, Response::HTTP_CREATED, context: ['groups' => ['comment:item']]);
    }

    /**
     * @param array<string, string> $headers
     */
    private function ticketResponse(Ticket $ticket, int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return $this->json($ticket, $status, $headers, ['groups' => ['ticket:item']]);
    }

    private function findCategory(CategoryRepository $categories, ?int $id): Category
    {
        return $categories->find((int) $id) ?? throw new UnprocessableEntityHttpException(sprintf('Category %d does not exist.', $id));
    }

    private function findAgent(UserRepository $users, int $id): User
    {
        $agent = $users->find($id);

        if ($agent === null || !$agent->isAgent()) {
            throw new UnprocessableEntityHttpException(sprintf('User %d is not an agent.', $id));
        }

        return $agent;
    }
}
