<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Controller\Backend;

use Contao\CoreBundle\Controller\Backend\AbstractBackendController;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Message;
use Plakart\CssUtilitiesBundle\Preset\ImportPlan;
use Plakart\CssUtilitiesBundle\Preset\InvalidPresetException;
use Plakart\CssUtilitiesBundle\Preset\PresetImporter;
use Plakart\CssUtilitiesBundle\Preset\PresetSerializer;
use Plakart\CssUtilitiesBundle\Widget\ClassSelectionWidget;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Three steps: choose a file -> preview with selection -> import.
 *
 * The preset JSON travels in a hidden field between preview and import and is
 * validated again, so no session state is needed.
 */
#[Route('%contao.backend.route_prefix%/pcu-css-classes/import', name: self::ROUTE, defaults: ['_scope' => 'backend'], methods: ['GET', 'POST'])]
class ImportController extends AbstractBackendController
{
    public const ROUTE = 'plakart_css_utilities_import';

    private const MAX_SIZE = 1024 * 1024;

    public function __construct(
        private readonly PresetImporter $importer,
        private readonly string $presetFile,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'pcu_css_classes');

        $step = $request->request->getString('step');

        try {
            if ('preview' === $step) {
                return $this->preview($this->readSource($request));
            }

            if ('apply' === $step) {
                return $this->apply($request);
            }
        } catch (InvalidPresetException $e) {
            return $this->renderUpload($e->getMessage());
        }

        return $this->renderUpload();
    }

    private function preview(string $json): Response
    {
        $plan = $this->importer->plan(PresetSerializer::fromJson($json));

        $GLOBALS['TL_CSS'][] = ClassSelectionWidget::STYLESHEET;

        return $this->render('@Contao/backend/pcu_css_classes/import_preview.html.twig', [
            'headline' => $this->lang('pcu_import_headline'),
            'title' => $this->lang('pcu_import_headline'),
            'plan' => $plan,
            'json' => base64_encode($json),
        ]);
    }

    private function apply(Request $request): Response
    {
        $json = base64_decode($request->request->getString('json'), true);

        if (false === $json) {
            throw new InvalidPresetException($this->lang('pcu_import_invalid'));
        }

        $preset = PresetSerializer::fromJson($json);
        $result = $this->importer->apply($preset, self::selection($this->importer->plan($preset), $request), self::updates($request));

        Message::addConfirmation(\sprintf(
            $this->lang('pcu_import_done'),
            $result['groups'],
            $result['classes'],
            $result['updatedGroups'],
            $result['updatedClasses'],
        ));

        return new RedirectResponse($this->generateUrl('contao_backend', ['do' => 'pcu_css_classes']), Response::HTTP_SEE_OTHER);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function selection(ImportPlan $plan, Request $request): array
    {
        $groups = array_map(strval(...), $request->request->all('groups'));
        $classes = $request->request->all('classes');
        $selection = [];

        foreach ($plan->groups as $item) {
            $group = $item['group'];
            $selected = \is_array($classes[$group->alias] ?? null) ? array_values(array_map(strval(...), $classes[$group->alias])) : [];

            // New groups need their own checkbox, existing ones only receive new classes
            if ($item['isNew'] ? \in_array($group->alias, $groups, true) : [] !== $selected) {
                $selection[$group->alias] = $selected;
            }
        }

        return $selection;
    }

    /**
     * @return array{groups: list<string>, classes: array<string, list<string>>}
     */
    private static function updates(Request $request): array
    {
        $classes = [];

        foreach ($request->request->all('updateClasses') as $alias => $values) {
            if (\is_array($values)) {
                $classes[(string) $alias] = array_values(array_map(strval(...), $values));
            }
        }

        return [
            'groups' => array_values(array_map(strval(...), $request->request->all('updateGroups'))),
            'classes' => $classes,
        ];
    }

    private function readSource(Request $request): string
    {
        if ('file' === $request->request->getString('source')) {
            $json = is_file($this->presetFile) ? file_get_contents($this->presetFile) : false;

            if (false === $json) {
                throw new InvalidPresetException(\sprintf($this->lang('pcu_import_no_file'), $this->presetFile));
            }

            return $json;
        }

        $file = $request->files->get('preset');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new InvalidPresetException($this->lang('pcu_import_no_upload'));
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidPresetException($this->lang('pcu_import_too_large'));
        }

        return (string) file_get_contents($file->getPathname());
    }

    private function renderUpload(?string $error = null): Response
    {
        $GLOBALS['TL_CSS'][] = ClassSelectionWidget::STYLESHEET;

        return $this->render('@Contao/backend/pcu_css_classes/import.html.twig', [
            'headline' => $this->lang('pcu_import_headline'),
            'title' => $this->lang('pcu_import_headline'),
            'import_error' => $error,
            'preset_file' => is_file($this->presetFile) ? $this->presetFile : null,
        ]);
    }

    private function lang(string $key): string
    {
        return $this->translator->trans('MSC.'.$key, [], 'contao_default');
    }
}
