<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal;

use insolita\fractal\exceptions\ValidationException;
use Yii;
use yii\base\ErrorException;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\HttpException;
use yii\web\Response;
use const YII_DEBUG;

class JsonApiErrorHandler extends ErrorHandler
{
    public const ERROR_FORMAT_YII = 'yii';
    public const ERROR_FORMAT_SPEC = 'spec';
    public $errorAction;

    /**
     * The way for format validation errors
     * 'yii' will return
     *
     * ```json
     * {
     *   "errors": [
     *     {
     *       "status": 422,
     *       "detail": "Model validation failed.",
     *       "meta": {
     *         "attributeName1": ["error message1", ...],
     *         "attributeName2": ["error message1", ...],
     *       }
     *   }
     * ```
     *
     * 'attr' will return

     * ```json
     *     {
     *         'errors': [
     *                { 'attribute': 'attributeName', 'message': 'errorMessage1'},
     *                { 'attribute': 'attributeName', 'message': 'errorMessage2'},
     *                { 'attribute': 'attributeName2', 'message': 'errorMessage'},
     *          ]
     *     }
     * ```
     *
     * 'spec' will return accordingly json api example
     *  @see https://jsonapi.org/examples/#error-objects-multiple-errors
     *
     * ```json
     *     {
     *         'errors': [
     *                { 'status': '422', 'source': {'attribute': 'attributeName'}, 'detail': 'errorMessage1'},
     *                { 'status': '422', 'source': {'attribute': 'attributeName'}, 'detail': 'errorMessage2'},
     *                { 'status': '422', 'source': {'attribute': 'attributeName2'}, 'detail': 'errorMessage'},
     *          ]
     *     }
     * ```
     */
    public $validationErrorFormat = self::ERROR_FORMAT_YII;


    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            // reset parameters of response to avoid interference with partially created response data
            // in case the error occurred while sending the response.
            $response->isSent = false;
            $response->stream = null;
            $response->data = null;
            $response->content = null;
        } else {
            $response = new Response();
        }
        $response->setStatusCodeByException($exception);
        $response->format = Response::FORMAT_JSON;
        if ($this->errorAction !== null) {
            $result = Yii::$app->runAction($this->errorAction);
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response->data = $result;
            }
        } else {
            $response->data = $this->convertExceptionToJsonApi($exception);
        }
        $response->send();
    }

    private function convertExceptionToJsonApi($exception)
    {
        if (!YII_DEBUG && !$exception instanceof UserException && !$exception instanceof HttpException) {
            //@TODO: need to ensure
            $exception = new HttpException(500, Yii::t('yii', 'An internal server error occurred.'));
        }
        if ($exception instanceof ValidationException) {
            return $this->handleValidationErrors($exception->getErrors());
        }
        $error = new JsonApiError();
        if ($exception instanceof \Exception) {
            $error->code = $exception->getCode();
            $error->title = ($exception instanceof Exception || $exception instanceof ErrorException) ?
               $exception->getName() : 'Exception';
            $error->detail = $exception->getMessage();
        }
        if ($exception instanceof HttpException) {
            $error->status = $exception->statusCode;
        }
        if (!YII_DEBUG) {
            return $error->jsonSerialize();
        }
        $error->meta['error_type'] = get_class($exception);
        if (!$exception instanceof UserException) {
            $error->meta['error_file'] = $exception->getFile();
            $error->meta['error_line'] = $exception->getLine();
            if ($exception instanceof \yii\db\Exception) {
                $error->meta['error_info'] = $exception->errorInfo;
            } else {
                $error->meta['error_info'] = '';
            }
        }
        return $error->jsonSerialize();
    }

    private function handleValidationErrors(array $errors)
    {
        if ($this->validationErrorFormat === self::ERROR_FORMAT_YII) {
            return [
                'errors' => [
                    [
                        'status' => 422,
                        'detail' => 'Model validation failed.',
                        'meta' => $errors,
                    ],
                ]
            ];
        }
        $formattedErrors = [];
        foreach ($errors as $attr => $messages) {
            foreach ($messages as $message) {
                $error = ['status'=>422, 'source'=>['attribute'=>$attr], 'detail'=>$message];
                $formattedErrors[] = $error;
            }
        }
        return [
            'meta'=>['type'=>'Validation Errors'],
            'errors' => $formattedErrors,
        ];
    }
}
