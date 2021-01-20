<?php

namespace Nevestul4o\NetworkController\Rules;

use Illuminate\Contracts\Validation\Rule;
use Nevestul4o\NetworkController\Controllers\UploadController;

class FileExists implements Rule
{
    /**
     * @var string
     */
    private $fileName = '';

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $uploadController = new UploadController();

        $fileName = $uploadController->getFileNameFromLink($value);

        if (
            !file_exists($uploadController->getImagesPath() . '/' . $fileName)
            && !file_exists($uploadController->getUploadsPath() . '/' . $fileName)
        ) {
            $this->fileName = $fileName;
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "The file {$this->fileName} does not exist.";
    }
}
