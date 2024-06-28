<?php

namespace Nevestul4o\NetworkController\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TinyMCEUploadController extends UploadController
{
    /**
     * Uses the 'uploadSubmit' function from the Network controller.
     * Prepares the request to match the requirements of the function.
     * Returns only the image relative URL.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->merge([$this->filesUrl => [$request->file('file')]]);
        $request->files->set($this->filesUrl, [$request->file('file')]);

        $uploadedFiles = $this->uploadSubmit($request);

        $fileName = explode('/' , $uploadedFiles[0]);
        $fileName = array_pop($fileName);

        return new JsonResponse(['location' => '/'.$this->imagesUrl.'/'.$this->imagesOrgPathName.'/' . $fileName]);
    }
}
