<?php

namespace testapp\controllers;

use app\models\Comment;
use insolita\fractal\ActiveJsonApiController;
use testapp\transformers\CommentTransformer;

class CommentController extends ActiveJsonApiController
{
    public $modelClass = Comment::class;
    public $transformer = CommentTransformer::class;
    public $resourceKey = 'comments';
}