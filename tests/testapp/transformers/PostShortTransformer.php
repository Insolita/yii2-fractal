<?php

namespace app\transformers;

use app\models\Comment;
use app\models\Post;
use app\models\User;
use League\Fractal\ParamBag;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

class PostShortTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'author', 'category'
    ];

    public function transform(Post $post)
    {
        return $post->getAttributes(['id','category_id','author_id', 'name']);
    }

    public function includeAuthor(Post $post): Item
    {
        $author = $post->author;

        return $this->item($author, function(User $author){
            return $author->getAttributes(['id', 'username', 'email', 'created_at']);
        }, 'users') + ['links'=>['self'=>'/users/'.$author->id]];
    }


    public function includeCategory(Post $post):Item
    {
        $category = $post->category;
        return $this->item($category, new CategoryTransformer(), 'category');
    }
}