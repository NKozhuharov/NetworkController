<?php

if (!function_exists('uploads_path')) {
    /**
     * Get the path, where the uploaded files are stored
     *
     * @return string
     */
    function uploads_path(): string
    {
        return base_path().'/'.config('networkcontroller.uploads.path');
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
        return base_path().'/'.config('networkcontroller.images.path');
    }
}

if (!function_exists('uploaded_file_path')) {
    /**
     * Get the path, where a specific uploaded file is stored
     *
     * @param  string  $fileName
     * @return string
     */
    function uploaded_file_path(string $fileName): string
    {
        return uploads_path().'/'.$fileName;
    }
}

if (!function_exists('uploaded_image_path')) {
    /**
     * Get the path, where a specific uploaded image is stored
     *
     * @param  string  $fileName
     * @return string
     */
    function uploaded_image_path(string $fileName): string
    {
        return images_path().'/'.$fileName;
    }
}
