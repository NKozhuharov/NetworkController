<?php

namespace Nevestul4o\NetworkController\Exceptions;

use Exception;

/**
 * Generic exception, used to return something in the API
 */
class ErrorResponseException extends Exception
{
    protected $message = 'Error';
}
