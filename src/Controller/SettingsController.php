<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\WebToPrintBundle\Controller;

use Pimcore\Bundle\WebToPrintBundle\Config;
use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Bundle\WebToPrintBundle\Processor\Gotenberg;
use Pimcore\Bundle\WebToPrintBundle\Processor\PdfReactor;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/settings')]
class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/get-web2print', name: 'pimcore_bundle_web2print_settings_getweb2print', methods: ['GET'])]
    public function getWeb2printAction(Request $request): JsonResponse
    {
        $this->checkPermission('web2print_settings');

        $valueArray = Config::getWeb2PrintConfig();

        $response = [
            'values' => $valueArray,
        ];

        return $this->jsonResponse($response);
    }

    #[Route('/set-web2print', name: 'pimcore_bundle_web2print_settings_setweb2print', methods: ['PUT'])]
    public function setWeb2printAction(Request $request): JsonResponse
    {
        $this->checkPermission('web2print_settings');

        $values = $this->decodeJson($request->request->getString('data'));

        unset(
            $values['documentation'],
            $values['requirements'],
            $values['additions'],
            $values['json_converter'],
        );

        Config::save($values);

        return $this->jsonResponse(['success' => true]);
    }

    #[Route('/test-web2print', name: 'pimcore_bundle_web2print_settings_testweb2print', methods: ['GET'])]
    public function testWeb2printAction(Request $request): Response
    {
        $this->checkPermission('web2print_settings');

        $response = $this->render('@PimcoreWebToPrint/settings/test_web2print.html.twig');
        $html = $response->getContent();

        $adapter = Processor::getInstance();
        $params = [];

        if ($adapter instanceof PdfReactor) {
            $params['adapterConfig'] = [
                'javaScriptSettings' => [
                    'enabled' => false,
                ],
                'addLinks' => true,
                'appendLog' => true,
                'debugSettings' => [
                    'all' => true,
                ],
            ];
        } elseif ($adapter instanceof Gotenberg) {
            $params = Config::getWeb2PrintConfig();
            $params = json_decode($params['gotenbergSettings'], true) ?: [];
        }

        $responseOptions = [
            'Content-Type' => 'application/pdf',
        ];

        $pdfData = $adapter->getPdfFromString($html, $params);

        return new Response(
            $pdfData,
            200,
            $responseOptions

        );
    }
}
