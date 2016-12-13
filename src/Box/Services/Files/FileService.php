<?php

namespace Box\Services\Files;

use Box\Enums\ExceptionMessages;
use Box\Enums\BoxAccessPoints;
use Box\Exceptions\Files\FileNotFoundException;
use Box\Services\BaseService;
use Box\Auth\AppAuth;

class FileService extends BaseService
{

    public function __construct(AppAuth $app_auth)
    {
        parent::__construct($app_auth);
    }

    public function uploadPreFlight($file_path = "", $folder_id = 0)
    {
        $handle = $this->readFile($file_path);

        $file_name = basename($file_path);

        // Throws exception on 4XX response code
        return $this->guzzle_client->request(
            'OPTIONS',
            BoxAccessPoints::FILEUPLOADPREFLIGHT,
            [
                "json" => [
                    "name" => $file_name,
                    "parent" => [
                        "id" => (string)$folder_id
                    ],
                    "size" => filesize($file_path)
                ],
                "headers" => $this->getAuthHeaders()
            ]
        );
    }

    public function uploadFile($file_path = "", $folder_id = 0)
    {
        $handle = $this->readFile($file_path);

        $file_name = basename($file_path);

        // Throws exception on 4XX response code
        return $this->guzzle_client->request(
            'POST',
            BoxAccessPoints::FILEUPLOAD,
            [
                "multipart" => [
                    [
                        "name" => "attributes",
                        "contents" => json_encode([
                            "name" => $file_name,
                            "parent" => [
                                "id" => $folder_id
                            ]
                        ])
                    ],
                    [
                        "name" => "file",
                        "contents" => $handle
                    ]
                ],
                "headers" => $this->getAuthHeaders()
            ]
        );
    }

    /**
     * Returns the handle of fopen
     */
    private function readFile($file_path)
    {
        if (!file_exists($file_path) || (($handle = fopen($file_path, 'r')) === false)) {
            throw new FileNotFoundException(ExceptionMessages::FILENOTFOUND . " (file : ". $file_path .")");
        }

        return $handle;
    }
}
