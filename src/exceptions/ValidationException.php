<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

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
