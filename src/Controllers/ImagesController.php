<?php

namespace Nevestul4o\NetworkController\Controllers;

use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImagesController extends UploadController
{
    /**
     * Contains all the supported image widths
     *
     * @var array
     */
    protected array $supportedImageSizes;

    /**
     * The path, where the resized images will be stored
     *
     * @var string
     */
    protected string $resizedImagesPath;

    /**
     * Set to false to keep the metadata of the cached images
     *
     * @var bool
     */
    protected bool $removeMetadata;

    /**
     * ImagesController constructor.
     * Validates and initializes $supportedImageSizes and $resizedImagesPath.
     * Requires IMAGES_SUPPORTED_SIZES and IMAGES_RESIZED_PATH in the Laravel .env file
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $supportedImageSizes = env('IMAGES_SUPPORTED_SIZES', '');
        if (empty($supportedImageSizes)) {
            throw new Exception("To use the images controller, add IMAGES_SUPPORTED_SIZES configuration variable to the .env file!");
        }

        $supportedImageSizes = explode(',', $supportedImageSizes);
        if (empty($supportedImageSizes)) {
            throw new Exception("Invalid IMAGES_SUPPORTED_SIZES configuration variable!");
        }

        foreach ($supportedImageSizes as $supportedImageSize) {
            $supportedImageSize = (int)$supportedImageSize;
            if (empty($supportedImageSize)) {
                throw new Exception("Invalid IMAGES_SUPPORTED_SIZES configuration variable!");
            }
            $this->supportedImageSizes[] = $supportedImageSize;
        }

        $this->supportedImageSizes[] = $this->imagesOrgPathName;

        $this->resizedImagesPath = env('IMAGES_RESIZED_PATH', '');
        if (empty($this->resizedImagesPath)) {
            throw new Exception("To use the images controller, add IMAGES_RESIZED_PATH configuration variable to the .env file!");
        }

        $this->resizedImagesPath = base_path() . '/../' . $this->resizedImagesPath;

        $this->ensureDirectoryExists($this->resizedImagesPath);

        $this->removeMetadata = env('IMAGES_REMOVE_METADATA' , TRUE);
    }

    /**
     * @param string $requestedWidth
     * @param string $imgName
     * @return BinaryFileResponse
     * @throws Exception
     */
    public function getImage(string $requestedWidth, string $imgName): BinaryFileResponse
    {
        if (!in_array($requestedWidth, $this->supportedImageSizes)) {
            throw new Exception("Requested width {$requestedWidth} is not supported!");
        }

        $originalImgFullPath = $this->getImagesPath() . '/' . $imgName;
        if (!file_exists($originalImgFullPath)) {
            throw new Exception("{$imgName} does not exist!");
        }

        if ($requestedWidth === $this->imagesOrgPathName) {
            return response()->file($originalImgFullPath);
        }

        $resizedImageDirectory = $this->resizedImagesPath . '/' . $requestedWidth;

        $this->ensureDirectoryExists($resizedImageDirectory);

        $resizedImageFullPath = $resizedImageDirectory . '/' . $imgName;

        if (file_exists($resizedImageFullPath)) {
            return response()->file($resizedImageFullPath);
        }

        $imagick = new \Imagick($originalImgFullPath);
        $ratio = $requestedWidth / $imagick->getImageWidth();

        $imagick->resizeImage($requestedWidth, $imagick->getImageHeight() * $ratio, $imagick::FILTER_LANCZOS, 1);

        if ($this->removeMetadata) {
            $imagick->stripImage();
        }

        $imagick->writeImage($resizedImageFullPath);

        return response()->file($resizedImageFullPath);
    }
}
