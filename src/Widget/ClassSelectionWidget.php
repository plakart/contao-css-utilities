<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Widget;

use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroup;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;
use Plakart\CssUtilitiesBundle\ClassSelection\SelectionNormalizer;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;

/**
 * Renders one select per class group that applies to the edited record.
 *
 * The value is a serialized array of group alias => list of CSS classes.
 */
class ClassSelectionWidget extends Widget
{
    public const STYLESHEET = 'bundles/plakartcssutilities/css/backend.css|static';

    protected $blnSubmitInput = true;

    protected $strTemplate = 'be_widget';

    /**
     * @var list<ClassGroup>|null
     */
    private ?array $groups = null;

    /**
     * @param list<ClassGroup> $groups
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function generate(): string
    {
        $GLOBALS['TL_CSS'][] = self::STYLESHEET;

        $selection = SelectionNormalizer::normalize($this->varValue, $this->getGroups());
        $html = [];

        foreach ($this->getGroups() as $group) {
            $html[] = $this->generateGroup($group, $selection[$group->alias] ?? []);
        }

        return \sprintf('<div class="pcu-class-selection" id="ctrl_%s">%s</div>%s', $this->strId, implode('', $html), $this->wizard);
    }

    protected function validator($varInput): string
    {
        $selection = SelectionNormalizer::normalize($varInput, $this->getGroups());

        return [] === $selection ? '' : serialize($selection);
    }

    /**
     * @param list<string> $selected
     */
    private function generateGroup(ClassGroup $group, array $selected): string
    {
        $id = $this->strId.'_'.$group->alias;
        $name = $this->strName.'['.$group->alias.']';
        $options = [];

        if (!$group->multiple) {
            $options[] = '<option value="">-</option>';
        }

        foreach ($group->classes as $class => $label) {
            $options[] = \sprintf(
                '<option value="%s"%s>%s</option>',
                StringUtil::specialchars($class),
                \in_array($class, $selected, true) ? ' selected' : '',
                StringUtil::specialchars($label),
            );
        }

        if ($group->multiple) {
            $select = \sprintf(
                '<input type="hidden" name="%s" value=""><div class="tl_select_wrapper" data-controller="contao--choices"><select name="%s[]" id="ctrl_%s" class="tl_mselect" multiple data-action="focus->contao--scroll-offset#store">%s</select></div>',
                $name,
                $name,
                $id,
                implode('', $options),
            );
        } else {
            $select = \sprintf(
                '<select name="%s" id="ctrl_%s" class="tl_select" data-action="focus->contao--scroll-offset#store">%s</select>',
                $name,
                $id,
                implode('', $options),
            );
        }

        return \sprintf(
            '<div class="pcu-class-selection__group"><label for="ctrl_%s">%s</label>%s</div>',
            $id,
            StringUtil::specialchars($group->title),
            $select,
        );
    }

    /**
     * @return list<ClassGroup>
     */
    private function getGroups(): array
    {
        if (null !== $this->groups) {
            return $this->groups;
        }

        $dc = $this->objDca;

        if (!$dc instanceof DataContainer) {
            return $this->groups = [];
        }

        $container = System::getContainer();
        $target = $container->get(TargetRegistry::class)->forTable($dc->table);

        if (null === $target) {
            return $this->groups = [];
        }

        $type = null;

        if (null !== $target->typeField) {
            $record = $dc->getCurrentRecord();
            $type = isset($record[$target->typeField]) ? (string) $record[$target->typeField] : null;
        }

        return $this->groups = $container->get(ClassGroupRepository::class)->findPublishedFor($target->key, $type);
    }
}
