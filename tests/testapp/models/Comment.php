<?php

namespace app\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $message
 * @property \app\models\User $user
 * @property \app\models\Post $post
*/
class Comment extends ActiveRecord
{
   public static function tableName()
   {
       return '{{%comments}}';
   }

   public function behaviors()
   {
       return [
           'blame'=>[
               'class'=>BlameableBehavior::class,
               'createdByAttribute'=>'user_id',
               'updatedByAttribute'=>false,
               'defaultValue'=>1
           ],
           'stamp'=>[
               'class'=>TimestampBehavior::class,
               'updatedAtAttribute'=>false,
               'value'=> new Expression('NOW()')
           ]
       ];
   }

    public function rules()
    {
        return [
            [['message'], 'string'],
            [['message'], 'trim'],
            [['message'], 'required'],
            [['post_id'],'integer'],
            [['post_id'],'exist', 'targetRelation'=>'post'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['user_id' => 'id']);
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['post_id'=>'id']);
    }
}