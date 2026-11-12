<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Controller\Backend;

use Contao\CoreBundle\Controller\Backend\AbstractBackendController;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\Preset\PresetExporter;
use Plakart\CssUtilitiesBundle\Preset\PresetSerializer;
use Plakart\CssUtilitiesBundle\Widget\ClassSelectionWidget;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('%contao.backend.route_prefix%/pcu-css-classes/export', name: self::ROUTE, defaults: ['_scope' => 'backend'], methods: ['GET', 'POST'])]
class ExportController extends AbstractBackendController
{
    public const ROUTE = 'plakart_css_utilities_export';

    public function __construct(
        private readonly Connection $connection,
        private readonly PresetExporter $exporter,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'pcu_css_classes');

        if ($request->isMethod('POST')) {
            $preset = $this->exporter->export(
                self::ids($request->request->all('groups')),
                self::ids($request->request->all('classes')),
                self::ids($request->request->all('spacing')),
            );

            $response = new Response(PresetSerializer::toJson($preset));
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                \sprintf('css-classes-%s.json', date('Y-m-d')),
            ));

            return $response;
        }

        $GLOBALS['TL_CSS'][] = ClassSelectionWidget::STYLESHEET;

        return $this->render('@Contao/backend/pcu_css_classes/export.html.twig', [
            'headline' => $this->translator->trans('MSC.pcu_export_headline', [], 'contao_default'),
            'title' => $this->translator->trans('MSC.pcu_export_headline', [], 'contao_default'),
            'groups' => $this->loadTree(),
            'spacing' => $this->connection->fetchAllAssociative('SELECT step, label FROM tl_pcu_spacing ORDER BY step'),
        ]);
    }

    /**
     * @param array<mixed> $values
     *
     * @return list<int>
     */
    private static function ids(array $values): array
    {
        return array_values(array_filter(array_map(intval(...), $values)));
    }

    /**
     * @return list<array{id: int, title: string, alias: string, classes: list<array{id: int, label: string, cssClass: string}>}>
     */
    private function loadTree(): array
    {
        $classes = [];

        foreach ($this->connection->fetchAllAssociative('SELECT id, pid, label, cssClass FROM tl_pcu_class ORDER BY pid, sorting, id') as $row) {
            $classes[(int) $row['pid']][] = ['id' => (int) $row['id'], 'label' => (string) $row['label'], 'cssClass' => (string) $row['cssClass']];
        }

        $groups = [];

        foreach ($this->connection->fetchAllAssociative('SELECT id, title, alias FROM tl_pcu_class_group ORDER BY title, id') as $row) {
            $groups[] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'alias' => (string) $row['alias'],
                'classes' => $classes[(int) $row['id']] ?? [],
            ];
        }

        return $groups;
    }
}
