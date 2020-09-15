<?php

namespace app\transformers;

use app\models\Comment;
use app\models\User;
use League\Fractal\TransformerAbstract;
use yii\helpers\Url;

class UserExtendTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     * @var array
     */
    protected $availableIncludes = ['comments'];

    /**
     * Include resources without needing it to be requested.
     * @var array
     */
    protected $defaultIncludes = ['posts'];

    public function transform(User $user)
    {
        return $user->getAttributes(['id', 'username', 'email', 'created_at']) +
            [
                'links' => [
                    'self' => Url::toRoute('/me/info', true),
                    'details' => Url::toRoute('/me/details', true),
                ],
            ];
    }

    public function includePosts(User $user)
    {
        $transformer = new PostShortTransformer();
        $transformer->setAvailableIncludes(['author']);
        return $this->collection($user->posts, $transformer, 'posts');
    }

    public function includeComments(User $user)
    {
        $comments = $user->comments;
        return $this->collection($comments,
            function(Comment $comment) {
                return $comment->getAttributes() + [
                        'links' => [
                            'self' => '/posts/' . $comment->post_id . '/comments/' . $comment->id,
                        ],
                    ];
            },
            'comments');
    }
}