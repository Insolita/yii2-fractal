<?php

use app\models\User;
use app\models\Category;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%posts}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%categories}}`
 * - `{{%users}}`
 */
class m200909_043742_create_posts_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%posts}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'name' => $this->string()->unique(),
            'body' => $this->text(),
            'author_id' => $this->integer()->notNull(),
            'publish_date'=> $this->date()->null(),
        ]);

        // creates index for column `category_id`
        $this->createIndex(
            '{{%idx-posts-category_id}}',
            '{{%posts}}',
            'category_id'
        );

        // add foreign key for table `{{%categories}}`
        $this->addForeignKey(
            '{{%fk-posts-category_id}}',
            '{{%posts}}',
            'category_id',
            '{{%categories}}',
            'id',
            'CASCADE'
        );

        // creates index for column `author_id`
        $this->createIndex(
            '{{%idx-posts-author_id}}',
            '{{%posts}}',
            'author_id'
        );

        // add foreign key for table `{{%users}}`
        $this->addForeignKey(
            '{{%fk-posts-author_id}}',
            '{{%posts}}',
            'author_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );
        $this->seed();
    }

    protected function seed()
    {
        $categories = Category::find()->where(['!=', 'name', 'Banana'])->all();
        $faker = Faker\Factory::create('en_US');
        $users = User::find()->where(['!=', 'username', 'Beta'])->all();
        foreach ($users as $user){
            foreach ($categories as $category){
                for ($i = 0; $i<=5; $i++){
                    $this->getDb()->createCommand()->insert('posts', [
                        'category_id' => $category->id,
                        'name' => $faker->unique()->sentence(),
                        'body' => $faker->realText(500),
                        'author_id' => $user->id,
                        'publish_date'=> $faker->dateTimeInInterval('-1month', '+5days')->format('Y-m-d'),
                    ])->execute();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%categories}}`
        $this->dropForeignKey(
            '{{%fk-posts-category_id}}',
            '{{%posts}}'
        );

        // drops index for column `category_id`
        $this->dropIndex(
            '{{%idx-posts-category_id}}',
            '{{%posts}}'
        );

        // drops foreign key for table `{{%users}}`
        $this->dropForeignKey(
            '{{%fk-posts-author_id}}',
            '{{%posts}}'
        );

        // drops index for column `author_id`
        $this->dropIndex(
            '{{%idx-posts-author_id}}',
            '{{%posts}}'
        );

        $this->dropTable('{{%posts}}');
    }
}
