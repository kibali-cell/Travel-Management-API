<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $statusCode;
    protected $responseData;
    
    public function __construct(string $message, int $statusCode = 500, $responseData = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
    }
    
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    public function getResponseData()
    {
        return $this->responseData;
    }
}