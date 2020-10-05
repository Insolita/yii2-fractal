<?php
use app\models\Comment;
use app\models\Post;
use insolita\fractal\RelationshipManager;
use yii\base\NotSupportedException;

class RelationsPatcherTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testPatchMultipleWithAppendData()
    {
        $model = Post::findOne(11);
        $comments = $model->getComments()->select('id')->column();
        $model2 = Post::findOne(12);
        $comments2 = $model2->getComments()->select('id')->column();
        verify($comments2)->notEmpty();
        verify($comments)->notEmpty();
        $newComments = array_merge($comments, $comments2);
        $service = new RelationshipManager($model, 'comments', array_map(function($id) {
            return ['id'=>$id];
        }, $newComments));
        $service->patch();
        $model->refresh();
        $model2->refresh();
        expect($model2->getComments()->count())->equals(0);
        expect($model->getComments()->count())->equals(count($newComments));
    }

    public function testPatchMultipleWithReplaceData()
    {
        $model = Post::findOne(11);
        $comments = $model->getComments()->select('id')->column();
        $model2 = Post::findOne(12);
        $comments2 = $model2->getComments()->select('id')->column();
        verify($comments2)->notEmpty();
        verify($comments)->notEmpty();
        $service = new RelationshipManager($model, 'comments', array_map(function($id) {
            return ['id'=>$id];
        }, $comments2));
        $service->patch();
        $model->refresh();
        $model2->refresh();
        expect($model2->getComments()->count())->equals(0);
        expect($model->getComments()->count())->equals(count($comments2));
        $newComments = $model->getComments()->select('id')->column();
        expect($newComments)->equals($comments2);
    }

    public function testDeleteUnlinkOnly()
    {
        $model = Post::findOne(11);
        $comments = $model->getComments()->select('id')->column();
        verify($comments)->notEmpty();
        verify($comments)->count(3);
        $service = new RelationshipManager($model, 'comments', [['id' => $comments[0]], ['id' => $comments[1]]]);
        $service->delete();
        $model->refresh();
        expect($model->getComments()->count())->equals(1);
        $deleted = Comment::findOne($comments[0]);
        expect($deleted)->isInstanceOf(Comment::class);
        expect($deleted->post_id)->null();
    }


    public function testDelete()
    {
        $model = Post::findOne(11);
        $comments = $model->getComments()->select('id')->column();
        verify($comments)->notEmpty();
        verify($comments)->count(3);
        $service = new RelationshipManager($model, 'comments', [['id' => $comments[0]], ['id' => $comments[1]]]);
        $service->delete(false);
        $model->refresh();
        expect($model->getComments()->count())->equals(1);
        $deleted = Comment::findOne($comments[0]);
        expect($deleted)->null();
    }
}