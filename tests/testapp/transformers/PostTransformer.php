<?php


namespace app\transformers;

use app\models\Comment;
use app\models\Post;
use app\models\User;
use League\Fractal\ParamBag;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

class PostTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'author', 'category', 'comments'
    ];

    public function transform(Post $post)
    {
        return $post->getAttributes(null, ['publish_date']);
    }

    public function includeAuthor(Post $post): Item
    {
        $author = $post->author;

        return $this->item($author, function (User $author) {
            return $author->getAttributes(['id', 'username', 'email', 'created_at'])
                + ['links'=>['self'=>'/users/'.$author->id]];
        }) ;
    }


    public function includeCategory(Post $post):Item
    {
        $category = $post->category;
        return $this->item($category, new CategoryTransformer(), 'category');
    }


    public function includeComments(Post $post, ParamBag $params = null):Collection
    {
        $comments = $post->comments;
        return $this->collection($comments, function (Comment $comment) {
            return $comment->getAttributes();
        }, 'comments');
    }
}
