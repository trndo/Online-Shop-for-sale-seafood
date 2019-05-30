<?php


namespace App\Form\CategoryForm;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name',TextType::class,[
            'label' => 'Назваие категории',
            'attr' => [
                'class' => 'form-control'
            ]
        ])
        ->add('seoTitle',TextType::class,[
            'label' => 'Сео тайтл',
            'attr' => [
                'class' => 'form-control'
            ]
        ])
        ->add('seoDescription',TextareaType::class,[
            'label' => 'Сео дескрипшн',
            'attr' => [
                'class' => 'form-control'
            ]
        ])
        ->add('save',SubmitType::class,[
            'label' => 'Добавить категорию!',
            'attr' => [
                'class' => 'btn btn-primary btn-in-form'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'inherit_data' => true
        ]);
    }
}