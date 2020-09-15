<?php

namespace insolita\fractal\providers;

use League\Fractal\Resource\ResourceInterface;

interface JsonApiDataProviderInterface
{
    public function toCollection():ResourceInterface;
}
