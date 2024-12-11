<?php

namespace Olssonm\Swish\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Olssonm\Swish\Error;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ValidationException extends RequestException
{
    /**
     * @var array<Error>
     */
    private array $errors = [];

    /**
     * Undocumented function
     *
     * @param array<mixed> $handlerContext
     */
    public function __construct(
        string $message,
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $previous = null,
        array $handlerContext = []
    ) {

        $data = json_decode((string) $response?->getBody()->getContents());

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

    /**
     * @return array<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
