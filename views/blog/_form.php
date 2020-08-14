<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use vova07\imperavi\Widget;
use kartik\select2\Select2;
use kartik\file\FileInput;

/* @var $this yii\web\View */
/* @var $model common\models\Blog */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="blog-form">

    <?php $form = ActiveForm::begin([
        // 'id' => 'w0',
        'options'=>['enctype'=>'multipart/form-data']
    ]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
<div class="row">
    
    <?= $form->field($model, 'text', ['options'=>['class'=>'col-xs-6']])->widget(Widget::className(), [
            'settings' => [
                'lang' => 'en',
                'minHeight' => 200,
                'imageUpload' => Url::to(['/blog/blog/image-upload', 'sub'=>'blog']),
                'plugins' => [
                    'clips',
                    'fullscreen',
                ],
                'clips' => [
                    ['Lorem ipsum...', 'Lorem...'],
                    ['red', '<span class="label-red">red</span>'],
                    ['green', '<span class="label-green">green</span>'],
                    ['blue', '<span class="label-blue">blue</span>'],
                ],
            ],
        ]);
     ?>
    <?= $form->field($model, 'file', ['options'=>['class'=>'col-xs-6']])->widget(FileInput::classname(), [
            'options' => ['accept' => 'image/*'],
            'pluginOptions' => [
                'showCaption' => false,
                'showRemove' => true,
                'showUpload' => false,
                'browseClass' => 'btn btn-primary btn-block',
                'browseIcon' => '<i class="glyphicon glyphicon-camera"></i> ',
                'browseLabel' =>  'Select Photo'
            ],
        ]) ?>
</div>
    <?= $form->field($model, 'url', ['options'=>['class'=>'col-xs-6']])->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status_id', ['options'=>['class'=>'col-xs-6']])->dropDownList(\common\modules\blog\models\Blog::STATUS_LIST) ?>

    <?= $form->field($model, 'sort', ['options'=>['class'=>'col-xs-6']])->textInput() ?>
    <?=
         $form->field($model, 'tags_array', ['options'=>['class'=>'col-xs-6']])->widget(Select2::classname(), [
                'data' => $data,
                'value' => $model->tags,
                'language' => 'en',
                'options' => ['placeholder' => 'Select a state ...', 'multiple' => true],
                'pluginOptions' => [
                    'allowClear' => true,
                    'tags' => true,
                    'maximumInputLength' => 10,
                ],
            ]);

    ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
    <?= FileInput::widget([
            'name' => 'ImageManager[attachment]',
            'options' => ['multiple'=>true],
            'pluginOptions' => [
                'deleteUrl' => Url::toRoute(['/blog/blog/delete-image']),
                'initialPreview' => $model->imageLinks,
                'initialPreviewAsData' => true,
                'overwriteInitial' => false,
                'initialPreviewConfig' => $model->imageLinksData,
                'uploadUrl' => Url::to(['/blog/blog/save-image']),
                'uploadExtraData' => [
                    'ImageManager[class]' => $model->formName(),
                    'ImageManager[item_id]' => $model->id
                ],
                'maxFileCount' => 10,
            ],
            'pluginEvents' => [
                'filesorted' => new \yii\web\JsExpression(
                    'function(event, params){
                    $.post("'.Url::toRoute(["/blog/blog/sort-image", "id" => $model->id]).'", {sort: params})
                }'),
            ]
        ]) ?>
</div>
