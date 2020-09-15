<?php

namespace insolita\fractal;

use JsonSerializable;
use yii\base\BaseObject;

class JsonApiError extends BaseObject implements JsonSerializable
{
    public $id;
    public $status;
    public $code;
    public $title;
    public $details;
    public $links;
    public $source;
    public $meta = [];

    public function jsonSerialize()
    {
        $base = [
            'code'=>$this->code,
            'title'=>$this->title
        ];
        if ($this->id) {
            $base['id'] = $this->id;
        }
        if ($this->status) {
            $base['status'] = $this->status;
        }
        if ($this->details) {
            $base['details'] = $this->details;
        }
        if ($this->links) {
            $base['links'] = $this->links;
        }
        if ($this->source) {
            $base['source'] = $this->source;
        }
        if (!empty($this->meta && \is_array($this->meta))) {
            return ['errors' => [$base], 'meta'=>$this->meta];
        }
        return ['errors' => [$base]];
    }
}
