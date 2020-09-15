<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property bool $active
*/
class Category extends ActiveRecord
{
   public static function tableName()
   {
       return '{{%categories}}';
   }

    public function rules()
    {
        return [
            [['name'], 'string', 'max'=>255, 'min'=>3],
            [['name'], 'trim'],
            [['name'], 'required'],
            [['name'], 'unique'],
            ['active', 'default', 'value'=>false],
            ['active', 'boolean'],
        ];
    }
}