<?php

namespace insolita\fractal;

use insolita\fractal\exceptions\NonJsonApiResponseException;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\ResponseFormatterInterface;
use const JSON_PRETTY_PRINT;

class JsonApiResponseFormatter extends Component implements ResponseFormatterInterface
{
    private const CONTENT_TYPE = 'application/vnd.api+json; charset=UTF-8';
    /**
     * @var int the encoding options passed to [[Json::encode()]]. For more details please refer to
     * <https://secure.php.net/manual/en/function.json-encode.php>.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     */
    public $encodeOptions = 320;

    /**
     * @var bool whether to format the output in a readable "pretty" format. This can be useful for debugging purpose.
     * If this is true, `JSON_PRETTY_PRINT` will be added to [[encodeOptions]].
     * Defaults to `false`.
     * This property has no effect, when [[useJsonp]] is `true`.
     * @since 2.0.7
     */
    public $prettyPrint = false;

    public function format($response)
    {
        $response->getHeaders()->set('Content-Type', self::CONTENT_TYPE);
        $options = $this->encodeOptions;
        if ($this->prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }
        if ($response->data !== null) {
            $data = $response->data;
            if (
                !isset($data['data'])
                && !isset($data['errors'])
                && !isset($data['meta'])
            ) {
                throw new NonJsonApiResponseException('Response data is not followed JsonApi spec');
            }
            $response->content = Json::encode($data, $options);
        } elseif ($response->content === null) {
            //$response->content = 'null';
            $response->content = Json::encode(['meta'=>['type'=>'Empty response']], $options);
        }
    }
}
