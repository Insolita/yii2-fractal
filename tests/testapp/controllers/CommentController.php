<?php

namespace app\controllers;

use app\models\Comment;
use insolita\fractal\ActiveJsonApiController;
use app\transformers\CommentTransformer;

class CommentController extends ActiveJsonApiController
{
    public $modelClass = Comment::class;
    public $transformer = CommentTransformer::class;
    public $resourceKey = 'comments';
}