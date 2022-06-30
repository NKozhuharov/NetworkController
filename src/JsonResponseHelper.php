<?php

namespace Nevestul4o\NetworkController;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\Manager;

/**
 * Stores functions, which are commonly used when creating responses
 */
class JsonResponseHelper
{
    const STATUS_ERROR = 'error';
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';
    const STATUS_INFO = 'info';

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
     * Get a standard JSON response (['data' => $data]) for the provided variable
     *
     * @param $data
     * @return JsonResponse
     */
    public function standardDataResponse($data): JsonResponse
    {
        return new JsonResponse(['data' => $data]);
    }

    /**
     * Get a JsonResponse instance for 404 errors
     *
     * @param  string  $message
     * @return JsonResponse
     */
    public function errorNotFoundJsonResponse(string $message = ''): JsonResponse
    {
        if (empty($message)) {
            $message = 'Not Found';
        }
        return new JsonResponse($message, 404);
    }

    /**
     * Get a JsonResponse instance for 204
     *
     * @return JsonResponse
     */
    public function noContentResponse(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    /**
     * Get a JsonResponse instance for ValidaitionExceptions
     *
     * @param  ValidationException  $exception
     * @param  string  $message
     * @return JsonResponse
     */
    public function validationErrorJsonResponse(
        ValidationException $exception,
        string $message = 'Invalid data sent'
    ): JsonResponse {
        return new JsonResponse(
            ['status' => self::STATUS_ERROR, 'message' => $message, 'details' => $exception->errors()],
            422
        );
    }

    /**
     * Get a JsonResponse instance for responses with status 'error'
     *
     * @param  string  $message
     * @param  int  $httpStatus
     * @return JsonResponse
     */
    public function errorJsonResponse(string $message, int $httpStatus = 400): JsonResponse
    {
        return new JsonResponse(['status' => self::STATUS_ERROR, 'message' => $message], $httpStatus);
    }

    /**
     * Get a JsonResponse instance for responses with status 'success'
     *
     * @param  string  $message
     * @return JsonResponse
     */
    public function successJsonResponse(string $message): JsonResponse
    {
        return new JsonResponse(['status' => self::STATUS_SUCCESS, 'message' => $message]);
    }

    /**
     * Get a JsonResponse instance for responses with status 'warning'
     *
     * @param  string  $message
     * @return JsonResponse
     */
    public function warningJsonResponse(string $message): JsonResponse
    {
        return new JsonResponse(['status' => self::STATUS_WARNING, 'message' => $message]);
    }

    /**
     * Get a JsonResponse instance for responses with status 'info'
     *
     * @param  string  $message
     * @return JsonResponse
     */
    public function infoJsonResponse(string $message): JsonResponse
    {
        return new JsonResponse(['status' => self::STATUS_INFO, 'message' => $message]);
    }
}
