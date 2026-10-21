<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketManageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EnumType::class, [
                'class' => TicketStatus::class,
                'label' => 'Statut',
                'choice_label' => static fn (TicketStatus $s) => $s->label(),
            ])
            ->add('assignee', EntityType::class, [
                'class' => User::class,
                'label' => 'Agent assigné',
                'required' => false,
                'placeholder' => 'Non assigné',
                'query_builder' => static fn (UserRepository $users) => $users->agentsQueryBuilder(),
                'setter' => static fn (Ticket $ticket, ?User $agent) => $ticket->assignTo($agent),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Ticket::class]);
    }
}
