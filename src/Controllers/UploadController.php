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
    protected string $filesArrayKey = 'files';

    /**
     * Files with these MIME types will be considered images
     *
     * @var array
     */
    protected array $imageMimeTypes = ['image/jpeg', 'image/png'];

    /**
     * Request, containing this path, will return the original image
     *
     * @var string
     */
    protected string $imagesOrgPathName = 'org';

    /**
     * This url part indicates, that the requested resource is an image
     *
     * @var string
     */
    protected string $imagesUrl = 'images';

    /**
     * This url part indicates, that the requested resource is a file (not an image) :D
     *
     * @var string
     */
    protected string $filesUrl = 'files';

    /**
     * The rules, which will be used to validate an upload request
     *
     * @var array
     */
    protected array $validationRules = [];

    /**
     * Path, where all files are uploaded
     *
     * @var string
     */
    private string $uploadsPath;

    /**
     * Path, where all images are uploaded
     *
     * @var string
     */
    private string $imagesPath;

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
     * Checks if a directory exists. If it doesn't attempt to create it.
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
     * Resizes an image to the specified width
     *
     * @param string $originalImgFullPath The full path of the original image
     * @param string $resizedImageFullPath The full path of the resized image
     * @param int $requestedWidth The width of the resized image
     * @param bool $stripImage (optional) Whether to strip the image metadata (default: false)
     * @return void
     */
    protected function resizeImage(string $originalImgFullPath, string $resizedImageFullPath, int $requestedWidth, bool $stripImage = false): void
    {
        $imagick = new \Imagick($originalImgFullPath);
        if ($imagick->getImageWidth() > $requestedWidth) {
            $ratio = $requestedWidth / $imagick->getImageWidth();

            $imagick->resizeImage($requestedWidth, $imagick->getImageHeight() * $ratio, $imagick::FILTER_LANCZOS, 1);

            if ($stripImage) {
                $imagick->stripImage();
            }

            $imagick->writeImage($resizedImageFullPath);
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
        $this->uploadsPath = config('networkcontroller.uploads.path');
        if (empty($this->uploadsPath)) {
            throw new Exception('To use the uploads controller, set UPLOADS_PATH configuration variable in the .env file to a non-empty string!');
        }

        $this->imagesPath = config('networkcontroller.images.path');
        if (empty($this->imagesPath)) {
            throw new Exception('To use the uploads controller, set IMAGES_PATH configuration variable in the .env file to a non-empty string!');
        }

        $this->uploadsPath = base_path() . '/' . $this->uploadsPath;
        $this->ensureDirectoryExists($this->uploadsPath);

        $this->imagesPath = base_path() . '/' . $this->imagesPath;
        $this->ensureDirectoryExists($this->imagesPath);

        $this->validationRules = [
            $this->filesArrayKey        => 'required|array',
            $this->filesArrayKey . '.*' => 'required|file|max:2048',
        ];
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
        if (!empty($this->validationRules)) {
            $request->validate($this->validationRules);
        }

        $uploadedFiles = [];

        foreach ($request->{$this->filesArrayKey} as $files) {
            foreach ($files as $file) {
                $basePath = in_array($file->getClientMimeType(), $this->imageMimeTypes) ? $this->imagesPath : $this->uploadsPath;
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
                $fileInfo = $file->move($basePath, $fileName);

                if (in_array($file->getClientMimeType(), $this->imageMimeTypes)) {
                    if (config('networkcontroller.images.auto_resize') && config('networkcontroller.images.auto_resize_width') > 0) {
                        $this->resizeImage(
                            $this->getImagesPath() . '/' . $fileName,
                            $this->getImagesPath() . '/' . $fileName,
                            config('networkcontroller.images.auto_resize_width')
                        );
                    }

                    $uploadedFiles[] = config('app.url', url('/')) . $this->imagesUrl . '/' . $this->imagesOrgPathName . '/' . $fileInfo->getFilename();
                } else {
                    $uploadedFiles[] = config('app.url', url('/')) . $this->filesUrl . '/' . $fileInfo->getFilename();
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Deletes a file, by its link (or name)
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
