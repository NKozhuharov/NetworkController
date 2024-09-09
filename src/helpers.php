<?php

if (!function_exists('uploads_path')) {
    /**
     * Get the path, where the uploaded files are stored
     *
     * @return string
     */
    function uploads_path(): string
    {
        return base_path() . '/' . config('networkcontroller.uploads.path');
    }
}

if (!function_exists('images_path')) {
    /**
     * Get the path, where the uploaded images are stored
     *
     * @return string
     */
    function images_path(): string
    {
        return base_path() . '/' . config('networkcontroller.images.path');
    }
}

if (!function_exists('uploaded_file_path')) {
    /**
     * Get the path, where a specific uploaded file is stored
     *
     * @param string $fileName
     * @return string
     */
    function uploaded_file_path(string $fileName): string
    {
        return uploads_path() . '/' . $fileName;
    }
}

if (!function_exists('uploaded_image_path')) {
    /**
     * Get the path, where a specific uploaded image is stored
     *
     * @param string $fileName
     * @return string
     */
    function uploaded_image_path(string $fileName): string
    {
        return images_path() . '/' . $fileName;
    }
}

if (!function_exists('resized_image_url')) {
    /**
     * Get the url, which will resize the image using the specified width
     *
     * @param string $url
     * @param int $width
     * @return string
     */
    function resized_image_url(string $url, int $width): string
    {
        if (!in_array($width, explode(',', config('networkcontroller.images.supported_sizes')))) {
            throw new \InvalidArgumentException('Invalid image width');
        }

        return str_replace('/org/', '/' . $width . '/', $url);
    }
}
