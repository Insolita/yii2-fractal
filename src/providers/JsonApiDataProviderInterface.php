<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\providers;

use League\Fractal\Resource\ResourceInterface;

interface JsonApiDataProviderInterface
{
    public function toCollection():ResourceInterface;
}
