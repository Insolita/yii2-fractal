<?php

use app\models\User;
use app\models\Post;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%comments}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%users}}`
 * - `{{%posts}}`
 */
class m200909_044342_create_comments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%comments}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'post_id' => $this->integer()->notNull(),
            'message' => $this->text(),
            'created_at' => $this->timestamp(),
        ]);

        // creates index for column `user_id`
        $this->createIndex(
            '{{%idx-comments-user_id}}',
            '{{%comments}}',
            'user_id'
        );

        // add foreign key for table `{{%users}}`
        $this->addForeignKey(
            '{{%fk-comments-user_id}}',
            '{{%comments}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );

        // creates index for column `post_id`
        $this->createIndex(
            '{{%idx-comments-post_id}}',
            '{{%comments}}',
            'post_id'
        );

        // add foreign key for table `{{%posts}}`
        $this->addForeignKey(
            '{{%fk-comments-post_id}}',
            '{{%comments}}',
            'post_id',
            '{{%posts}}',
            'id',
            'CASCADE'
        );
        $this->seed();
    }

    private function seed()
    {
        $faker = Faker\Factory::create('en_US');
        $users = User::find()->where(['!=', 'username', 'Alpha'])->all();
        $posts = Post::find()->where(['>', 'id', 5])->all();
        foreach ($posts as $post){
            foreach ($users as $user){
                if($user->id === $post->author_id){
                    continue;
                }
                $this->getDb()->createCommand()->insert('comments', [
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'message' => $faker->paragraphs(3, true),
                    'created_at' => $faker->dateTimeThisDecade->format('Y-m-d H:i:s'),
                ])->execute();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%users}}`
        $this->dropForeignKey(
            '{{%fk-comments-user_id}}',
            '{{%comments}}'
        );

        // drops index for column `user_id`
        $this->dropIndex(
            '{{%idx-comments-user_id}}',
            '{{%comments}}'
        );

        // drops foreign key for table `{{%posts}}`
        $this->dropForeignKey(
            '{{%fk-comments-post_id}}',
            '{{%comments}}'
        );

        // drops index for column `post_id`
        $this->dropIndex(
            '{{%idx-comments-post_id}}',
            '{{%comments}}'
        );

        $this->dropTable('{{%comments}}');
    }
}
