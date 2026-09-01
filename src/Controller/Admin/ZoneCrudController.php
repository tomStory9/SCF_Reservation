<?php

namespace App\Controller\Admin;

use App\Entity\Zone;
use App\Enum\ZoneType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class ZoneCrudController extends AbstractCrudController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Zone::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'admin.field.name');
        yield ChoiceField::new('typeZone', 'admin.field.type')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => ZoneType::class,
            ])
            ->formatValue(fn ($value) => null === $value
                ? null
                : $this->translator->trans('admin.enum.zone_type.'.$value->value));
        yield AssociationField::new('facility', 'admin.field.facility')
            ->formatValue(fn ($value, $entity) => $value?->getName())
            ->setFormTypeOption('choice_label', 'name');
        yield TextField::new('frDesc', 'admin.field.zone_desc_fr');
        yield TextField::new('enDesc', 'admin.field.zone_desc_en');
        yield TextField::new('jpDesc', 'admin.field.zone_desc_jp');
    }

    public function configureFilters(Filters $filters): Filters
    {
        $typeChoices = [];
        foreach (ZoneType::cases() as $case) {
            $typeChoices['admin.enum.zone_type.'.$case->value] = $case;
        }

        return $filters
            ->add(TextFilter::new('name', 'admin.field.name'))
            ->add(
                ChoiceFilter::new('typeZone', 'admin.field.type')
                ->setChoices($typeChoices)
            )
            ->add(EntityFilter::new('facility', 'admin.field.facility'))
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.zone')
            ->setEntityLabelInPlural('admin.entity.zones');
    }
}
