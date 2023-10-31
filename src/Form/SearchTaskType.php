<?php 

namespace App\Form;

use App\Entity\User;
use App\Entity\Tasks;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;



class SearchTaskType extends AbstractType
{

  public function __construct(Security $security)
  {
      $this->security = $security;
  }
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
      $builder
          ->add('user', EntityType::class, [
              'class' => User::class,
              'choice_label' => 'email',
              'required' => true,
              'placeholder' => 'Choose a user',
              'disabled' => !$this->security->isGranted('ROLE_SUPER_ADMIN'),
          ])
      ;
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
        'data_class' => Tasks::class,
    ]);
  }
}