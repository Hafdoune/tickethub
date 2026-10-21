<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TicketFilter;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/tickets')]
class TicketController extends AbstractController
{
    private const PER_PAGE = 15;

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
}
