<?php

namespace Nevestul4o\NetworkController;

use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\Manager;

class ResponseHelper
{
    /**
     * Transforms a Fractal Resource (Item/Collection) to a Laravel JsonResponse
     *
     * @param  ResourceAbstract  $collection
     * @return JsonResponse
     */
    public function fractalResourceToJsonResponse(ResourceAbstract $collection): JsonResponse
    {
        $fractalManager = new Manager();
        return new JsonResponse($fractalManager->createData($collection)->toArray());
    }

    /**
     * Get a JsonResponse instance for 404 errors
     *
     * @return JsonResponse
     */
    public function errorNotFoundJsonResponse(): JsonResponse
    {
        return new JsonResponse('Not found', 404);
    }
}