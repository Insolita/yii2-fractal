<?php

namespace testapp\transformers;

use app\models\Comment;
use League\Fractal\TransformerAbstract;

class CommentTransformer extends TransformerAbstract
{
    public function transform(Comment $comment):array
    {
        return $comment->getAttributes();
    }
}