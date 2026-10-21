<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const PASSWORD = 'password';

    private const CATEGORIES = ['Internet', 'Mobile', 'Facturation', 'Équipement', 'Compte client'];

    private const SUBJECTS = [
        'Pas de connexion internet depuis ce matin',
        'Débit très lent en soirée',
        'Montant de la facture incorrect',
        'La box redémarre en boucle',
        'Impossible d\'activer ma nouvelle carte SIM',
        'Portabilité du numéro bloquée',
        'Le roaming ne fonctionne pas à l\'étranger',
        'Changement d\'offre non appliqué',
        'Le Wi-Fi se déconnecte toutes les heures',
        'Demande de remboursement après coupure',
        'Mot de passe de l\'espace client perdu',
        'Décodeur TV sans signal',
    ];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(2026);

        $categories = [];
        foreach (self::CATEGORIES as $name) {
            $manager->persist($categories[] = new Category($name));
        }

        $this->createUser($manager, 'admin@tickethub.test', 'Admin TicketHub', [User::ROLE_ADMIN]);
        $agents = [
            $this->createUser($manager, 'agent@tickethub.test', 'Sara Benali', [User::ROLE_AGENT]),
            $this->createUser($manager, 'agent2@tickethub.test', 'Youssef Amrani', [User::ROLE_AGENT]),
        ];

        $customers = [$this->createUser($manager, 'client@tickethub.test', 'Karim Idrissi')];
        for ($i = 1; $i <= 6; ++$i) {
            $customers[] = $this->createUser($manager, "client{$i}@tickethub.test", $faker->name());
        }

        for ($i = 0; $i < 45; ++$i) {
            $manager->persist($this->createTicket($faker, $faker->randomElement($customers), $agents, $categories));
        }

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(ObjectManager $manager, string $email, string $name, array $roles = []): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFullName($name)
            ->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));

        $manager->persist($user);

        return $user;
    }

    /**
     * @param list<User>     $agents
     * @param list<Category> $categories
     */
    private function createTicket(Generator $faker, User $author, array $agents, array $categories): Ticket
    {
        $createdAt = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-60 days', '-1 hour'));

        $ticket = (new Ticket($createdAt))
            ->setTitle($faker->randomElement(self::SUBJECTS))
            ->setDescription($faker->paragraphs(2, true))
            ->setPriority($faker->randomElement(TicketPriority::cases()))
            ->setCategory($faker->randomElement($categories))
            ->setAuthor($author);

        $status = $faker->randomElement(TicketStatus::cases());
        if ($status !== TicketStatus::Open) {
            $agent = $faker->randomElement($agents);
            $ticket->assignTo($agent)->setStatus($status);

            foreach (range(1, $faker->numberBetween(1, 3)) as $n) {
                $ticket->addComment(
                    (new Comment($createdAt->modify("+{$n} hours")))
                        ->setAuthor($n % 2 === 1 ? $agent : $author)
                        ->setContent($faker->sentences(2, true))
                );
            }
        }

        return $ticket;
    }
}
