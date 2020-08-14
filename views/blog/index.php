<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel common\models\BlogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Blogs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="blog-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Blog', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'title',
            // 'text:ntext',
            'url',
            'smallImage:image',
            'date_create',
            'date_update:datetime',
            ['attribute'=>'tags', 'value'=>'tagsAsString'],
            ['attribute'=>'status_id', 'filter'=>\common\modules\blog\models\Blog::STATUS_LIST,'value'=>'statusName'],
            // 'sort',

            [
                'class' => 'yii\grid\ActionColumn',
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
