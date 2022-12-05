<?php

if (! function_exists('uploads_path')) {
    /**
     * Get the path, where the uploaded files are stored
     *
     * @return string
     * @throws Exception
     */
    function uploads_path(): string
    {
        $configuredPath = config('networkcontroller.uploads.path');
        if (empty($configuredPath)) {
            throw new Exception('To use the uploads controller, set UPLOADS_PATH configuration variable in the .env file to a non-empty string!');
        }

        return base_path() . '/../' . $configuredPath;
    }
}

if (! function_exists('uploaded_images_path')) {
    /**
     * Get the path, where the uploaded images are stored
     *
     * @return string
     * @throws Exception
     */
    function uploaded_images_path(): string
    {
        $configuredPath = config('networkcontroller.images.path');
        if (empty($configuredPath)) {
            throw new Exception('To use the uploads controller, set IMAGES_PATH configuration variable in the .env file to a non-empty string!');
        }

        return base_path() . '/../' . $configuredPath;
    }
}
