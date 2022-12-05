<?php

if (! function_exists('uploads_path')) {
    /**
     * Get the path, where the uploaded files are stored
     *
     * @return string
     */
    function uploads_path(): string
    {
        return base_path() . '/../' . config('networkcontroller.uploads.path');
    }
}

if (! function_exists('uploaded_images_path')) {
    /**
     * Get the path, where the uploaded images are stored
     *
     * @return string
     */
    function uploaded_images_path(): string
    {
        return base_path() . '/../' . config('networkcontroller.images.path');
    }
}
