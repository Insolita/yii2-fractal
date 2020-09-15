<?php

namespace app\transformers;

use app\models\User;
use League\Fractal\TransformerAbstract;
use yii\helpers\Url;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return $user->getAttributes(['id', 'username', 'email']) + [
                'links' => [
                    'self' => Url::toRoute('/me/info', true),
                    'details' => Url::toRoute('/me/details', true),
                ],
            ];
    }
}