<?php

namespace App\Tests\Support;

use App\Entity\Category;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Security\ApiTokenHandler;
use Doctrine\ORM\EntityManagerInterface;

trait CreatesEntities
{
    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @param list<string> $roles
     */
    protected function createUser(string $email, array $roles = [], ?string $apiToken = null): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFullName(ucfirst(strstr($email, '@', true)))
            ->setRoles($roles)
            ->setPassword('not-used')
            ->setApiToken($apiToken !== null ? ApiTokenHandler::hash($apiToken) : null);

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function createCategory(string $name = 'Internet'): Category
    {
        $category = new Category($name);
        $this->em()->persist($category);
        $this->em()->flush();

        return $category;
    }

    protected function createTicket(User $author, Category $category, string $title = 'Connexion coupée', TicketStatus $status = TicketStatus::Open): Ticket
    {
        $ticket = (new Ticket())
            ->setTitle($title)
            ->setDescription('La connexion ne fonctionne plus depuis ce matin.')
            ->setCategory($category)
            ->setAuthor($author)
            ->setStatus($status);

        $this->em()->persist($ticket);
        $this->em()->flush();

        return $ticket;
    }
}
