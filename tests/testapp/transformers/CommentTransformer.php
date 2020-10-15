<?php

namespace app\transformers;

use app\models\Comment;
use League\Fractal\TransformerAbstract;

class CommentTransformer extends TransformerAbstract
{
    public $availableIncludes = ['post'];

    public function transform(Comment $comment):array
    {
        return $comment->getAttributes();
    }

    public function includePost(Comment $comment)
    {
        $transformer = new PostTransformer();
        return $this->item($comment->post, $transformer, 'posts');
    }
}