<?php

namespace insolita\fractal\exceptions;

use Throwable;
use yii\web\HttpException;

class ValidationException extends HttpException
{
    protected $errors;

    public function __construct(array $errors, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct(422, $message, $code, $previous);
    }

    public function getErrors():array
    {
        return $this->errors;
    }
}
