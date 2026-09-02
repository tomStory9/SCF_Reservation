<?php

namespace App\Form;

use App\Entity\Specialty;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'information.email.label',
                'attr' => ['placeholder' => 'information.email.placeholder'],
                'translation_domain' => 'forms',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(message: 'information.email.not_blank'),
                    new Email(message: 'information.email.invalid'),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'information.name.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.name.placeholder'],
                'required' => true,
                'constraints' => [new NotBlank(message: 'information.name.not_blank')],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'information.lastname.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.lastname.placeholder'],
                'required' => true,
                'constraints' => [new NotBlank(message: 'information.lastname.not_blank')],
            ])
            ->add('phone', TelType::class, [
                'label' => 'information.phone.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.phone.placeholder'],
                'required' => true,
                'constraints' => [new NotBlank(message: 'information.phone.not_blank')],
            ])
            ->add('company', TextType::class, [
                'label' => 'information.company.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.company.placeholder'],
                'required' => false,
            ])
            ->add('nationalitie', TextType::class, [
                'label' => 'information.nationalitie.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.nationalitie.placeholder'],
                'required' => true,
                'constraints' => [new NotBlank(message: 'information.nationalitie.not_blank')],
            ])
            ->add('residenceCity', TextType::class, [
                'label' => 'information.residence_city.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.residence_city.placeholder'],
                'required' => true,
                'constraints' => [new NotBlank(message: 'information.residence_city.not_blank')],
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'information.birth_date.label',
                'translation_domain' => 'forms',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'constraints' => [new NotBlank(message: 'information.birth_date.not_blank')],
            ])
            ->add('practiceStartYear', IntegerType::class, [
                'label' => 'information.practice_start_year.label',
                'translation_domain' => 'forms',
                'attr' => [
                    'placeholder' => 'information.practice_start_year.placeholder',
                    'min' => 1950,
                    'max' => (int) date('Y'),
                ],
                'required' => true,
                'constraints' => [new NotBlank(message: 'information.practice_start_year.not_blank')],
            ])
            ->add('lastPerformance', TextType::class, [
                'label' => 'information.last_performance.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.last_performance.placeholder'],
                'required' => false,
            ])
            ->add('instagramAccount', TextType::class, [
                'label' => 'information.instagram.label',
                'translation_domain' => 'forms',
                'attr' => ['placeholder' => 'information.instagram.placeholder'],
                'required' => false,
            ])
            ->add('specialties', EntityType::class, [
                'class' => Specialty::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => 'information.specialties.label',
                'translation_domain' => 'forms',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
