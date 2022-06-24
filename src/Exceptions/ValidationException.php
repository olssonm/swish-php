<?php

namespace Olssonm\Swish\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ValidationException extends RequestException
{
    private $errorCode;

    private $errorMessage;

    public function __construct(
        string $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        \Throwable $previous = null,
        array $handlerContext = []
    ) {

        $this->errorCode = json_decode((string) $response->getBody())->errorCode;
        $this->errorMessage = json_decode((string) $response->getBody())->errorMessage;

        parent::__construct($message, $request, $response, $previous, $handlerContext);
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
