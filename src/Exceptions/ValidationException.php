<?php

namespace Olssonm\Swish\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Olssonm\Swish\Error;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ValidationException extends RequestException
{
    private array $errors = [];

    public function __construct(
        string $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        \Throwable $previous = null,
        array $handlerContext = []
    ) {

        $data = json_decode((string) $response->getBody()->getContents());

        if (is_array($data)) {
            foreach ($data as $error) {
                $this->errors[] = new Error((array) $error);
            }
        } else {
            $this->errors[] = new Error([
                'errorCode' => $data->errorCode,
                'errorMessage' => $data->errorMessage,
                'additionalInformation' => $data->additionalInformation ?? null,
            ]);
        }

        parent::__construct($message, $request, $response, $previous, $handlerContext);
    }

    public function getErrors(): array
    {

        return $this->errors;
    }
}
