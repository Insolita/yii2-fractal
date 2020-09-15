<?php

namespace app\models;

use app\models\User;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $category_id
 * @property int $author_id
 * @property string $name
 * @property string $publish_date
 * @property string $body
 * @property \app\models\User $author
 * @property \app\models\Category $category
 * @property array|\app\models\Comment[] $comments
*/
class Post extends ActiveRecord
{
   public static function tableName()
   {
       return '{{%posts}}';
   }

   public function behaviors()
   {
       return [
           'blame'=>[
               'class'=>BlameableBehavior::class,
               'createdByAttribute'=>'author_id',
               'updatedByAttribute'=>false,
               'defaultValue'=>1
           ]
       ];
   }

    public function rules()
    {
        return [
            [['name'], 'string', 'max'=>255, 'min'=>3],
            [['body'], 'string'],
            [['name', 'body'], 'trim'],
            [['name', 'body', 'category_id'], 'required'],
            [['name'], 'unique'],
            [['category_id'],'integer'],
            [['category_id'],'exist', 'targetRelation'=>'category'],
            ['publish_date', 'date', 'format'=>'php:Y-m-d','skipOnEmpty'=>true],
        ];
    }

    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'author_id']);
    }

    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id'=>'category_id']);
    }

    public function getComments()
    {
        return $this->hasMany(Comment::class, ['post_id' => 'id']);
    }
}