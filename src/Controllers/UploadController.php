<?php

namespace Nevestul4o\NetworkController\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class UploadController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * The key in the request, which contains the files
     *
     * @var string
     */
    protected $filesArrayKey = 'files';

    /**
     * Files with these MIME types will be considered images
     *
     * @var array
     */
    protected $imageMimeTypes = ['image/jpeg', 'image/png'];

    /**
     * Request, containing this path, will return the original image
     *
     * @var string
     */
    protected $imagesOrgPathName = 'org';

    /**
     * This url part indicates, that the requested resource is an image
     *
     * @var string
     */
    protected $imagesUrl = 'images';

    /**
     * This url part indicates, that the requested resource is a file (not an image) :D
     *
     * @var string
     */
    protected $filesUrl = 'files';

    /**
     * Path, where all files are uploaded
     *
     * @var string
     */
    private $uploadsPath = '';

    /**
     * Path, where all images are uploaded
     *
     * @var string
     */
    private $imagesPath = '';

    /**
     * The key in the request, which contains the files
     *
     * @return string
     */
    public function getFilesArrayKey(): string
    {
        return $this->filesArrayKey;
    }

    /**
     * Files with these MIME types will be considered images
     *
     * @return array
     */
    public function getImageMimeTypes(): array
    {
        return $this->imageMimeTypes;
    }

    /**
     * Request, containing this path, will return the original image
     *
     * @return string
     */
    public function getImagesOrgPathName(): string
    {
        return $this->imagesOrgPathName;
    }

    /**
     * This url part indicates, that the requested resource is an image
     *
     * @return string
     */
    public function getImagesUrl(): string
    {
        return $this->imagesUrl;
    }

    /**
     * This url part indicates, that the requested resource is a file (not an image) :D
     *
     * @return string
     */
    public function getFilesUrl(): string
    {
        return $this->filesUrl;
    }

    /**
     * Path, where all files are uploaded
     *
     * @return string
     */
    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }

    /**
     * Path, where all images are uploaded
     *
     * @return string
     */
    public function getImagesPath(): string
    {
        return $this->imagesPath;
    }

    /**
     * Extracts the file name from an URL
     *
     * @param string $link
     * @return string
     */
    public function getFileNameFromLink(string $link): string
    {
        $fileName = explode('/', $link);
        return $fileName[count($fileName) - 1];
    }

    /**
     * Checks if a directory exists. If it doesn't attempts to create it.
     *
     * @param string $path
     * @throws Exception
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            try {
                mkdir($path, 0777);
            } catch (Exception $ex) {
                throw new Exception("Unable to create image storage path {$path}");
            }
        }
    }

    /**
     * UploadController constructor.
     * Validates and initializes $uploadsPath and $imagesPath.
     * Requires UPLOADS_PATH and IMAGES_PATH in the Laravel .env file
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->uploadsPath = env('UPLOADS_PATH', '');
        if (empty($this->uploadsPath)) {
            throw new Exception("To use the uploads controller, add UPLOADS_PATH configuration variable to the .env file!");
        }

        $this->imagesPath = env('IMAGES_PATH', '');
        if (empty($this->imagesPath)) {
            throw new Exception("To use the uploads controller, add IMAGES_PATH configuration variable to the .env file!");
        }

        $this->uploadsPath = base_path() . '/../' . $this->uploadsPath;
        $this->ensureDirectoryExists($this->uploadsPath);

        $this->imagesPath = base_path() . '/../' . $this->imagesPath;
        $this->ensureDirectoryExists($this->imagesPath);
    }

    /**
     * Handles the uploaded files:
     * 1) Images go to the path, defined in IMAGES_PATH
     * 2) All other files go to the path, defined in UPLOADS_PATH
     *
     * @param Request $request
     * @return array
     */
    public function uploadSubmit(Request $request): array
    {
        request()->validate(
            [
                $this->filesArrayKey        => 'required|array',
                $this->filesArrayKey . '.*' => 'required|max:2048',
            ]
        );

        $uploadedFiles = [];

        foreach ($request->{$this->filesArrayKey} as $files) {
            foreach ($files as $file) {
                $basePath = in_array($file->getClientMimeType(), $this->imageMimeTypes) ? $this->imagesPath : $this->uploadsPath;
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
                $fileInfo = $file->move($basePath, $fileName);

                if (in_array($file->getClientMimeType(), $this->imageMimeTypes)) {
                    $uploadedFiles[] = env('SITE_URL', url('/')) . $this->imagesUrl . '/' . $this->imagesOrgPathName . '/' . $fileInfo->getFilename();
                } else {
                    $uploadedFiles[] = env('SITE_URL', url('/')) . $this->filesUrl . '/' . $fileInfo->getFilename();
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Deletes a file, by it's link (or name)
     *
     * @param string $fileLink
     */
    public function deleteFile(string $fileLink): void
    {
        $fileName = $this->getFileNameFromLink($fileLink);
        if (file_exists($this->imagesPath . '/' . $fileName)) {
            unlink($filesForDeletion[] = $this->imagesPath . '/' . $fileName);
            return;
        }

        unlink($this->uploadsPath . '/' . $fileName);
    }
}
