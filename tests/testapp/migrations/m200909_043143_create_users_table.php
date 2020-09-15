<?php
use yii\db\Migration;

/**
 * Handles the creation of table `{{%users}}`.
 */
class m200909_043143_create_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->unique(),
            'email' => $this->string()->unique(),
            'password_hash' => $this->string(),
            'auth_key' => $this->string(),
            'created_at' => $this->timestamp(),
            'updated_at' => $this->timestamp()->null()->defaultValue(null),
        ]);

        $this->seed();

    }

    private function seed()
    {
        $faker = Faker\Factory::create('en_US');
        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $name)
        {
            $this->getDb()->createCommand()->insert('users', [
                'username'=>$name,
                'email'=>$name.'@mail.com',
                'password_hash'=>Yii::$app->security->generatePasswordHash("{$name}_secret"),
                'auth_key'=>$name.'_auth',
                'created_at'=>$faker->dateTimeThisDecade('now', 'UTC')->format('Y-m-d H:i:s')
            ])->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%users}}');
    }
}
