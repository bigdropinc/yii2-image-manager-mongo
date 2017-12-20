<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use kartik\file\FileInput;
use yii\data\ActiveDataProvider;
use yii\bootstrap\Modal;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;

/**
 * @var string $selectType
 * @var string $viewMode
 * @var ActiveDataProvider $dataProvider
 * @var bool $canUploadImage
 * @var bool $allowedFileExtensions
 * @var bool $canRemoveImage
 */

$this->title = Yii::t('imagemanager','Image manager');
?>

<div id="module-imagemanager" class="container-fluid <?= $selectType ?>">
    <div class="row">
        <div class="col-xs-6 col-sm-10 col-image-editor">
            <div class="image-cropper">
                <div class="image-wrapper">
                    <img src="" id="image-cropper">
                </div>
                <div class="action-buttons">
                    <a href="#" class="btn btn-primary apply-crop">
                        <i class="fa fa-crop"></i>
                        <span class="hidden-xs"><?=Yii::t('imagemanager','Crop')?></span>
                    </a>
                    <?php if ($viewMode === "iframe"): ?>
                        <a href="#" class="btn btn-primary apply-crop-select">
                            <i class="fa fa-crop"></i>
                            <span class="hidden-xs"><?=Yii::t('imagemanager','Crop and select')?></span>
                        </a>
                    <?php endif; ?>
                    <a href="#" class="btn btn-default cancel-crop">
                        <i class="fa fa-undo"></i>
                        <span class="hidden-xs"><?=Yii::t('imagemanager','Cancel')?></span>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xs-6 col-sm-10 col-overview">
            <?php Pjax::begin([
                'id'=>'pjax-mediamanager',
                'timeout'=>'5000'
            ]); ?>
            <?= ListView::widget([
                'dataProvider' => $dataProvider,
                'itemOptions' => ['class' => 'item img-thumbnail'],
                'layout' => "<div class='item-overview'>{items}</div> {pager}",
                'itemView' => function ($model) {
                    return $this->render("@noam148/imagemanager/views/manager/_item", ['model' => $model]);
                },
            ]) ?>
            <?php Pjax::end(); ?>
        </div>
        <div class="col-xs-6 col-sm-2 col-options">
            <div class="form-group">
                <?= Html::textInput('input-mediamanager-search', null, ['id'=>'input-mediamanager-search', 'class'=>'form-control', 'placeholder'=>Yii::t('imagemanager','Search').'...'])?>
            </div>

            <?php if ($canUploadImage):?>

                <?php Modal::begin([
                    'header'=>'File Input inside Modal',
                    'toggleButton' => [
                        'label'=>'Upload', 'class'=>'btn btn-primary btn-block'
                    ],
                    'options' => ['tabindex' => null]
                ]); ?>

                <?php $form = ActiveForm::begin([
                    'action' => Url::to(['manager/upload']),
                    'options'=>['enctype'=>'multipart/form-data']
                ]); ?>

                <div class="form-group">
                    <?= Html::label('Tags', 'tags') ?>
                    <?= Select2::widget([
                        'name' => 'tags',
                        'theme' => Select2::THEME_BOOTSTRAP,
                        'options' => [
                            'placeholder' => 'Enter tags...',
                            'id' => 'w' . $form->getId(),
                        ],
                        'pluginOptions' => [
                            'tags' => true,
                            'multiple' => true,
                        ],
                    ]) ?>
                </div>

                <?= FileInput::widget([
                'name' => 'imagemanagerFiles[]',
                'id' => 'imagemanager-files',
                'options' => [
                    'multiple' => true,
                    'accept' => 'image/*'
                ],
                'pluginOptions' => [
//                        'uploadUrl' => Url::to(['manager/upload']),
                    'allowedFileExtensions' => $allowedFileExtensions,
//                        'uploadAsync' => false,
//                        'showPreview' => false,
                    'showRemove' => false,
                    'showUpload' => false,
                    'showCaption' => false,
                    'showCancel' => false,
                    'browseClass' => 'btn btn-primary btn-block',
                    'browseIcon' => '<i class="fa fa-upload"></i> ',
                    'browseLabel' => 'Select photos',
                ],
//                    'pluginEvents' => [
//                        "filebatchselected" => "function(event, files){  $('.msg-invalid-file-extension').addClass('hide'); $(this).fileinput('upload'); }",
//                        "filebatchuploadsuccess" => "function(event, data, previewId, index) {
//						imageManagerModule.uploadSuccess(data.jqXHR.responseJSON.imagemanagerFiles);
//					}",
//                        "fileuploaderror" => "function(event, data) { $('.msg-invalid-file-extension').removeClass('hide'); }",
//                    ],
            ]) ?>
                <br>
                <?= Html::submitButton('Upload', ['class' => 'btn btn-primary btn-block']) ?>

                <?php ActiveForm::end() ?>
                <?php Modal::end() ?>

            <?php endif; ?>

            <div class="image-info hide">
                <div class="thumbnail">
                    <img src="#">
                </div>
                <div class="edit-buttons">
                    <a href="#" class="btn btn-primary btn-block crop-image-item">
                        <i class="fa fa-crop"></i>
                        <span class="hidden-xs"><?=Yii::t('imagemanager','Crop')?></span>
                    </a>
                </div>
                <div class="details">
                    <div class="form-group">
                        <label>Link for image</label> <button class="btn copy-link">Copy</button>
                        <input title="" type="text" class="form-control image-link" readonly>
                    </div>
                    <div class="fileName"></div>
                    <div class="tags"></div>
                    <div class="created"></div>
                    <div class="fileSize"></div>
                    <div class="dimensions"><span class="dimension-width"></span> &times; <span class="dimension-height"></span></div>
                    <?php if ($canRemoveImage): ?>
                        <a href="#" class="btn btn-xs btn-danger delete-image-item" ><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> <?=Yii::t('imagemanager','Delete')?></a>
                    <?php endif; ?>
                </div>
                <?php if ($viewMode === "iframe"): ?>
                    <a href="#" class="btn btn-primary btn-block pick-image-item"><?=Yii::t('imagemanager','Select')?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>