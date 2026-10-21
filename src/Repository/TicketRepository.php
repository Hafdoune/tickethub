<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * @return Paginator<Ticket>
     */
    public function paginateVisibleFor(User $user, TicketFilter $filter, int $page = 1, int $limit = 20): Paginator
    {
        $qb = $this->visibleFor($user)
            ->addSelect('a', 'c', 'asg')
            ->leftJoin('t.assignee', 'asg')
            ->orderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit);

        $this->applyFilter($qb, $filter);

        return new Paginator($qb);
    }

    /**
     * @return array<string, int>
     */
    public function countByStatusFor(User $user): array
    {
        $rows = $this->visibleFor($user)
            ->select('t.status AS status', 'COUNT(t.id) AS total')
            ->groupBy('t.status')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys(array_map(static fn (TicketStatus $s) => $s->value, TicketStatus::cases()), 0);

        foreach ($rows as $row) {
            $status = $row['status'] instanceof TicketStatus ? $row['status']->value : (string) $row['status'];
            $counts[$status] = (int) $row['total'];
        }

        return $counts;
    }

    private function visibleFor(User $user): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.author', 'a')
            ->join('t.category', 'c');

        if (!$user->isAgent()) {
            $qb->andWhere('t.author = :user')->setParameter('user', $user);
        }

        return $qb;
    }

    private function applyFilter(QueryBuilder $qb, TicketFilter $filter): void
    {
        if ($filter->status !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filter->status);
        }

        if ($filter->priority !== null) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $filter->priority);
        }

        if ($filter->categoryId !== null) {
            $qb->andWhere('c.id = :category')->setParameter('category', $filter->categoryId);
        }

        if ($filter->assignedTo !== null) {
            $qb->andWhere('asg.id = :assignee')->setParameter('assignee', $filter->assignedTo);
        }

        if ($filter->search !== null && $filter->search !== '') {
            $qb->andWhere('t.title LIKE :q OR t.description LIKE :q')
                ->setParameter('q', '%'.addcslashes($filter->search, '%_').'%');
        }
    }
}
