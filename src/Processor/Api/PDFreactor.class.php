<?php
/**
 * RealObjects PDFreactor PHP Client version 12.0.0
 * https://www.pdfreactor.com
 *
 * Released under the following license:
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015-2024 RealObjects GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace com\realobjects\pdfreactor\webservice\client;

class PDFreactor {
    private const ASYNC_503 = "Asynchronous conversions are unavailable.";
    private const ERROR_400 = "Invalid client data.";
    private const ERROR_401 = "Unauthorized.";
    private const ERROR_404 = "Document with the given ID was not found.";
    private const ERROR_413 = "The configuration is too large to process.";
    private const ERROR_429 = "Too many requests made to the PDFreactor Web Service.";
    private const ERROR_503 = "PDFreactor Web Service is unavailable.";

    private $url;
    public $apiKey;

    function __construct($url = null) {
        $this->url = $url;
        if ($url == null) {
            $this->url = "http://localhost:9423/service/rest";
        }
        if (substr($this->url, -1) == "/") {
            $this->url = substr($this->url, 0, -1);
        }
        $this->apiKey = null;
    }

    /**
     * Converts the specified configuration into PDF or image and returns the generated PDF or image.
     * @param Configuration $configuration The configuration object.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return Result The result object containing the converted document and metadata.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function convert($config, &$connectionSettings = null) {
        $this->prepareConfiguration($config);
        try {
            $responseData = $this->createConnectionWithData('convert.json', $connectionSettings, false, false, false, false, $config);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 400:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_400);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 413:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_413);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 500:
                        throw $this->createServerException($responseData);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return json_decode($responseData["data"]);
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Converts the specified configuration into PDF or image. Writes the result in the specified stream. If no stream is specified, returns the result instead.
     * @param Configuration $configuration The configuration object.
     * @param resource $wh The stream to write into.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return byte[]|void The converted document as binary data. No data is returned if a stream parameter was specified.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function convertAsBinary($config, &$writeHandle = null, &$connectionSettings = null) {
        $this->prepareConfiguration($config);
        $useStream = true;
        if ($writeHandle == NULL || is_array ($writeHandle)) {
            $connectionSettings = $writeHandle;
            $writeHandle = null;
            $useStream = false;
        }
        try {
            $responseData = $this->createConnectionWithData('convert.bin', $connectionSettings, true, false, false, false, $config, $writeHandle);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 400:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_400);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 413:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_413);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 500:
                        throw $this->createServerException($responseData);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            if (!$useStream) {
                return $responseData["data"];
            }
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Converts the specified configuration into PDF or image. This operation responds immediately and does not wait for the conversion to finish. This is especially useful for very large or complex documents where the conversion will take some time.
     * @param Configuration $configuration The configuration object.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return Result A URL to determine the progress of the conversion is contained in the 'Location' response header.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function convertAsync($config, &$connectionSettings = null) {
        $this->prepareConfiguration($config);
        try {
            $responseData = $this->createConnectionWithData('convert/async.json', $connectionSettings, false, false, false, true, $config);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 400:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_400);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 413:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_413);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 500:
                        throw $this->createServerException($responseData);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ASYNC_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return $responseData["documentId"];
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Converts the specified configuration into PDF or image. Writes the result in the specified stream. If no stream is specified, returns the result instead.
     * @param string $documentId The document ID.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return Progress The progress object containing information about the progress of the document conversion. When the conversion is finished, a URL to download the conversion result is contained in the 'Location' response header.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function getProgress($documentId, &$connectionSettings = null) {
        if (is_null($documentId)) {
            throw new ClientException("No conversion was triggered.");
        }
        try {
            $responseData = $this->createConnection("progress/{$documentId}.json", $connectionSettings, false, false, false, false);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 404:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_404);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return json_decode($responseData["data"]);
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Retrieves the asynchronously converted document with the given ID.
     * @param string $documentId The document ID.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return Result The result object containing the converted document and metadata.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function getDocument($documentId, &$connectionSettings = null) {
        if (is_null($documentId)) {
            throw new ClientException("No conversion was triggered.");
        }
        try {
            $responseData = $this->createConnection("document/{$documentId}.json", $connectionSettings, false, false, false, false);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 404:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_404);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return json_decode($responseData["data"]);
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Retrieves the asynchronously converted document with the given ID. Writes the result in the specified stream. If no stream is specified, returns the result instead.
     * @param Configuration $configuration The configuration object.
     * @param resource $wh The stream to write into.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return byte[]|void The converted document as binary data. No data is returned if a stream parameter was specified.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function getDocumentAsBinary($documentId, &$writeHandle = null, &$connectionSettings = null) {
        if (is_null($documentId)) {
            throw new ClientException("No conversion was triggered.");
        }
        $useStream = true;
        if ($writeHandle == NULL || is_array ($writeHandle)) {
            $connectionSettings = $writeHandle;
            $writeHandle = null;
            $useStream = false;
        }
        try {
            $responseData = $this->createConnection("document/{$documentId}.bin", $connectionSettings, true, false, false, false, $writeHandle);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 404:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_404);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            if (!$useStream) {
                return $responseData["data"];
            }
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Retrieves the metadata of the asynchronously converted document with the given ID.
     * @param string $documentId The document ID.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return Result The result object containing the converted document and metadata.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function getDocumentMetadata($documentId, &$connectionSettings = null) {
        if (is_null($documentId)) {
            throw new ClientException("No conversion was triggered.");
        }
        try {
            $responseData = $this->createConnection("document/metadata/{$documentId}.json", $connectionSettings, false, false, false, false);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 404:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_404);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return json_decode($responseData["data"]);
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Converts the specified asset package into PDF or image and returns the generated PDF or image.
     * @param resource $assetPackage The input stream for the Asset Package.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return Result The result object containing the converted document and metadata.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function convertAssetPackage(&$assetPackage, &$connectionSettings = null) {
        try {
            $responseData = $this->createConnectionWithData('convert.json', $connectionSettings, false, true, false, false, $assetPackage);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 400:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_400);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 413:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_413);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 500:
                        throw $this->createServerException($responseData);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return json_decode($responseData["data"]);
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Converts the specified asset package into PDF or image. Writes the result in the specified stream. If no stream is specified, returns the result instead.
     * @param resource $assetPackage The input stream for the Asset Package.
     * @param resource $wh The stream to write into.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return byte[]|void The converted document as binary data. No data is returned if a stream parameter was specified.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function convertAssetPackageAsBinary(&$assetPackage, &$writeHandle = null, &$connectionSettings = null) {
        $useStream = true;
        if ($writeHandle == NULL || is_array ($writeHandle)) {
            $connectionSettings = $writeHandle;
            $writeHandle = null;
            $useStream = false;
        }
        try {
            $responseData = $this->createConnectionWithData('convert.bin', $connectionSettings, true, true, false, false, $assetPackage, $writeHandle);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 400:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_400);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 413:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_413);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 500:
                        throw $this->createServerException($responseData);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            if (!$useStream) {
                return $responseData["data"];
            }
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Converts the specified asset package into PDF or image. This operation responds immediately and does not wait for the conversion to finish. This is especially useful for very large or complex documents where the conversion will take some time.
     * @param resource $assetPackage The input stream for the Asset Package.
     * @param resource $wh The stream to write into.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return documentId A URL to determine the progress of the conversion is contained in the 'Location' response header.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function convertAssetPackageAsync(&$assetPackage, &$connectionSettings = null) {
        try {
            $responseData = $this->createConnectionWithData('convert/async.json', $connectionSettings, false, true, false, true, $assetPackage);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 400:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_400);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 413:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_413);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 500:
                        throw $this->createServerException($responseData);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ASYNC_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return $responseData["documentId"];
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Retrieves the asynchronously converted page of a multi-image with the given ID and page number. Writes the result in the specified stream. If no stream is specified, returns the result instead.
     * @param string $documentId The document ID.
     * @param int $pageNumber The page number.
     * @param resource $wh The stream to write into.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return byte[]|void The converted document as binary data. No data is returned if a stream parameter was specified.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function getDocumentPageAsBinary($documentId, $pageNumber, &$writeHandle = null, &$connectionSettings = null) {
        if (is_null($documentId)) {
            throw new ClientException("No conversion was triggered.");
        }
        $useStream = true;
        if ($writeHandle == NULL || is_array ($writeHandle)) {
            $connectionSettings = $writeHandle;
            $writeHandle = null;
            $useStream = false;
        }
        try {
            $responseData = $this->createConnection("document/{$documentId}/{$pageNumber}.bin", $connectionSettings, true, false, false, false, $writeHandle);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 422:
                        throw $this->createServerException($responseData);
                    case 400:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_400);
                    case 404:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_404);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            if (!$useStream) {
                return $responseData["data"];
            }
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Deletes the asynchronously converted document with the given ID. If the conversion is still running, it gets terminated.
     * @param string $documentId The document ID.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function deleteDocument($documentId, &$connectionSettings = null) {
        if (is_null($documentId)) {
            throw new ClientException("No conversion was triggered.");
        }
        try {
            $responseData = $this->createConnection("document/{$documentId}.json", $connectionSettings, false, false, true, false);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 404:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_404);
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Returns the version of the PDFreactor Web Service that is currently running.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @return Version The version object.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function getVersion(&$connectionSettings = null) {
        try {
            $responseData = $this->createConnection('version.json', $connectionSettings, false, false, false, false);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
            return json_decode($responseData["data"]);
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Checks if the PDFreactor Web Service is available and functional.
     * @param array<string, string> $connectionSettings The connection settings object.
     * @throws PDFreactorWebserviceException If an issue occurred while attempting to connect to the service.
     */
    public function getStatus(&$connectionSettings = null) {
        try {
            $responseData = $this->createConnection('status.json', $connectionSettings, false, false, false, false);
            $status = $responseData["status"];
            if ($status == null || $status <= 0) {
                throw $this->createAnonymousClientException($responseData["error"]);
            }
            if ($responseData["errorMode"]) {
                switch ($status) {
                    case 401:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_401);
                    case 429:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_429);
                    case 503:
                        throw $this->createServerException($responseData, PDFreactor::ERROR_503);
                    default:
                        throw $this->createAnonymousServerException($status);
                }
            }
        } catch (Exception $e) {
            if ($e instanceof PDFreactorWebserviceException) {
                throw $e;
            }
            throw $this->createAnonymousClientException($e->message, $e);
        }
    }
    /**
     * Returns the URL where the document with the given ID can be accessed.
     * @param string $documentId The document ID.
     * @param int $pageNumber The page number.
     * @return string The document URL.
     */
    function getDocumentUrl($documentId, $pageNumber = null) {
        if (!is_null($documentId)) {
            if (!is_null($pageNumber)) {
                return "{$this->url}/document/{$documentId}/{$pageNumber}";
            }
            return "{$this->url}/document/{$documentId}";
        }
        return null;
    }
    /**
     * Returns the URL where the progress of the document with the given ID can be accessed.
     * @param string $documentId The document ID.
     * @return string The progress URL.
     */
    function getProgressUrl($documentId) {
        if (!is_null($documentId)) {
            return "{$this->url}/progress/{$documentId}";
        }
        return null;
    }
    public const VERSION = 12; //"12.0.0";
    /**
     * The API key. Only required if the PDFreactor Web Service is so configured that only clients with a valid API key can access it.
     */
    public function __get($name) {
        if ($name == "apiKey") {
            return $this->apiKey;
        }
    }

    private function prepareConfiguration(&$config) {
        if (!is_null($config)) {
            $config['clientName'] = "PHP";
            $config['clientVersion'] = PDFreactor::VERSION;
        }
    }
    private function createConnection($path, &$connectionSettings = null, $textError = false, $zip = false, $delete = false, $async = false, &$wh = null) {
        $emptyPayload = [];
        return $this->createConnectionWithData($path, $connectionSettings, $textError, $zip, $delete, $async, $emptyPayload, $wh);
    }
    private function createConnectionWithData($path, &$connectionSettings = null, $textError = false, $zip = false, $delete = false, $async = false, &$payload = null, &$wh = null) {
        $url = $this->url . "/" . $path;
        $input = !!$payload;
        $useStream = $wh != null;

        if (!is_null($this->apiKey)) {
            $url .= "?apiKey=" . $this->apiKey;
        }
        $headers = [];
        $headers[] = "User-Agent: PDFreactor PHP API v" . PDFreactor::VERSION;
        $headers[] = "X-RO-User-Agent: PDFreactor PHP API v" . PDFreactor::VERSION;
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== "content-type" && $lcName !== "content-length" && $lcName !== "range") {
                    $headers[] = $name . ": " . $value;
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . "=" . $value . "; ";
            }
        }
        if ($input) {
            $headers[] = "Content-Type: " . ($zip ? "application/zip" : "application/json");
        }
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headers[] = "Cookie: " . substr($cookieStr, 0, -2);
        }
        $curl = curl_init($url);
        $responseHeaders = [];

        if ($input) {
            if ($zip) {
                curl_setopt($curl, CURLOPT_PUT, 1);
                curl_setopt($curl, CURLOPT_INFILE, $payload);
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $delete ? "DELETE" : ($input ? "POST" : "GET"));
        curl_setopt($curl, CURLOPT_TIMEOUT, 300);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) { // ignore invalid headers
                return $len;
            }
            $responseHeaders[] = [ trim($header[0]), trim($header[1]) ];
            return $len;
        });
        $error = null;
        $result = null;
        $errorMode = true;
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, function($curl, $data) use(&$wh, &$useStream, &$result) {
            if ($wh != null && $useStream) {
                fwrite($wh, $data);
            } else {
                $result .= $data;
            }
            return strlen($data);
        });
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);
        $status = $info["http_code"];
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        $errorId = null;
        $documentId = null;
        if ($errorMode) {
            foreach ($responseHeaders as $header) {
                $headerName = $header[0];
                if ($headerName == "X-RO-Error-ID") {
                    $errorId = $header[1];
                }
            }
        }
        if ($async) {
            foreach ($responseHeaders as $header) {
                $headerName = $header[0];
                if (strtolower($headerName) == "location") {
                    $documentId = trim(substr($header[1], strrpos($header[1], "/") + 1));
                }
                if (strtolower($headerName) == "set-cookie") {
                    $keepDocument = false;
                    if (isset($config->{'keepDocument'})) {
                        $keepDocument = $config->{'keepDocument'};
                    }
                    if (isset($connectionSettings)) {
                        if (empty($connectionSettings['cookies'])) {
                            $connectionSettings['cookies'] = [];
                        }
                        $pos = stripos($header[1], ';', 0);
                        $headerValue = $header[1];
                        if ($pos > 0) {
                            $headerValue = substr($headerValue, 0, $pos);
                        }
                        $cookie = explode('=', $headerValue);
                        $cookieName = trim($cookie[0]);
                        $cookieValue = trim($cookie[1]);
                        $connectionSettings['cookies'][$cookieName] = $cookieValue;
                    }
                }
            }
        }
        curl_close($curl);
        if ($errorMode && empty($error)) {
            if ($status == NULL || $status <= 0) {
                $error = "Could not connect to server.";
            } else if ($textError && !empty($result)) {
                $error = $result;
            }
        }
        return [
            "errorMode" => $errorMode,
            "error" => $error,
            "errorId" => $errorId,
            "data" => $error == NULL || !$textError ? $result : NULL,
            "status" => $status,
            "documentId" => $documentId,
            "info" => $info
        ];
    }
    private function createServerException(&$responseData, $clientMessage = NULL) {
        $serverMessage = NULL;
        $result = NULL;
        $errorId = "";

        if ($responseData != NULL) {
            $serverMessage = $responseData["error"];
            $result = $responseData["data"] != null ? json_decode($responseData["data"]) : NULL;
            $errorId = $responseData["errorId"];
        }

        switch ($errorId) {
            case "asyncUnavailable":
                return new AsyncUnavailableException($errorId, $clientMessage, $serverMessage, $result);
            case "badRequest":
                return new BadRequestException($errorId, $clientMessage, $serverMessage, $result);
            case "conversionAborted":
                return new ConversionAbortedException($errorId, $clientMessage, $serverMessage, $result);
            case "conversionFailure":
                return new ConversionFailureException($errorId, $clientMessage, $serverMessage, $result);
            case "documentNotFound":
                return new DocumentNotFoundException($errorId, $clientMessage, $serverMessage, $result);
            case "invalidClient":
                return new InvalidClientException($errorId, $clientMessage, $serverMessage, $result);
            case "invalidConfiguration":
                return new InvalidConfigurationException($errorId, $clientMessage, $serverMessage, $result);
            case "noConfiguration":
                return new NoConfigurationException($errorId, $clientMessage, $serverMessage, $result);
            case "noInputDocument":
                return new NoInputDocumentException($errorId, $clientMessage, $serverMessage, $result);
            case "notAcceptable":
                return new NotAcceptableException($errorId, $clientMessage, $serverMessage, $result);
            case "serviceUnavailable":
                return new ServiceUnavailableException($errorId, $clientMessage, $serverMessage, $result);
            case "unauthorized":
                return new UnauthorizedException($errorId, $clientMessage, $serverMessage, $result);
            case "unprocessableConfiguration":
                return new UnprocessableConfigurationException($errorId, $clientMessage, $serverMessage, $result);
            case "unprocessableInput":
                return new UnprocessableInputException($errorId, $clientMessage, $serverMessage, $result);
            default:
                return new ServerException($errorId, $serverMessage, $result);
        }
    }
    private function createAnonymousServerException($status) {
        return new ServerException(NULL, "PDFreactor Web Service error (status {$status}).");
    }
    private function createAnonymousClientException($message, $exception = null) {
        return new UnreachableServiceException("Error connecting to PDFreactor Web Service at {$this->url}. Please make sure the PDFreactor Web Service is installed and running (Error: {$message})", $exception);
    }
    private function createUnknownException($exception) {
        return new PDFreactorWebserviceException("Unknown PDFreactor Web Service error (Error: {$exception->message})", $exception);
    }
}

/**
 * This type of exception is thrown by the PDFreactor Web Service client. It has several sub classes, all indicating different issues. To react to specific problems, it is recommended to catch appropriate sub class exceptions.
 * @see ClientException
 * @see ServerException
 */
class PDFreactorWebserviceException extends \Exception {
    var $result;
    function __construct($message) {
        parent::__construct($message == null ? "Unknown PDFreactor Web Service error" : $message);
    }
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}
/**
 * This type of exception is produced by the PDFreactor Web Service and indicates that the conversion could not be processed for some reason. Exceptions of this class mean that the PDFreactor Web Service is running. Please note that the client requires the 'X-RO-Error-ID' HTTP header to be present to convert the exception in the appropriate type. If that header is missing, exceptions will have this generic type instead.
 * @see AsyncUnavailableException
 * @see BadRequestException
 * @see ConversionAbortedException
 * @see ConversionFailureException
 * @see DocumentNotFoundException
 * @see InvalidClientException
 * @see InvalidConfigurationException
 * @see NoConfigurationException
 * @see NoInputDocumentException
 * @see NotAcceptableException
 * @see ServiceUnavailableException
 * @see UnauthorizedException
 * @see UnprocessableConfigurationException
 * @see UnprocessableInputException
 */
class ServerException extends PDFreactorWebserviceException {
    var $result;
    var $errorId;
    function __construct($errorId = null, $clientMessage = null, $serverMessage = null, $result = null) {
        $this->result = $result;
        $this->errorId = $errorId;
        $messages = [];
        if ($serverMessage == NULL && $result != NULL) {
            $serverMessage = $result->error;
        }
        if ($clientMessage != NULL) {
            array_push($messages, $clientMessage);
        }
        if ($serverMessage != NULL) {
            array_push($messages, $serverMessage);
        }
        $message = implode(" ", $messages);
        parent::__construct($message == null ? "Unknown PDFreactor Web Service error" : $message);
    }
    public function getResult() {
        return $this->result;
    }
}
/**
 * This type of exception is produced by the client and indicates that a connection to the PDFreactor Web Service could not be established. Exceptions of this class do not necessarily indicate a problem with the PDFreactor Web Service, only that it could not be reached. This could have various reasons, including a non-functioning PDFreactor Web Service, a blocking firewall or an incorrectly configured service URL.
 * @see ClientTimeoutException
 * @see InvalidServiceException
 * @see UnreachableServiceException
 */
class ClientException extends PDFreactorWebserviceException {
    var $cause;
    function __construct($message, $cause = null) {
        $this->cause = $cause;
        parent::__construct($message);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * Asynchronous conversions are not available in this PDFreactor Web Service.
 */
class AsyncUnavailableException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The page number you specified is either below 0 or exceeds the document's total number of pages.
 */
class BadRequestException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The supplied configuration is valid, however the conversion could not be completed for some reason. See the error message for details.
 */
class ConversionAbortedException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The configuration could not be processed and should be re-checked.
 */
class ConversionFailureException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * Conversion does not exist.
 */
class DocumentNotFoundException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The version of the client that was used is outdated and no longer supported. This is only available for the PDFreactor REST clients.
 */
class InvalidClientException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The supplied configuration was not valid for some reason. See the error message for details.
 */
class InvalidConfigurationException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * No configuration was supplied to the operation.
 */
class NoConfigurationException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * No input document was specified in the configuration.
 */
class NoInputDocumentException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The server could not produce a result with a media type that matches the client's request. The configuration or Accept header should be adjusted accordingly.
 */
class NotAcceptableException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The PDFreactor Web Service is running and reachable, but not in a state to perform the requested operation.
 */
class ServiceUnavailableException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The client failed an authorization check, e.g. because a supplied API key was invalid.
 */
class UnauthorizedException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The supplied configuration was accepted by PDFreactor but could not be converted for some reason. See the error message for details.
 */
class UnprocessableConfigurationException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The supplied input data was accepted by PDFreactur but could not be processed for some reason. See the error message for details.
 */
class UnprocessableInputException extends ServerException {
    function __construct($errorId, $clientMessage, $serverMessage, $result) {
        parent::__construct($errorId, $clientMessage, $serverMessage, $result);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The PDFreactor Web Service could not be reached.
 */
class UnreachableServiceException extends ClientException {
    function __construct($message, $cause = null) {
        parent::__construct($message, $cause);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * A response was received but it could not be identified as a response from the PDFreactor Web Service.
 */
class InvalidServiceException extends ClientException {
    function __construct($message, $cause = null) {
        parent::__construct($message, $cause);
    }
}
/**
 * This exception is thrown under the following circumstances:
 * The request to the PDFreactor Web Service timed out. This usually occurs during synchronous conversions. Increasing the timeout or switching to asynchronous conversions might resolve this.
 */
class ClientTimeoutException extends ClientException {
    function __construct($message, $cause = null) {
        parent::__construct($message, $cause);
    }
}

/**
 * <p>An enum containing callback type constants.</p>
 */
abstract class CallbackType {
    /**
     * <p>This callback is called when the conversion is finished.</p>
     * <ul>
     * <li><em>Allowed content types:</em>
     *  {@see ContentType::JSON}
     * ,
     *  {@see ContentType::XML}
     * ,
     *  {@see ContentType::TEXT}
     * .</li>
     * <li><em>Payload model:</em>
     * <code>Result</code>. The object returned will only contain the metadata, not the document data.
     * The document has to be retrieved actively from the server. If the content type is
     *  {@see ContentType::TEXT}
     * , only the document ID will be posted.</li>
     * </ul>
     * @var string
     */
    public const FINISH = "FINISH";
    /**
     * <p>This callback is called regularly to inform on the progress of the conversion.</p>
     * <ul>
     * <li><em>Allowed content types:</em>
     *  {@see ContentType::JSON}
     * ,
     *  {@see ContentType::XML}
     * ,
     *  {@see ContentType::TEXT}
     * .</li>
     * <li><em>Payload model:</em>
     * <code>Progress</code>. If the content type is
     *  {@see ContentType::TEXT}
     * , only the progress percentage will be posted.</li>
     * <li><em>Interval property applies.</em></li>
     * </ul>
     * @var string
     */
    public const PROGRESS = "PROGRESS";
    /**
     * <p>This callback is called when the conversion is started.</p>
     * <ul>
     * <li><em>Allowed content types:</em>
     *  {@see ContentType::JSON}
     * ,
     *  {@see ContentType::XML}
     * ,
     *  {@see ContentType::TEXT}
     * .</li>
     * <li><em>Payload model:</em>
     * <code>Info</code>. If the content type is
     *  {@see ContentType::TEXT}
     * , only the document ID will be posted.</li>
     * </ul>
     * @var string
     */
    public const START = "START";
}
/**
 * <p>An enum containing cleanup constants.</p>
 */
abstract class Cleanup {
    /**
     * <p>Indicates that the CyberNeko HTML parser will be used to perform a
     * cleanup when loading a non-well-formed document.</p>
     * @var string
     */
    public const CYBERNEKO = "CYBERNEKO";
    /**
     * <p>Indicates that JTidy will be used to perform a cleanup when loading a
     * non-well-formed document.</p>
     * @var string
     */
    public const JTIDY = "JTIDY";
    /**
     * <p>Indicates that no cleanup will be performed when loading a document. If the
     * loaded document is not well-formed, an exception will be thrown.</p>
     * @var string
     */
    public const NONE = "NONE";
    /**
     * <p>Indicates that tagsoup will be used to perform a
     * cleanup when loading a non-well-formed document.</p>
     * @var string
     */
    public const TAGSOUP = "TAGSOUP";
}
/**
 * <p>An enum containing color space constants.</p>
 */
abstract class ColorSpace {
    /**
     * <p>The color space CMYK.</p>
     * @var string
     */
    public const CMYK = "CMYK";
    /**
     * <p>The color space RGB.</p>
     * @var string
     */
    public const RGB = "RGB";
}
/**
 * <p>An enum containing conformance constants.</p>
 */
abstract class Conformance {
    /**
     * <p>PDF with no additional restrictions (default)</p>
     * @var string
     */
    public const PDF = "PDF";
    /**
     * <p>PDF/A-1a (ISO 19005-1:2005 Level A)</p>
     * @var string
     */
    public const PDFA1A = "PDFA1A";
    /**
     * <p>PDF/A-1a + PDF/UA-1 (ISO 19005-1:2005 Level A + ISO 14289-1:2014)</p>
     * @var string
     */
    public const PDFA1A_PDFUA1 = "PDFA1A_PDFUA1";
    /**
     * <p>PDF/A-1b (ISO 19005-1:2005 Level B)</p>
     * @var string
     */
    public const PDFA1B = "PDFA1B";
    /**
     * <p>PDF/A-2a (ISO 19005-2:2011 Level A)</p>
     * @var string
     */
    public const PDFA2A = "PDFA2A";
    /**
     * <p>PDF/A-2a + PDF/UA-1 (ISO 19005-2:2011 Level A + ISO 14289-1:2014)</p>
     * @var string
     */
    public const PDFA2A_PDFUA1 = "PDFA2A_PDFUA1";
    /**
     * <p>PDF/A-2b (ISO 19005-2:2011 Level B)</p>
     * @var string
     */
    public const PDFA2B = "PDFA2B";
    /**
     * <p>PDF/A-2u (ISO 19005-2:2011 Level U)</p>
     * @var string
     */
    public const PDFA2U = "PDFA2U";
    /**
     * <p>PDF/A-3a (ISO 19005-3:2012 Level A)</p>
     * @var string
     */
    public const PDFA3A = "PDFA3A";
    /**
     * <p>PDF/A-3a + PDF/UA-1 (ISO 19005-3:2012 Level A + ISO 14289-1:2014)</p>
     * @var string
     */
    public const PDFA3A_PDFUA1 = "PDFA3A_PDFUA1";
    /**
     * <p>PDF/A-3b (ISO 19005-3:2012 Level B)</p>
     * @var string
     */
    public const PDFA3B = "PDFA3B";
    /**
     * <p>PDF/A-3u (ISO 19005-3:2012 Level U)</p>
     * @var string
     */
    public const PDFA3U = "PDFA3U";
    /**
     * <p>PDF/UA-1 (ISO 14289-1:2014)</p>
     * @var string
     */
    public const PDFUA1 = "PDFUA1";
    /**
     * <p>PDF/X-1a:2001 (ISO 15930-1:2001)</p>
     * @var string
     */
    public const PDFX1A_2001 = "PDFX1A_2001";
    /**
     * <p>PDF/X-1a:2003 (ISO 15930-4:2003)</p>
     * @var string
     */
    public const PDFX1A_2003 = "PDFX1A_2003";
    /**
     * <p>PDF/X-3:2002 (ISO 15930-3:2002)</p>
     * @var string
     */
    public const PDFX3_2002 = "PDFX3_2002";
    /**
     * <p>PDF/X-3:2003 (ISO 15930-6:2003)</p>
     * @var string
     */
    public const PDFX3_2003 = "PDFX3_2003";
    /**
     * <p>PDF/X-4 (ISO 15930-7:2008)</p>
     * @var string
     */
    public const PDFX4 = "PDFX4";
    /**
     * <p>PDF/X-4p (ISO 15930-7:2008)</p>
     * @var string
     */
    public const PDFX4P = "PDFX4P";
}
/**
 * <p>An enum containing content type constants.</p>
 */
abstract class ContentType {
    /**
     * <p>Content type BINARY, corresponds with "application/octet-stream" MIME type.</p>
     * @var string
     */
    public const BINARY = "BINARY";
    /**
     * <p>Content type BMP, corresponds with "image/bmp" MIME type.</p>
     * @var string
     */
    public const BMP = "BMP";
    /**
     * <p>Content type GIF, corresponds with "image/gif" MIME type.</p>
     * @var string
     */
    public const GIF = "GIF";
    /**
     * <p>Content type HTML, corresponds with "text/html" MIME type.</p>
     * @var string
     */
    public const HTML = "HTML";
    /**
     * <p>Content type JPEG, corresponds with "image/jpeg" MIME type.</p>
     * @var string
     */
    public const JPEG = "JPEG";
    /**
     * <p>Content type JSON, corresponds with "application/json" MIME type.</p>
     * @var string
     */
    public const JSON = "JSON";
    /**
     * <p>Content type NONE, i.e. no content.</p>
     * @var string
     */
    public const NONE = "NONE";
    /**
     * <p>Content type PDF, corresponds with "application/pdf" MIME type.</p>
     * @var string
     */
    public const PDF = "PDF";
    /**
     * <p>Content type PNG, corresponds with "image/png" MIME type.</p>
     * @var string
     */
    public const PNG = "PNG";
    /**
     * <p>Content type TEXT, corresponds with "text/plain" MIME type.</p>
     * @var string
     */
    public const TEXT = "TEXT";
    /**
     * <p>Content type TIFF, corresponds with "image/tiff" MIME type.</p>
     * @var string
     */
    public const TIFF = "TIFF";
    /**
     * <p>Content type XML, corresponds with "application/xml" MIME type.</p>
     * @var string
     */
    public const XML = "XML";
}
/**
 * <p>An enum containing cookie policy constants.</p>
 */
abstract class CookiePolicy {
    /**
     * <p>Disables cookie handling entirely. Cookies specified in the API are still sent, but server cookies are rejected.</p>
     * @var string
     */
    public const DISABLED = "DISABLED";
    /**
     * <p>A standard-compliant cookie policy that ignores date issues. This is the default value.</p>
     * @var string
     */
    public const RELAXED = "RELAXED";
    /**
     * <p>A strict standard-compliant cookie policy.</p>
     * @var string
     */
    public const STRICT = "STRICT";
}
/**
 * <p>An enum containing CSS property support mode constants.</p>
 */
abstract class CssPropertySupport {
    /**
     * <p>Indicates that all style declarations are considered valid
     * disregarding the possibility of improper rendering.</p>
     * <p>Valid values may be overwritten by invalid style declarations.</p>
     * @var string
     */
    public const ALL = "ALL";
    /**
     * <p>Indicates that all values set in style declarations will be
     * validated as long as PDFreactor supports the corresponding
     * property.</p>
     * <p>Style declarations for properties not supported
     * by PDFreactor are taken as invalid.</p>
     * @var string
     */
    public const HTML = "HTML";
    /**
     * <p>Indicates that all values set in style declarations will be
     * validated as long as PDFreactor supports the corresponding
     * property.</p>
     * <p>Style declarations for properties not supported
     * by PDFreactor but by third party products are taken as valid.</p>
     * @var string
     */
    public const HTML_THIRD_PARTY = "HTML_THIRD_PARTY";
    /**
     * <p>Indicates that all values set in style declarations will be
     * taken as valid if a third party product supports the corresponding
     * property.</p>
     * <p>Style declarations for properties not supported by
     * any third party product but supported by PDFreactor will be validated.</p>
     * @var string
     */
    public const HTML_THIRD_PARTY_LENIENT = "HTML_THIRD_PARTY_LENIENT";
}
/**
 * <p>An enum containing document type constants.</p>
 */
abstract class Doctype {
    /**
     * <p>Indicates that the document type will be detected automatically.
     * When the document has a file extension, it is used to determine whether the document is
     *  {@see Doctype::HTML5}
     * or
     *  {@see Doctype::XHTML}
     * . If there is no file extension or it is unknown, then the document content itself is searched
     * for an XML declaration, a doctype preamble and the root element.</p>
     * @var string
     */
    public const AUTODETECT = "AUTODETECT";
    /**
     * <p>Indicates that the document type will be set to HTML5.
     * The HTML default style sheet is used and the document is loaded regarding style
     * elements, style attributes and link stylesheets.</p>
     * @var string
     */
    public const HTML5 = "HTML5";
    /**
     * <p>Indicates that the document type will be set to XHTML. The HTML default
     * style sheet is used and the document is loaded regarding style
     * elements, style attributes and link stylesheets.</p>
     * @var string
     */
    public const XHTML = "XHTML";
    /**
     * <p>Indicates that the document type will be set to generic XML. No default
     * style sheet is used and the document is loaded as is without regards
     * to style elements or attributes.</p>
     * @var string
     */
    public const XML = "XML";
}
/**
 * <p>An enum containing encryption constants.</p>
 */
abstract class Encryption {
    /**
     * <p>Indicates that the document will be encrypted using AES 128 bit encryption.
     * @var string
     */
    public const AES_128 = "AES_128";
    /**
     * <p>Indicates that the document will be encrypted using AES 256 bit encryption.
     * @var string
     */
    public const AES_256 = "AES_256";
    /**
     * <p>Indicates that the document will not be encrypted. If encryption is disabled
     * then no user password and no owner password can be used.</p>
     * @var string
     */
    public const NONE = "NONE";
    /**
     * <p>Indicates that the document will be encrypted using RC4 128 bit encryption.
     * @var string
     */
    public const RC4_128 = "RC4_128";
    /**
     * <p>Indicates that the document will be encrypted using RC4 40 bit encryption.</p>
     * @var string
     */
    public const RC4_40 = "RC4_40";
    /**
     * <p>Deprecated as of PDFreactor 12. Use
     *  {@see Encryption::RC4_128}
     * instead.</p>
     * @var string
     */
    public const TYPE_128 = "TYPE_128";
    /**
     * <p>Deprecated as of PDFreactor 12. Use
     *  {@see Encryption::RC4_40}
     * instead.</p>
     * @var string
     */
    public const TYPE_40 = "TYPE_40";
}
/**
 * <p>An enum containing error policies.</p>
 */
abstract class ErrorPolicy {
    /**
     * <p>Whether an exception should be thrown when the PDF's conformance was not validated
     * even though
     *  {@see Configuration::setValidateConformance(Boolean)}
     * was enabled.
     * Now exceptions will be thrown if the validation module is not available or the conformance format is
     * not fully supported for validation.</p>
     * <p>Use this policy only if you exclusively convert documents where validation is supported
     * and strictly required.</p>
     * @var string
     */
    public const CONFORMANCE_VALIDATION_UNAVAILABLE = "CONFORMANCE_VALIDATION_UNAVAILABLE";
    /**
     * <p>Whether exceptions occurring when trying to merge invalid documents (e.g.
     * encrypted documents for which no owner password or an invalid password was
     * supplied) should be ignored.</p>
     * <p>Use this policy if the conversion should proceed even if one or more
     * documents that should be merged are invalid. They will be omitted from
     * the final PDF.</p>
     * @var string
     */
    public const IGNORE_INVALID_MERGE_DOCUMENTS_EXCEPTION = "IGNORE_INVALID_MERGE_DOCUMENTS_EXCEPTION";
    /**
     * <p>Whether an exception should be thrown when no legal full license key is set.
     * This allows to programmatically ensure that documents are not altered due to license issues.</p>
     * @var string
     */
    public const LICENSE = "LICENSE";
    /**
     * <p>Whether an exception should be thrown when resources could not be loaded.</p>
     * @var string
     */
    public const MISSING_RESOURCE = "MISSING_RESOURCE";
    /**
     * <p>Whether an exception should be thrown when there are uncaught exceptions
     * in the input document JavaScript, including syntax error.</p>
     * @var string
     */
    public const UNCAUGHT_JAVASCRIPT_EXCEPTION = "UNCAUGHT_JAVASCRIPT_EXCEPTION";
    /**
     * <p>Whether an exception should be thrown when an event of level
     *  {@see LogLevel::WARN}
     * is logged.</p>
     * @var string
     */
    public const WARN_EVENT = "WARN_EVENT";
}
/**
 * <p>An enum containing constants for logging exceeding content against.</p>
 */
abstract class ExceedingContentAgainst {
    /**
     * <p>Do not log exceeding content.</p>
     * @var string
     */
    public const NONE = "NONE";
    /**
     * <p>Log content exceeding the edges of its page.</p>
     * @var string
     */
    public const PAGE_BORDERS = "PAGE_BORDERS";
    /**
     * <p>Log content exceeding its page content area (overlaps the page margin).</p>
     * @var string
     */
    public const PAGE_CONTENT = "PAGE_CONTENT";
    /**
     * <p>Log content exceeding its container.</p>
     * @var string
     */
    public const PARENT = "PARENT";
}
/**
 * <p>An enum containing constants for analyzing exceeding content.</p>
 */
abstract class ExceedingContentAnalyze {
    /**
     * <p>Log exceeding content.</p>
     * @var string
     */
    public const CONTENT = "CONTENT";
    /**
     * <p>Log exceeding content and all boxes.</p>
     * @var string
     */
    public const CONTENT_AND_BOXES = "CONTENT_AND_BOXES";
    /**
     * <p>Log exceeding content and boxes without absolute positioning.</p>
     * @var string
     */
    public const CONTENT_AND_STATIC_BOXES = "CONTENT_AND_STATIC_BOXES";
    /**
     * <p>Do not log exceeding content.</p>
     * @var string
     */
    public const NONE = "NONE";
}
/**
 * <p>An enum containing HTTP authentication scheme constants.</p>
 */
abstract class HttpAuthScheme {
    /**
     * <p>This constant indicates that the credentials are to be used for any authentication scheme. This is the default value.</p>
     * @var string
     */
    public const ANY = "ANY";
    /**
     * <p>BASIC authentication.</p>
     * @var string
     */
    public const BASIC = "BASIC";
    /**
     * <p>DIGEST authentication.</p>
     * @var string
     */
    public const DIGEST = "DIGEST";
    /**
     * <p>Kerberos authentication.</p>
     * @var string
     */
    public const KERBEROS = "KERBEROS";
    /**
     * <p>Windows NTLM authentication.</p>
     * @var string
     */
    public const NTLM = "NTLM";
    /**
     * <p>Simple and Protected GSSAPI Negotiation Mechanism.</p>
     * @var string
     */
    public const SPNEGO = "SPNEGO";
}
/**
 * <p>An enum containing HTTP protocol constants.</p>
 */
abstract class HttpProtocol {
    /**
     * <p>This constant indicates that the credentials are to be used for any HTTP protocol.</p>
     * @var string
     */
    public const ANY = "ANY";
    /**
     * <p>HTTP only.</p>
     * @var string
     */
    public const HTTP = "HTTP";
    /**
     * <p>HTTPS only.</p>
     * @var string
     */
    public const HTTPS = "HTTPS";
}
/**
 * <p>Deprecated as of PDFreactor 12. Use
 *  {@see SecuritySettings::setTrustAllConnectionCertificates(Boolean)}
 * instead.</p>
 */
abstract class HttpsMode {
    /**
     * <p>Indicates lenient HTTPS behavior. This means that many certificate issues are ignored.</p>
     * @var string
     */
    public const LENIENT = "LENIENT";
    /**
     * <p>Indicates strict HTTPS behavior. This matches the default behavior of Java.</p>
     * @var string
     */
    public const STRICT = "STRICT";
}
/**
 * <p>An enum containing JavaScript debug mode constants.</p>
 */
abstract class JavaScriptDebugMode {
    /**
     * <p>Indicates that all exceptions thrown during JavaScript processing are logged
     * in addition to the effects of POSITIONS.</p>
     * @var string
     */
    public const EXCEPTIONS = "EXCEPTIONS";
    /**
     * <p>Indicates that all JavaScript functions entered or exited are logged
     * in addition to the effects of POSITIONS and EXCEPTIONS.</p>
     * @var string
     */
    public const FUNCTIONS = "FUNCTIONS";
    /**
     * <p>Indicates that every line of executed JavaScript is logged
     * in addition to the effects of POSITIONS, EXCEPTIONS and FUNCTIONS.</p>
     * @var string
     */
    public const LINES = "LINES";
    /**
     * <p>Indicates that debugging is disabled.</p>
     * @var string
     */
    public const NONE = "NONE";
    /**
     * <p>Indicates that the filenames and line numbers that caused output
     * (e.g. via console.log) are logged.</p>
     * @var string
     */
    public const POSITIONS = "POSITIONS";
}
/**
 * <p>An enum containing JavaScript engines.</p>
 */
abstract class JavaScriptEngine {
    /**
     * <p>GraalVM JavaScript engine</p>
     * @var string
     */
    public const GRAALJS = "GRAALJS";
    /**
     * <p>Rhino JavaScript engine</p>
     * @var string
     */
    public const RHINO = "RHINO";
}
/**
 * <p>An enum containing keystore type constants.</p>
 */
abstract class KeystoreType {
    /**
     * <p>Keystore type "jks".</p>
     * @var string
     */
    public const JKS = "JKS";
    /**
     * <p>Keystore type "pkcs12".</p>
     * @var string
     */
    public const PKCS12 = "PKCS12";
}
/**
 * <p>An enum containing log level constants.</p>
 */
abstract class LogLevel {
    /**
     * <p>Indicates that debug, info, warn and fatal log events will be logged.</p>
     * @var string
     */
    public const DEBUG = "DEBUG";
    /**
     * <p>Indicates that only error log events will be logged.</p>
     * @var string
     */
    public const ERROR = "ERROR";
    /**
     * <p>Deprecated as of PDFreactor 12. Use
     *  {@see LogLevel::ERROR}
     * instead.</p>
     * @var string
     */
    public const FATAL = "FATAL";
    /**
     * <p>Indicates that info, warn and fatal log events will be logged.</p>
     * @var string
     */
    public const INFO = "INFO";
    /**
     * <p>Indicates that no log events will be logged.</p>
     * @var string
     */
    public const NONE = "NONE";
    /**
     * <p>Deprecated as of PDFreactor 12. Use
     *  {@see LogLevel::TRACE}
     * instead.</p>
     * @var string
     */
    public const PERFORMANCE = "PERFORMANCE";
    /**
     * <p>Indicates that all log events will be logged.</p>
     * @var string
     */
    public const TRACE = "TRACE";
    /**
     * <p>Indicates that warn and fatal log events will be logged.</p>
     * @var string
     */
    public const WARN = "WARN";
}
/**
 * <p>An enum containing media feature constants.</p>
 */
abstract class MediaFeature {
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing whether any available input mechanism allows the user to hover over elements.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const ANY_HOVER = "ANY_HOVER";
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing whether any available input mechanism is a pointing device, and if so, how accurate is it.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const ANY_POINTER = "ANY_POINTER";
    /**
     * <p>CSS 3 Media Feature describing the aspect ratio of the page content.</p>
     * <p>By default, this value is computed using the values of
     *  {@see MediaFeature::WIDTH}
     * and
     *  {@see MediaFeature::HEIGHT}
     * . Setting a specific value does override the computed
     * value.</p>
     * @var string
     */
    public const ASPECT_RATIO = "ASPECT_RATIO";
    /**
     * <p>CSS 3 Media Feature describing the number of bits per color component.</p>
     * <p>Default value is 8, except if the output is forced to be grayscale, in which case it is 0.</p>
     * @var string
     */
    public const COLOR = "COLOR";
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing the approximate range of colors that are supported by the UA and output device.</p>
     * <p>The default value is "srgb".</p>
     * @var string
     */
    public const COLOR_GAMUT = "COLOR_GAMUT";
    /**
     * <p>CSS 3 Media Feature describing the number of entries in the color lookup table.</p>
     * <p>Default value is 0, except if the output format is "gif" in which case it is 256.</p>
     * @var string
     */
    public const COLOR_INDEX = "COLOR_INDEX";
    /**
     * <p>CSS 3 Media Feature describing the aspect ratio of the page.</p>
     * <p>By default, this value is computed using the values of
     *  {@see MediaFeature::DEVICE_WIDTH}
     * and
     *  {@see MediaFeature::DEVICE_HEIGHT}
     * . Setting a specific value does override
     * the computed value.</p>
     * @var string
     */
    public const DEVICE_ASPECT_RATIO = "DEVICE_ASPECT_RATIO";
    /**
     * <p>CSS 3 Media Feature describing the height of the page.</p>
     * <p>The default height is that of a DIN A4 page (297mm).</p>
     * @var string
     */
    public const DEVICE_HEIGHT = "DEVICE_HEIGHT";
    /**
     * <p>CSS 3 Media Feature describing the width of the page.</p>
     * <p>The default width is that of a DIN A4 page (210mm).</p>
     * @var string
     */
    public const DEVICE_WIDTH = "DEVICE_WIDTH";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) representing how a web application is being presented within the context of an OS.</p>
     * <p>The default value is "fullscreen".</p>
     * @var string
     */
    public const DISPLAY_MODE = "DISPLAY_MODE";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) representing the combination of max brightness, color depth, and contrast ratio that are supported by the user agent and output device.</p>
     * <p>The default value is "standard".</p>
     * @var string
     */
    public const DYNAMIC_RANGE = "DYNAMIC_RANGE";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) that is used to query the characteristics of the user's display so the author can adjust the style of the document.</p>
     * <p>The default value is "opaque".</p>
     * @var string
     */
    public const ENVIRONMENT_BLENDING = "ENVIRONMENT_BLENDING";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) indicates whether the user-agent enforces a limited color palette.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const FORCED_COLORS = "FORCED_COLORS";
    /**
     * <p>CSS 3 Media Feature defining whether the output is grid-based.</p>
     * <p>Default value 0, as PDFs are not grid-based.</p>
     * @var string
     */
    public const GRID = "GRID";
    /**
     * <p>CSS 3 Media Feature height of page content.</p>
     * <p>The default height is that of a DIN A4 page with 2cm margin (257mm).</p>
     * @var string
     */
    public const HEIGHT = "HEIGHT";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) that describes the number of logical segments of the viewport in the horizontal direction.</p>
     * <p>The default value is "1".</p>
     * @var string
     */
    public const HORIZONTAL_VIEWPORT_SEGMENTS = "HORIZONTAL_VIEWPORT_SEGMENTS";
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing whether the primary input mechanism allows the user to hover over elements.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const HOVER = "HOVER";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) indicating whether the content is displayed normally, or whether colors have been inverted.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const INVERTED_COLORS = "INVERTED_COLORS";
    /**
     * <p>CSS 3 Media Feature describing the number of bits per pixel in a monochrome frame buffer.</p>
     * <p>Default value is 0, if the output format is not monochrome.</p>
     * @var string
     */
    public const MONOCHROME = "MONOCHROME";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) allowing authors to know whether the user agent is providing obviously discoverable navigation controls as part of its user interface.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const NAV_CONTROLS = "NAV_CONTROLS";
    /**
     * <p>CSS 3 Media Feature describing the page orientation.</p>
     * <p>By default, this value is computed using the values of
     *  {@see MediaFeature::WIDTH}
     * and
     *  {@see MediaFeature::HEIGHT}
     * .
     * Setting a specific value does override the computed value.</p>
     * <p>Valid values are "portrait" or "landscape".</p>
     * @var string
     */
    public const ORIENTATION = "ORIENTATION";
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing the behavior of the device when content overflows the initial containing block in the block axis.</p>
     * <p>The default value is "page", except if an image output was set to continuous, in which case it is "none".</p>
     * @var string
     */
    public const OVERFLOW_BLOCK = "OVERFLOW_BLOCK";
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing the behavior of the device when content overflows the initial containing block in the inline axis.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const OVERFLOW_INLINE = "OVERFLOW_INLINE";
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing whether the primary input mechanism is a pointing device, and if so, how accurate is it.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const POINTER = "POINTER";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) reflecting the user's desire that the page use a light or dark color theme.</p>
     * <p>The default value is "light".</p>
     * @var string
     */
    public const PREFERS_COLOR_SCHEME = "PREFERS_COLOR_SCHEME";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) used to detect if the user has requested more or less contrast in the page.</p>
     * <p>The default value is "no-preference".</p>
     * @var string
     */
    public const PREFERS_CONSTRAST = "PREFERS_CONSTRAST";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) used to detect if the user has a preference for being served alternate content that uses less data for the page to be rendered.</p>
     * <p>The default value is "no-preference".</p>
     * @var string
     */
    public const PREFERS_REDUCED_DATA = "PREFERS_REDUCED_DATA";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) used to detect if the user has requested the system minimize the amount of animation or motion it uses.</p>
     * <p>The default value is "reduce".</p>
     * @var string
     */
    public const PREFERS_REDUCED_MOTION = "PREFERS_REDUCED_MOTION";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) used to detect if the user has requested the system minimize the amount of transparent or translucent layer effects it uses.</p>
     * <p>The default value is "no-preference".</p>
     * @var string
     */
    public const PREFERS_REDUCED_TRANSPARENCY = "PREFERS_REDUCED_TRANSPARENCY";
    /**
     * <p>CSS 3 Media Feature describing the resolution of the output device.</p>
     * <p>This also defines the value of the <code>window.devicePixelRatio</code> property available from JavaScript.</p>
     * <p>Default value is 300dpi.</p>
     * @var string
     */
    public const RESOLUTION = "RESOLUTION";
    /**
     * <p>CSS Media Feature (Media Queries Level 4) describing the scanning process of some output devices.</p>
     * <p>The default value is "progressive".</p>
     * @var string
     */
    public const SCAN = "SCAN";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) used to query whether scripting languages, such as JavaScript, are supported on the current document.</p>
     * <p>The default value is "initial-only" if JavaScript has been enabled or "none" otherwise.</p>
     * @var string
     */
    public const SCRIPTING = "SCRIPTING";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) used to query the ability of the output device to modify the appearance of content once it has been rendered.</p>
     * <p>The default value is "none".</p>
     * @var string
     */
    public const UPDATE = "UPDATE";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) that describes the number of logical segments of the viewport in the vertical direction.</p>
     * <p>The default value is "1".</p>
     * @var string
     */
    public const VERTICAL_VIEWPORT_SEGMENTS = "VERTICAL_VIEWPORT_SEGMENTS";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) describing the approximate range of colors that are supported by the UA and output device's video plane.</p>
     * <p>The default value is "srgb".</p>
     * @var string
     */
    public const VIDEO_COLOR_GAMUT = "VIDEO_COLOR_GAMUT";
    /**
     * <p>CSS Media Feature (Media Queries Level 5) representing the combination of max brightness, color depth, and contrast ratio that are supported by the UA and output device's video plane.</p>
     * <p>The default value is "standard".</p>
     * @var string
     */
    public const VIDEO_DYNAMIC_RANGE = "VIDEO_DYNAMIC_RANGE";
    /**
     * <p>CSS 3 Media Feature width of page content.</p>
     * <p>The default width is that of a DIN A4 page with 2cm margin (170mm).</p>
     * @var string
     */
    public const WIDTH = "WIDTH";
}
/**
 * <p>An enum containing merge mode constants.</p>
 */
abstract class MergeMode {
    /**
     * <p>Default merge mode: Append converted document to existing PDF.</p>
     * @var string
     */
    public const APPEND = "APPEND";
    /**
     * <p>Advanced merge mode: Allows to insert specific pages from existing PDFs into
     * the converted document.</p>
     * <p>This is done via a special syntax of
     *  {@see Configuration::setPageOrder(String)}
     * .</p>
     * @var string
     */
    public const ARRANGE = "ARRANGE";
    /**
     * <p>Alternate merge mode (overlay): Adding converted document above the existing PDF.</p>
     * @var string
     */
    public const OVERLAY = "OVERLAY";
    /**
     * <p>Alternate merge mode (overlay): Adding converted document below the existing PDF.</p>
     * @var string
     */
    public const OVERLAY_BELOW = "OVERLAY_BELOW";
    /**
     * <p>Alternate merge mode: Prepend converted document to existing PDF.</p>
     * @var string
     */
    public const PREPEND = "PREPEND";
}
/**
 * <p>An enum containing default profiles for output intents.</p>
 */
abstract class OutputIntentDefaultProfile {
    /**
     * <p>"Coated FOGRA39" output intent default profile.</p>
     * @var string
     */
    public const FOGRA39 = "Coated FOGRA39";
    /**
     * <p>"Coated GRACoL 2006" output intent default profile.</p>
     * @var string
     */
    public const GRACOL = "Coated GRACoL 2006";
    /**
     * <p>"ISO News print 26% (IFRA)" output intent default profile.</p>
     * @var string
     */
    public const IFRA = "ISO News print 26% (IFRA)";
    /**
     * <p>"Japan Color 2001 Coated" output intent default profile.</p>
     * @var string
     */
    public const JAPAN = "Japan Color 2001 Coated";
    /**
     * <p>"Japan Color 2001 Newspaper" output intent default profile.</p>
     * @var string
     */
    public const JAPAN_NEWSPAPER = "Japan Color 2001 Newspaper";
    /**
     * <p>"Japan Color 2001 Uncoated" output intent default profile.</p>
     * @var string
     */
    public const JAPAN_UNCOATED = "Japan Color 2001 Uncoated";
    /**
     * <p>"Japan Web Coated (Ad)" output intent default profile.</p>
     * @var string
     */
    public const JAPAN_WEB = "Japan Web Coated (Ad)";
    /**
     * <p>"US Web Coated (SWOP) v2" output intent default profile.</p>
     * @var string
     */
    public const SWOP = "US Web Coated (SWOP) v2";
    /**
     * <p>"Web Coated SWOP 2006 Grade 3 Paper" output intent default profile.</p>
     * @var string
     */
    public const SWOP_3 = "Web Coated SWOP 2006 Grade 3 Paper";
}
/**
 * <p>An enum containing output format constants.</p>
 */
abstract class OutputType {
    /**
     * <p>BMP output format.</p>
     * @var string
     */
    public const BMP = "BMP";
    /**
     * <p>GIF output format.</p>
     * @var string
     */
    public const GIF = "GIF";
    /**
     * <p>JPEG output format, with dithering applied.</p>
     * @var string
     */
    public const GIF_DITHERED = "GIF_DITHERED";
    /**
     * <p>JPEG output format.</p>
     * @var string
     */
    public const JPEG = "JPEG";
    /**
     * <p>PDF output format.</p>
     * @var string
     */
    public const PDF = "PDF";
    /**
     * <p>PNG output format.</p>
     * @var string
     */
    public const PNG = "PNG";
    /**
     * <p>Deprecated as of PDFreactor 11. Use
     *  {@see OutputType::PNG}
     * instead.</p>
     * @var string
     */
    public const PNG_AI = "PNG_AI";
    /**
     * <p>Transparent PNG output format.</p>
     * @var string
     */
    public const PNG_TRANSPARENT = "PNG_TRANSPARENT";
    /**
     * <p>Deprecated as of PDFreactor 11. Use
     *  {@see OutputType::PNG_TRANSPARENT}
     * instead.</p>
     * @var string
     */
    public const PNG_TRANSPARENT_AI = "PNG_TRANSPARENT_AI";
    /**
     * <p>Monochrome CCITT 1D/RLE compressed TIFF output format.</p>
     * @var string
     */
    public const TIFF_CCITT_1D = "TIFF_CCITT_1D";
    /**
     * <p>Monochrome CCITT 1D/RLE compressed TIFF output format, with dithering applied.</p>
     * @var string
     */
    public const TIFF_CCITT_1D_DITHERED = "TIFF_CCITT_1D_DITHERED";
    /**
     * <p>Monochrome CCITT Group 3/T.4 compressed TIFF output format.</p>
     * @var string
     */
    public const TIFF_CCITT_GROUP_3 = "TIFF_CCITT_GROUP_3";
    /**
     * <p>Monochrome CCITT Group 3/T.4 compressed TIFF output format, with dithering applied.</p>
     * @var string
     */
    public const TIFF_CCITT_GROUP_3_DITHERED = "TIFF_CCITT_GROUP_3_DITHERED";
    /**
     * <p>Monochrome CCITT Group 4/T.6 compressed TIFF output format.</p>
     * @var string
     */
    public const TIFF_CCITT_GROUP_4 = "TIFF_CCITT_GROUP_4";
    /**
     * <p>Monochrome CCITT Group 4/T.6 compressed TIFF output format, with dithering applied.</p>
     * @var string
     */
    public const TIFF_CCITT_GROUP_4_DITHERED = "TIFF_CCITT_GROUP_4_DITHERED";
    /**
     * <p>LZW compressed TIFF output format.</p>
     * @var string
     */
    public const TIFF_LZW = "TIFF_LZW";
    /**
     * <p>PackBits compressed TIFF output format.</p>
     * @var string
     */
    public const TIFF_PACKBITS = "TIFF_PACKBITS";
    /**
     * <p>Uncompressed TIFF output format.</p>
     * @var string
     */
    public const TIFF_UNCOMPRESSED = "TIFF_UNCOMPRESSED";
}
/**
 * <p>An enum containing constants that determines whether the converted document
 * or the specified PDF document(s) is the content document for overlaying.</p>
 */
abstract class OverlayContentDocument {
    /**
     * <p>The converted HTML document will be the content document.</p>
     * @var string
     */
    public const CONVERTED = "CONVERTED";
    /**
     * <p>The PDF document(s) indicated in the MergeSettings "documents",
     * appended to each other if there are multiple ones, will be the content document.</p>
     * @var string
     */
    public const PDF = "PDF";
}
/**
 * <p>An enum containing data to configure how overlay pages that have
 * different dimensions from the pages they are overlaying should be resized.</p>
 */
abstract class OverlayFit {
    /**
     * <p>The page keeps its aspect ratio, but is resized to fit within the given dimension.</p>
     * @var string
     */
    public const CONTAIN = "CONTAIN";
    /**
     * <p>The page keeps its aspect ratio and fills the given dimension. It will be clipped to fit.</p>
     * @var string
     */
    public const COVER = "COVER";
    /**
     * <p>The default. The page is resized to fill the given dimension.
     * If necessary, the page will be stretched or squished to fit.</p>
     * @var string
     */
    public const FILL = "FILL";
    /**
     * <p>The page is not resized. If necessary it will be clipped to fit.</p>
     * @var string
     */
    public const NONE = "NONE";
}
/**
 * <p>An enum containing data for repeating overlays.</p>
 */
abstract class OverlayRepeat {
    /**
     * <p>All pages of the shorter document are repeated, to overlay all pages of the longer document.</p>
     * @var string
     */
    public const ALL_PAGES = "ALL_PAGES";
    /**
     * <p>Last page of the shorter document is repeated, to overlay all pages of the longer document.</p>
     * @var string
     */
    public const LAST_PAGE = "LAST_PAGE";
    /**
     * <p>No pages of the shorter document are repeated, leaving some pages of the longer document without overlay.</p>
     * @var string
     */
    public const NONE = "NONE";
    /**
     * <p>The resulting PDF is trimmed to the number of pages of the shorter document.</p>
     * @var string
     */
    public const TRIM = "TRIM";
}
/**
 * <p>An enum containing pre-defined page orders.</p>
 */
abstract class PageOrder {
    /**
     * <p>Page order mode to arrange all pages in booklet order. To be used with
     *  {@see PagesPerSheetDirection::RIGHT_DOWN}
     * .
     * @var string
     */
    public const BOOKLET = "BOOKLET";
    /**
     * <p>Page order mode to arrange all pages in right-to-left booklet order. To be used with
     *  {@see PagesPerSheetDirection::RIGHT_DOWN}
     * .</p>
     * @var string
     */
    public const BOOKLET_RTL = "BOOKLET_RTL";
    /**
     * <p>Page order mode to keep even pages only.</p>
     * @var string
     */
    public const EVEN = "EVEN";
    /**
     * <p>Page order mode to keep odd pages only.</p>
     * @var string
     */
    public const ODD = "ODD";
    /**
     * <p>Page order mode to reverse the page order.</p>
     * @var string
     */
    public const REVERSE = "REVERSE";
}
/**
 * <p>An enum containing constants for pages per sheet directions.</p>
 */
abstract class PagesPerSheetDirection {
    /**
     * <p>Arranges the pages on a sheet from top to bottom and right to left.</p>
     * @var string
     */
    public const DOWN_LEFT = "DOWN_LEFT";
    /**
     * <p>Arranges the pages on a sheet from top to bottom and left to right.</p>
     * @var string
     */
    public const DOWN_RIGHT = "DOWN_RIGHT";
    /**
     * <p>Arranges the pages on a sheet from right to left and top to bottom.</p>
     * @var string
     */
    public const LEFT_DOWN = "LEFT_DOWN";
    /**
     * <p>Arranges the pages on a sheet from right to left and bottom to top.</p>
     * @var string
     */
    public const LEFT_UP = "LEFT_UP";
    /**
     * <p>Arranges the pages on a sheet from left to right and top to bottom.</p>
     * @var string
     */
    public const RIGHT_DOWN = "RIGHT_DOWN";
    /**
     * <p>Arranges the pages on a sheet from left to right and bottom to top.</p>
     * @var string
     */
    public const RIGHT_UP = "RIGHT_UP";
    /**
     * <p>Arranges the pages on a sheet from bottom to top and right to left.</p>
     * @var string
     */
    public const UP_LEFT = "UP_LEFT";
    /**
     * <p>Arranges the pages on a sheet from bottom to top and left to right.</p>
     * @var string
     */
    public const UP_RIGHT = "UP_RIGHT";
}
/**
 * <p>An enum containing trigger events for PDF scripts.</p>
 */
abstract class PdfScriptTriggerEvent {
    /**
     * <p>This event is triggered after the PDF has been printed by the viewer application.</p>
     * @var string
     */
    public const AFTER_PRINT = "AFTER_PRINT";
    /**
     * <p>This event is triggered after the PDF has been saved by the viewer application.</p>
     * @var string
     */
    public const AFTER_SAVE = "AFTER_SAVE";
    /**
     * <p>This event is triggered before the PDF is printed by the viewer application.</p>
     * @var string
     */
    public const BEFORE_PRINT = "BEFORE_PRINT";
    /**
     * <p>This event is triggered before the PDF is saved by the viewer application.</p>
     * @var string
     */
    public const BEFORE_SAVE = "BEFORE_SAVE";
    /**
     * <p>This event is triggered when the PDF is closed by the viewer application.</p>
     * @var string
     */
    public const CLOSE = "CLOSE";
    /**
     * <p>This event is triggered when the PDF is opened in the viewer application.</p>
     * @var string
     */
    public const OPEN = "OPEN";
}
/**
 * <p>An enum containing constants for processing preferences.</p>
 */
abstract class ProcessingPreferences {
    /**
     * <p>Processing preferences flag for the memory saving mode for images.</p>
     * @var string
     */
    public const SAVE_MEMORY_IMAGES = "SAVE_MEMORY_IMAGES";
}
/**
 * <p>An enum containing modes for Quirks.</p>
 */
abstract class QuirksMode {
    /**
     * <p>Doctype dependent behavior.</p>
     * @var string
     */
    public const DETECT = "DETECT";
    /**
     * <p>Forced quirks behavior.</p>
     * @var string
     */
    public const QUIRKS = "QUIRKS";
    /**
     * <p>Forced no-quirks behavior.</p>
     * @var string
     */
    public const STANDARDS = "STANDARDS";
}
/**
 * <p>An enum containing resolution units.</p>
 */
abstract class ResolutionUnit {
    /**
     * <p>Dots per inch. The default 1dppx/96dpi in this unit is about 38.</p>
     * @var string
     */
    public const DPCM = "DPCM";
    /**
     * <p>Dots per Inch. The default 1dppx/96dpi in this unit is 96.</p>
     * @var string
     */
    public const DPI = "DPI";
    /**
     * <p>Dots per 'px' unit. The default 1dppx/96dpi in this unit is 1.</p>
     * @var string
     */
    public const DPPX = "DPPX";
    /**
     * <p>Thousand dots per centimeter. The default 1dppx/96dpi in this unit is about 37795.</p>
     * @var string
     */
    public const TDPCM = "TDPCM";
    /**
     * <p>Thousand dots per inch. The default 1dppx/96dpi in this unit is 96000.</p>
     * @var string
     */
    public const TDPI = "TDPI";
    /**
     * <p>Thousand dots per 'px' unit. The default 1dppx/96dpi in this unit is 1000.</p>
     * @var string
     */
    public const TDPPX = "TDPPX";
}
/**
 * <p>An enum containing resource sub type constants.</p>
 */
abstract class ResourceSubtype {
    /**
     * <p>Indicates a "classic" (non-module) JavaScript. Used for resources of type
     *  {@see ResourceType::SCRIPT}
     * .</p>
     * @var string
     */
    public const JAVASCRIPT_CLASSIC = "JAVASCRIPT_CLASSIC";
    /**
     * <p>Indicates a JavaScript import map. Used for resources of type
     *  {@see ResourceType::SCRIPT}
     * .</p>
     * @var string
     */
    public const JAVASCRIPT_IMPORTMAP = "JAVASCRIPT_IMPORTMAP";
    /**
     * <p>Indicates a JavaScript module. Used for resources of type
     *  {@see ResourceType::SCRIPT}
     * .</p>
     * @var string
     */
    public const JAVASCRIPT_MODULE = "JAVASCRIPT_MODULE";
}
/**
 * <p>Indicates the type of resource.</p>
 */
abstract class ResourceType {
    /**
     * <p>An attachment.</p>
     * @var string
     */
    public const ATTACHMENT = "ATTACHMENT";
    /**
     * The main HTML or XML document.
     * @var string
     */
    public const DOCUMENT = "DOCUMENT";
    /**
     * <p>A font.</p>
     * @var string
     */
    public const FONT = "FONT";
    /**
     * <p>An ICC profile.</p>
     * @var string
     */
    public const ICC_PROFILE = "ICC_PROFILE";
    /**
     * <p>An iframe.</p>
     * @var string
     */
    public const IFRAME = "IFRAME";
    /**
     * <p>An image.</p>
     * @var string
     */
    public const IMAGE = "IMAGE";
    /**
     * <p>The license key.</p>
     * @var string
     */
    public const LICENSEKEY = "LICENSEKEY";
    /**
     * <p>A merge document.</p>
     * @var string
     */
    public const MERGE_DOCUMENT = "MERGE_DOCUMENT";
    /**
     * <p>An embedded object.</p>
     * @var string
     */
    public const OBJECT = "OBJECT";
    /**
     * <p>A running document.</p>
     * @var string
     */
    public const RUNNING_DOCUMENT = "RUNNING_DOCUMENT";
    /**
     * <p>A script.</p>
     * @var string
     */
    public const SCRIPT = "SCRIPT";
    /**
     * <p>A style sheet.</p>
     * @var string
     */
    public const STYLESHEET = "STYLESHEET";
    /**
     * <p>An unknown resource type.</p>
     * @var string
     */
    public const UNKNOWN = "UNKNOWN";
    /**
     * An XMLHttpRequest.
     * @var string
     */
    public const XHR = "XHR";
}
/**
 * <p>An enum containing the cryptographic filter type that is used for signing.</p>
 */
abstract class SigningMode {
    /**
     * <p>The self signed filter: PDFreactor creates a signature with the adbe.x509.rsa_sha1 (PKCS#1) filter type.</p>
     * @var string
     */
    public const SELF_SIGNED = "SELF_SIGNED";
    /**
     * <p>The VeriSign filter. PDFreactor creates a signature with VeriSign filter type.</p>
     * @var string
     */
    public const VERISIGN_SIGNED = "VERISIGN_SIGNED";
    /**
     * <p>The Windows Certificate Security: PDFreactor creates a signature with the adbe.pkcs7.sha1 (PKCS#7) filter type.</p>
     * @var string
     */
    public const WINCER_SIGNED = "WINCER_SIGNED";
}
/**
 * <p>An enum containing constants for viewer preferences.</p>
 */
abstract class ViewerPreferences {
    /**
     * <p>Position the document's window in the center of the screen.</p>
     * @var string
     */
    public const CENTER_WINDOW = "CENTER_WINDOW";
    /**
     * <p>Position pages in ascending order from left to right.</p>
     * @var string
     */
    public const DIRECTION_L2R = "DIRECTION_L2R";
    /**
     * <p>Position pages in ascending order from right to left.</p>
     * @var string
     */
    public const DIRECTION_R2L = "DIRECTION_R2L";
    /**
     * <p>Display the document's title in the top bar.</p>
     * @var string
     */
    public const DISPLAY_DOC_TITLE = "DISPLAY_DOC_TITLE";
    /**
     * <p>Print dialog default setting: duplex (long edge).</p>
     * @var string
     */
    public const DUPLEX_FLIP_LONG_EDGE = "DUPLEX_FLIP_LONG_EDGE";
    /**
     * <p>Print dialog default setting: duplex (short edge).</p>
     * @var string
     */
    public const DUPLEX_FLIP_SHORT_EDGE = "DUPLEX_FLIP_SHORT_EDGE";
    /**
     * <p>Print dialog default setting: simplex.</p>
     * @var string
     */
    public const DUPLEX_SIMPLEX = "DUPLEX_SIMPLEX";
    /**
     * <p>Resize the document's window to fit the size of the first displayed page.</p>
     * @var string
     */
    public const FIT_WINDOW = "FIT_WINDOW";
    /**
     * <p>Hide the viewer application's menu bar when the document is active.</p>
     * @var string
     */
    public const HIDE_MENUBAR = "HIDE_MENUBAR";
    /**
     * <p>Hide the viewer application's tool bars when the document is active.</p>
     * @var string
     */
    public const HIDE_TOOLBAR = "HIDE_TOOLBAR";
    /**
     * <p>Hide user interface elements in the document's window.</p>
     * @var string
     */
    public const HIDE_WINDOW_UI = "HIDE_WINDOW_UI";
    /**
     * <p>Show no panel on exiting full-screen mode. Has to be combined with
     *  {@see ViewerPreferences::PAGE_MODE_FULLSCREEN}
     * .</p>
     * @var string
     */
    public const NON_FULLSCREEN_PAGE_MODE_USE_NONE = "NON_FULLSCREEN_PAGE_MODE_USE_NONE";
    /**
     * <p>Show optional content group panel on exiting full-screen mode. Has to be combined with
     *  {@see ViewerPreferences::PAGE_MODE_FULLSCREEN}
     * .</p>
     * @var string
     */
    public const NON_FULLSCREEN_PAGE_MODE_USE_OC = "NON_FULLSCREEN_PAGE_MODE_USE_OC";
    /**
     * <p>Show bookmarks panel on exiting full-screen mode. Has to be combined with
     *  {@see ViewerPreferences::PAGE_MODE_FULLSCREEN}
     * .</p>
     * @var string
     */
    public const NON_FULLSCREEN_PAGE_MODE_USE_OUTLINES = "NON_FULLSCREEN_PAGE_MODE_USE_OUTLINES";
    /**
     * <p>Show thumbnail images panel on exiting full-screen mode. Has to be combined with
     *  {@see ViewerPreferences::PAGE_MODE_FULLSCREEN}
     * .</p>
     * @var string
     */
    public const NON_FULLSCREEN_PAGE_MODE_USE_THUMBS = "NON_FULLSCREEN_PAGE_MODE_USE_THUMBS";
    /**
     * <p>Display the pages in one column.</p>
     * @var string
     */
    public const PAGE_LAYOUT_ONE_COLUMN = "PAGE_LAYOUT_ONE_COLUMN";
    /**
     * <p>Display one page at a time (default).</p>
     * @var string
     */
    public const PAGE_LAYOUT_SINGLE_PAGE = "PAGE_LAYOUT_SINGLE_PAGE";
    /**
     * <p>Display the pages in two columns, with odd numbered pages on the left.</p>
     * @var string
     */
    public const PAGE_LAYOUT_TWO_COLUMN_LEFT = "PAGE_LAYOUT_TWO_COLUMN_LEFT";
    /**
     * <p>Display the pages in two columns, with odd numbered pages on the right.</p>
     * @var string
     */
    public const PAGE_LAYOUT_TWO_COLUMN_RIGHT = "PAGE_LAYOUT_TWO_COLUMN_RIGHT";
    /**
     * <p>Display two pages at a time, with odd numbered pages on the left.</p>
     * @var string
     */
    public const PAGE_LAYOUT_TWO_PAGE_LEFT = "PAGE_LAYOUT_TWO_PAGE_LEFT";
    /**
     * <p>Display two pages at a time, with odd numbered pages on the right.</p>
     * @var string
     */
    public const PAGE_LAYOUT_TWO_PAGE_RIGHT = "PAGE_LAYOUT_TWO_PAGE_RIGHT";
    /**
     * <p>Switch to fullscreen mode on startup.</p>
     * @var string
     */
    public const PAGE_MODE_FULLSCREEN = "PAGE_MODE_FULLSCREEN";
    /**
     * <p>Show attachments panel on startup.</p>
     * @var string
     */
    public const PAGE_MODE_USE_ATTACHMENTS = "PAGE_MODE_USE_ATTACHMENTS";
    /**
     * <p>Show no panel on startup.</p>
     * @var string
     */
    public const PAGE_MODE_USE_NONE = "PAGE_MODE_USE_NONE";
    /**
     * <p>Show optional content group panel on startup.</p>
     * @var string
     */
    public const PAGE_MODE_USE_OC = "PAGE_MODE_USE_OC";
    /**
     * <p>Show bookmarks panel on startup.</p>
     * @var string
     */
    public const PAGE_MODE_USE_OUTLINES = "PAGE_MODE_USE_OUTLINES";
    /**
     * <p>Show thumbnail images panel on startup.</p>
     * @var string
     */
    public const PAGE_MODE_USE_THUMBS = "PAGE_MODE_USE_THUMBS";
    /**
     * <p>Print dialog default setting: do not pick tray by PDF size.</p>
     * @var string
     */
    public const PICKTRAYBYPDFSIZE_FALSE = "PICKTRAYBYPDFSIZE_FALSE";
    /**
     * <p>Print dialog default setting: pick tray by PDF size.</p>
     * @var string
     */
    public const PICKTRAYBYPDFSIZE_TRUE = "PICKTRAYBYPDFSIZE_TRUE";
    /**
     * <p>Print dialog default setting: set scaling to application default value.</p>
     * @var string
     */
    public const PRINTSCALING_APPDEFAULT = "PRINTSCALING_APPDEFAULT";
    /**
     * <p>Print dialog default setting: disabled scaling.</p>
     * @var string
     */
    public const PRINTSCALING_NONE = "PRINTSCALING_NONE";
}
/**
 * <p>An enum containing the priority for XMP.</p>
 */
abstract class XmpPriority {
    /**
     * <p>Embed XMP ignoring requirements of the output format.</p>
     * <p><i>This may cause output PDFs to not achieve a specified conformance.</i></p>
     * @var string
     */
    public const HIGH = "HIGH";
    /**
     * <p>Embed XMP if the output format does not have XMP requirements.</p>
     * @var string
     */
    public const LOW = "LOW";
    /**
     * <p>Do not embed XMP.</p>
     * @var string
     */
    public const NONE = "NONE";
}
?>
