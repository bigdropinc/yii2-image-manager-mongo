<?php

namespace noam148\imagemanager\widgets;

use unclead\multipleinput\MultipleInput;
use Yii;
use yii\widgets\InputWidget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use noam148\imagemanager\models\ImageManager;
use noam148\imagemanager\assets\ImageManagerInputAsset;

class ImageManagerInput extends InputWidget
{
    /**
     * @var null|integer The aspect ratio the image needs to be cropped in (optional)
     */
    public $aspectRatio = null; //option info: https://github.com/fengyuanchen/cropper/#aspectratio

    /**
     * @var int Define the viewMode of the cropper
     */
    public $cropViewMode = 1; //option info: https://github.com/fengyuanchen/cropper/#viewmode

    /**
     * @var bool Show a preview of the image under the input
     */
    public $showPreview = true;

    /**
     * @var bool Show a confirmation message when de-linking a image from the input
     */
    public $showDeletePickedImageConfirm = false;

    /** @var bool */
    public $multiple = false;

    /** @var string */
    public $previewImageUrl;

    /** @var array */
    public $models;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        //set language
        if (!isset(Yii::$app->i18n->translations['imagemanager'])) {
            Yii::$app->i18n->translations['imagemanager'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en',
                'basePath' => '@noam148/imagemanager/messages'
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        //default
        $ImageManager_id = null;
        $mImageManager = null;
        $sFieldId = null;
        //start input group
        $field = "<div class='image-manager-input'>";
        $field .= "<div class='input-group'>";
        //set input fields
        if ($this->hasModel()) {
            //get field id
            $sFieldId = Html::getInputId($this->model, $this->attribute);
            $sFieldNameId = $sFieldId . "_name";
            //get attribute name
            $sFieldAttributeName = Html::getAttributeName($this->attribute);
            //get filename from selected file
            $ImageManager_id = $this->model->{$sFieldAttributeName};
            $mImageManager = ImageManager::findOne($ImageManager_id);

            $ImageManager_fileName = $mImageManager ? $mImageManager->fileName : null;
            //create field
            $field .= Html::textInput($this->attribute, $ImageManager_fileName, ['class' => 'form-control', 'id' => $sFieldNameId, 'readonly' => true]);
            $field .= Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        } else {
            $field .= Html::textInput($this->name . "_name", null, ['readonly' => true]);
            $field .= Html::hiddenInput($this->name, $this->value, $this->options);
        }
        //end input group
        $sHideClass = $ImageManager_id === null ? 'hide' : '';
        $field .= "<a href='#' class='input-group-addon btn btn-primary delete-selected-image " . $sHideClass . "' data-input-id='" . $sFieldId . "' data-show-delete-confirm='" . ($this->showDeletePickedImageConfirm ? "true" : "false") . "'><i class='glyphicon glyphicon-remove' aria-hidden='true'></i></a>";
        $field .= "<a href='#' class='input-group-addon btn btn-primary open-modal-imagemanager' data-aspect-ratio='" . $this->aspectRatio . "' data-crop-view-mode='" . $this->cropViewMode . "' data-input-id='" . $sFieldId . "' data-multiple='" . $this->multiple . "''>";
        $field .= "<i class='glyphicon glyphicon-folder-open' aria-hidden='true'></i>";
        $field .= "</a></div>";

        $this->registerClientScript();

        if ($this->multiple) {
            echo "<a href='#' class='pull-right input-group-addon btn btn-primary open-modal-imagemanager' data-aspect-ratio='" . $this->aspectRatio . "' data-crop-view-mode='" . $this->cropViewMode . "' data-input-id='" . $sFieldId . "' data-multiple='" . $this->multiple . "''><i class='glyphicon glyphicon-folder-open' aria-hidden='true'></i></a>";

            if (!$this->models) {
                $ids = $this->model->{$this->attribute};

                $images = $ids ? ImageManager::find()
                    ->where(['_id' => ['$in' => $ids]])
                    ->all() : [];

                $models = [];
                foreach ($images as $image) {
                    $models[array_search($image->id, $ids)] = $image;
                }

                ksort($models);

                $this->models = $models;
            }

            $i = 1;
            echo MultipleInput::widget([
                'data' => array_map(function ($model) use (&$i) {
                    /** @var ImageManager $model */
                    return [
                        'id' => $model->id,
                        'name' => $model->fileName,
                        'image' => \Yii::$app->imagemanager->getImagePath($model->id, 200, 200, "inset", true),
                        'order' => $i++,
                    ];
                }, $this->models),
                'model' => $this->model,
                'attribute' => $this->attribute,
                'allowEmptyList'    => true,
                'enableGuessTitle'  => true,
                'addButtonPosition' => MultipleInput::POS_FOOTER,
                'addButtonOptions' => [
                    'class' => 'hidden',
                ],
                'columns' => [
                    [
                        'name' => 'id',
                        'title' => 'Image',
                        'type' => 'hiddenInput',
                        'options' => [
                            'class' => 'image-id',
                        ],
                    ],
                    [
                        'name' => 'name',
                        'title' => 'Name',
                        'type' => 'static',
                        'value' => function ($data) {
                            return Html::tag('p', $data['name'], [
                                'class' => 'image-name',
                            ]);
                        },
                    ],
                    [
                        'name' => 'image',
                        'title' => 'Image',
                        'type' => 'static',
                        'value' => function ($data) {
                            return Html::img($data['image']);
                        },
                    ],
                    [
                        'name' => 'order',
                        'title' => 'Order',
                        'options' => [
                            'class' => 'image-order',
                            'type' => 'number',
                            'min' => 1,
                        ],
                    ],
                ],
            ]);
            return;
        }

        //show preview if is true
        if ($this->showPreview == true) {
            $sHideClass = ($mImageManager == null) ? "hide" : "";
            $sImageSource = isset($mImageManager->id)
                ? \Yii::$app->imagemanager->getImagePath($mImageManager->id, 500, 500, 'inset')
                : $this->previewImageUrl;

            $field .= '<div class="image-wrapper ' . $sHideClass . '">'
                . '<img id="' . $sFieldId . '_image" alt="Thumbnail" class="img-responsive img-preview" src="' . $sImageSource . '">'
                . '</div>';
        }

        //close image-manager-input div
        $field .= "</div>";

        echo $field;
    }

    /**
     * Registers js Input
     */
    public function registerClientScript() {
        $view = $this->getView();
        ImageManagerInputAsset::register($view);

        //set baseUrl from image manager
        $sBaseUrl = Url::to(['/imagemanager/manager']);
        //set base url
        $view->registerJs("imageManagerInput.baseUrl = '" . $sBaseUrl . "';");
        $view->registerJs("imageManagerInput.message = " . Json::encode([
                    'imageManager' => Yii::t('imagemanager','Image manager'),
                    'detachWarningMessage' => Yii::t('imagemanager', 'Are you sure you want to detach the image?'),
                ]) . ";");
    }
}
