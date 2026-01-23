<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;

class PlatformParameterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PlatformParameter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Platform Parameter')
            ->setEntityLabelInPlural('Platform Parameters')
            ->setPageTitle(Crud::PAGE_INDEX, 'Platform Parameters')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit Platform Parameter')
            ->setPageTitle(Crud::PAGE_NEW, 'Create Platform Parameter')
            ->setDefaultSort(['key' => 'ASC'])
            ->setPaginatorPageSize(25)
            ->setHelp(
                Crud::PAGE_INDEX,
                'Platform parameters are global configuration values accessible throughout the application.',
            );
    }

    public function configureFields(string $pageName): iterable
    {
        $typeChoices = [];
        foreach (ParameterType::cases() as $type) {
            $typeChoices[$type->getLabel()] = $type->value;
        }

        yield IdField::new('id')
            ->onlyOnDetail();

        yield TextField::new('key')
            ->setHelp('Unique identifier for the parameter (e.g., "site_name", "max_upload_size")')
            ->setDisabled(Crud::PAGE_EDIT === $pageName);

        yield TextField::new('label')
            ->setHelp('Human-readable label for the parameter');

        yield ChoiceField::new('type')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\EnumType::class)
            ->setFormTypeOption('class', ParameterType::class)
            ->setFormTypeOption('choice_label', fn (ParameterType $type) => $type->getLabel())
            ->renderAsBadges([
                ParameterType::STRING->value => 'primary',
                ParameterType::INTEGER->value => 'info',
                ParameterType::BOOLEAN->value => 'success',
                ParameterType::JSON->value => 'warning',
                ParameterType::LIST->value => 'secondary',
            ])
            ->setHelp('Data type of the parameter value')
            ->setDisabled(Crud::PAGE_EDIT === $pageName)
            ->formatValue(function ($value) {
                if ($value instanceof ParameterType) {
                    return $value->getLabel();
                }

                return ParameterType::from((string) $value)->getLabel();
            });

        yield TextareaField::new('value')
            ->setHelp($this->getValueHelpText())
            ->hideOnIndex();

        yield TextField::new('value', 'Value')
            ->onlyOnIndex()
            ->formatValue(function ($value) {
                $stringValue = (string) $value;
                if (\mb_strlen($stringValue) > 50) {
                    return \mb_substr($stringValue, 0, 50).'...';
                }

                return $stringValue;
            });

        yield TextareaField::new('description')
            ->setHelp('Optional description explaining the parameter\'s purpose')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Created At')
            ->hideOnIndex()
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Last Updated')
            ->hideOnForm();
    }

    private function getValueHelpText(): string
    {
        return <<<'HELP'
            Enter the parameter value according to its type:
            • STRING: Plain text value
            • INTEGER: Numeric value (e.g., 42)
            • BOOLEAN: true, false, 1, 0, yes, no
            • JSON: Valid JSON object or array (e.g., {"key": "value"})
            • LIST: One value per line (will be split by newlines)
            HELP;
    }
}
