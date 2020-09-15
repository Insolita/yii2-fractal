<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%categories}}`.
 */
class m200909_043417_create_categories_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%categories}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->unique(),
            'active' => $this->boolean()->defaultValue(false),
        ]);

        $this->seed();
    }

    private function seed()
    {
        $faker = Faker\Factory::create('en_US');
        foreach (['Apple', 'Banana', 'Orange', 'Strawberry'] as $name)
        {
            $this->getDb()->createCommand()->insert('categories', [
                'name'=>$name,
                'active'=>$faker->boolean(75)
            ])->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%categories}}');
    }
}
