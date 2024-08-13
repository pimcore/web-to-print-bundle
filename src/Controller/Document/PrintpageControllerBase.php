<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\WebToPrintBundle\Controller\Document;

use Exception;
use Pimcore\Bundle\AdminBundle\Controller\Admin\Document\DocumentControllerBase;
use Pimcore\Bundle\WebToPrintBundle\Config;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\PrintAbstract;
use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Schedule\Task;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
abstract class PrintpageControllerBase extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id", name="getdatabyid", methods={"GET"})
     *
     * @throws Exception
     */
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $page = PrintAbstract::getById($request->query->getInt('id'));

        if (!$page) {
            throw $this->createNotFoundException('Document not found');
        }

        if (($lock = $this->checkForLock($page, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $page = clone $page;
        $draftVersion = null;
        $page = $this->getLatestVersion($page, $draftVersion);

        $page->getVersions();

        // unset useless data
        $page->setEditables(null);
        $page->setChildren(null);

        $data = $page->getObjectVars();
        $data['locked'] = $page->isLocked();

        $this->addTranslationsData($page, $data);
        $this->minimizeProperties($page, $data);

        $data['url'] = $page->getUrl();
        $data['scheduledTasks'] = array_map(
            static function (Task $task) {
                return $task->getObjectVars();
            },
            $page->getScheduledTasks()
        );

        if ($page->getContentMainDocument()) {
            $data['contentMainDocumentPath'] = $page->getContentMainDocument()->getRealFullPath();
        }

        return $this->preSendDataActions($data, $page, $draftVersion);
    }

    /**
     * @Route("/save", name="save", methods={"PUT", "POST"})
     *
     * @throws ValidationException
     */
    public function saveAction(Request $request): JsonResponse
    {
        $page = PrintAbstract::getById($request->request->getInt('id'));
        if (!$page) {
            throw $this->createNotFoundException('Document not found');
        }

        $page = $this->getLatestVersion($page);

        Document\Service::saveElementToSession($page, $request->getSession()->getId());

        if ($request->query->getString('task') !== self::TASK_SAVE) {
            //check, if to cleanup existing elements of document
            $config = Config::get();
            if ($config['generalDocumentSaveMode'] == 'cleanup') {
                $page->setEditables([]);
            }
        }

        [$task, $page, $version] = $this->saveDocument($page, $request);

        if ($task === self::TASK_PUBLISH || $task === self::TASK_UNPUBLISH) {
            $treeData = $this->getTreeNodeConfig($page);

            return $this->adminJson([
                'success' => true,
                'data' => [
                    'versionDate' => $page->getModificationDate(),
                    'versionCount' => $page->getVersionCount(),
                ],
                'treeData' => $treeData,
            ]);
        } else {
            $draftData = [];
            if ($version) {
                $draftData = [
                    'id' => $version->getId(),
                    'modificationDate' => $version->getDate(),
                    'isAutoSave' => $version->isAutoSave(),
                ];
            }

            return $this->adminJson(['success' => true, 'draft' => $draftData]);
        }
    }

    /**
     * @Route("/add", name="add", methods={"POST"})
     *
     *
     */
    public function addAction(Request $request): JsonResponse
    {
        $success = false;
        $errorMessage = '';

        // check for permission
        $parentDocument = Document::getById($request->request->getInt('parentId'));
        $document = null;
        if ($parentDocument->isAllowed('create')) {
            $intendedPath = $parentDocument->getRealFullPath() . '/' . $request->request->getString('key');

            if (!Document\Service::pathExists($intendedPath)) {
                $createValues = [
                    'userOwner' => $this->getAdminUser()->getId(),
                    'userModification' => $this->getAdminUser()->getId(),
                    'published' => false,
                ];

                $createValues['key'] = \Pimcore\Model\Element\Service::getValidKey($request->request->getString('key'), 'document');

                // check for a docType
                $docType = Document\DocType::getById($request->request->getString('docTypeId'));
                if ($docType) {
                    $createValues['template'] = $docType->getTemplate();
                    $createValues['controller'] = $docType->getController();
                } else {
                    $config = $this->getParameter('pimcore_web_to_print');
                    if ($request->request->getString('type') === 'printpage') {
                        $createValues['controller'] = $config['default_controller_print_page'];
                    } elseif ($request->request->getString('type') === 'printcontainer') {
                        $createValues['controller'] = $config['default_controller_print_container'];
                    }
                }

                if ($request->request->has('inheritanceSource')) {
                    $createValues['contentMainDocumentId'] = $request->request->getInt('inheritanceSource');
                }

                $className = \Pimcore::getContainer()->get('pimcore.class.resolver.document')->resolve($request->request->getString('type'));

                /** @var Document $document */
                $document = \Pimcore::getContainer()->get('pimcore.model.factory')->build($className);

                $document = $document::create($parentDocument->getId(), $createValues);

                try {
                    $document->save();
                    $success = true;
                } catch (Exception $e) {
                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                $errorMessage = "prevented adding a document because document with same path+key [ $intendedPath ] already exists";
                Logger::debug($errorMessage);
            }
        } else {
            $errorMessage = 'prevented adding a document because of missing permissions';
            Logger::debug($errorMessage);
        }

        if ($success && $document instanceof Document) {
            if ($translationsBaseDocumentId = $request->request->getInt('translationsBaseDocument')) {
                $translationsBaseDocument = Document::getById($translationsBaseDocumentId);

                $properties = $translationsBaseDocument->getProperties();
                $properties = array_merge($properties, $document->getProperties());
                $document->setProperties($properties);
                $document->setProperty('language', 'text', $request->request->getString('language'), false, true);
                $document->save();

                $service = new Document\Service();
                $service->addTranslation($translationsBaseDocument, $document);
            }

            return $this->adminJson([
                'success' => $success,
                'id' => $document->getId(),
                'type' => $document->getType(),
            ]);
        }

        return $this->adminJson([
            'success' => $success,
            'message' => $errorMessage,
        ]);
    }

    protected function setValuesToDocument(Request $request, Document $document): void
    {
        $this->addSettingsToDocument($request, $document);
        $this->addDataToDocument($request, $document);
        $this->addPropertiesToDocument($request, $document);
    }

    /**
     * @Route("/active-generate-process", name="activegenerateprocess", methods={"POST"})
     *
     * @throws Exception
     */
    public function activeGenerateProcessAction(Request $request): JsonResponse
    {
        $document = PrintAbstract::getById($request->request->getInt('id'));

        if (!$document) {
            throw $this->createNotFoundException('Document with id ' . $request->request->getInt('id') . ' not found.');
        }

        $date = $document->getLastGeneratedDate();
        if ($date) {
            $date = $date->format('Y-m-d H:i');
        }

        $inProgress = $document->getInProgress();

        $statusUpdate = [];
        if ($inProgress) {
            $statusUpdate = Processor::getInstance()->getStatusUpdate($document->getId());
        }

        return $this->adminJson([
            'activeGenerateProcess' => !empty($inProgress),
            'date' => $date,
            'message' => $document->getLastGenerateMessage(),
            'downloadAvailable' => $this->checkFileExists($document->getPdfFileName()),
            'statusUpdate' => $statusUpdate,
        ]);
    }

    /**
     * @Route("/pdf-download", name="pdfdownload", methods={"GET"})
     *
     * @throws Exception
     */
    public function pdfDownloadAction(Request $request): BinaryFileResponse
    {
        $document = PrintAbstract::getById($request->query->getInt('id'));

        if (!$document) {
            throw $this->createNotFoundException('Document with id ' . $request->query->getInt('id') . ' not found.');
        }

        if ($this->checkFileExists($document->getPdfFileName())) {
            $response = new BinaryFileResponse($document->getPdfFileName());
            $response->headers->set('Content-Type', 'application/pdf');
            if ($request->query->has('download')) {
                $response->setContentDisposition('attachment', $document->getKey() . '.pdf');
            }

            return $response;
        } else {
            throw $this->createNotFoundException('File does not exist');
        }
    }

    /**
     * @Route("/start-pdf-generation", name="startpdfgeneration", methods={"POST"})
     *
     * @throws Exception
     */
    public function startPdfGenerationAction(Request $request, \Pimcore\Config $config): JsonResponse
    {
        $allParams = json_decode($request->getContent(), true);

        $document = PrintAbstract::getById($allParams['id']);

        if (!$document) {
            throw $this->createNotFoundException('Document with id ' . $allParams['id'] . ' not found.');
        }

        if (empty($allParams['hostName'] = $config['general']['domain'])) {
            $allParams['hostName'] = $_SERVER['HTTP_HOST'];
        }

        $https = $_SERVER['HTTPS'] ?? 'off';
        $allParams['protocol'] = $https === 'on' ? 'https' : 'http';
        $pdf = $document->getPdfFileName();
        if (is_file($pdf)) {
            unlink($pdf);
        }

        $result = $document->generatePdf($allParams);

        $this->saveProcessingOptions($document->getId(), $allParams);

        return $this->adminJson(['success' => $result]);
    }

    /**
     * @Route("/check-pdf-dirty", name="checkpdfdirty", methods={"GET"})
     *
     *
     */
    public function checkPdfDirtyAction(Request $request): JsonResponse
    {
        $printDocument = PrintAbstract::getById($request->query->getInt('id'));

        $dirty = true;
        if ($printDocument) {
            $dirty = $printDocument->pdfIsDirty();
        }

        return $this->adminJson(['pdfDirty' => $dirty]);
    }

    /**
     * @Route("/get-processing-options", name="getprocessingoptions", methods={"GET"})
     *
     *
     */
    public function getProcessingOptionsAction(Request $request): JsonResponse
    {
        $options = Processor::getInstance()->getProcessingOptions();

        $returnValue = [];

        $storedValues = $this->getStoredProcessingOptions($request->query->getInt('id'));

        foreach ($options as $option) {
            $value = $option['default'];
            if ($storedValues && array_key_exists($option['name'], $storedValues)) {
                $value = $storedValues[$option['name']];
            }

            $returnValue[] = [
                'name' => $option['name'],
                'label' => $option['name'],
                'value' => $value,
                'type' => $option['type'],
                'values' => isset($option['values']) ? $option['values'] : null,
            ];
        }

        return $this->adminJson(['options' => $returnValue]);
    }

    private function getStoredProcessingOptions(int $documentId): array
    {
        $filename = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-processingoptions-' . $documentId . '_' . $this->getAdminUser()->getId() . '.psf';
        if ($this->checkFileExists($filename)) {
            $options = \Pimcore\Tool\Serialize::unserialize(file_get_contents($filename));
            if (is_array($options)) {
                return $options;
            }
        }

        return [];
    }

    private function saveProcessingOptions(int $documentId, array $options): void
    {
        file_put_contents(PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-processingoptions-' . $documentId . '_' . $this->getAdminUser()->getId() . '.psf', \Pimcore\Tool\Serialize::serialize($options));
    }

    /**
     * @Route("/cancel-generation", name="cancelgeneration", methods={"DELETE"})
     *
     * @throws Exception
     */
    public function cancelGenerationAction(Request $request): JsonResponse
    {
        Processor::getInstance()->cancelGeneration($request->request->getInt('id'));

        return $this->adminJson(['success' => true]);
    }

    /**
     * Checks if a file exists on the filesystem.
     *
     *
     */
    private function checkFileExists(string $filePath): bool
    {
        $this->invalidateFsCacheFor($filePath);

        return file_exists($filePath);
    }

    /**
     * Invalidates the FS cache for a given file path by opening and closing the directory.
     * This is a workaround for a bug which happens when the local filesystem is using a NFS with cache.
     *
     *
     */
    private function invalidateFsCacheFor(string $filePath): void
    {
        try {
            if ($dh = opendir(dirname($filePath))) {
                closedir($dh);
            }
        } catch (Exception) {
        }
    }
}
