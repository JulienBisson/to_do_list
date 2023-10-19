<?php 

namespace App\Form;
// use App\Entity\User;
use App\Entity\Tasks;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;



class SearchTaskType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
    ->add(child: "title")
    ->add(child: "content")
    ->add(child: "title");
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
      $resolver->setDefaults([
          'data_class' => Tasks::class,
      ]);
  }

}