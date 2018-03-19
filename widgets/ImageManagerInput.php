<?php

namespace noam148\imagemanager\widgets;

use noam148\imagemanager\helpers\ImageHelper;
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

    /** @var array */
    public $additionalFields = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

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
        $this->registerClientScript();

        if ($this->multiple) {
            return $this->renderMultiplyInput();
        }

        return $this->renderSingeImageInput();
    }

    /**
     * @return null|string
     */
    protected function getFieldId()
    {
        return $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : null;
    }

    /**
     * @return mixed|null
     */
    protected function getImageId()
    {
        $fieldAttributeName = Html::getAttributeName($this->attribute);

        return $this->hasModel() ? $this->model->{$fieldAttributeName} : null;
    }

    /**
     * @return string
     */
    protected function getModalOpenBtn()
    {
        return Html::a('<i class="glyphicon glyphicon-folder-open" aria-hidden="true"></i>', '#', [
            'class' => 'pull-right input-group-addon btn btn-primary open-modal-imagemanager',
            'data-aspect-ratio' => $this->aspectRatio,
            'data-crop-view-mode' => $this->cropViewMode,
            'data-input-id' => $this->getFieldId(),
            'data-multiple' => $this->multiple ? 'true' : '',
        ]);
    }

    /**
     * @return string
     */
    protected function getDeleteImageBtn()
    {
        return Html::a('<i class="glyphicon glyphicon-remove" aria-hidden="true"></i>', '#', [
            'class' => 'input-group-addon btn btn-primary delete-selected-image ' . (!$this->getImageId() ? 'hide' : ''),
            'data-input-id' => $this->getFieldId(),
            'data-show-delete-confirm' => $this->showDeletePickedImageConfirm ? 'true' : 'false',
        ]);
    }

    /**
     * @return string
     */
    protected function renderSingeImageInput()
    {
        if ($this->hasModel()) {
            $model = ImageManager::findOne($this->getImageId());

            $input = Html::textInput($this->attribute, $model ? $model->fileName : null, [
                'class' => 'form-control',
                'id' => $this->getFieldId() . '_name',
                'readonly' => true
            ]);
            $input .= Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        } else {
            $input = Html::textInput($this->name . '_name', null, ['readonly' => true]);
            $input .= Html::hiddenInput($this->name, $this->value, $this->options);
        }

        $field = Html::tag('div', $input . $this->getDeleteImageBtn() . $this->getModalOpenBtn(), ['class' => 'input-group']);

        if ($this->showPreview) {
            $imageSource = isset($model)
                ? \Yii::$app->imagemanager->getImagePath($model->id, 500, 500, 'inset')
                : $this->previewImageUrl;

            $field .= Html::tag('div', Html::img($imageSource, [
                'id' => $this->getFieldId() . '_image',
                'alt' => 'Thumbnail',
                'class' => 'img-responsive img-preview',
            ]), ['class' => 'image-wrapper ' . (!isset($model) && !$this->previewImageUrl ? 'hide' : '')]);
        }

        return Html::tag('div', $field, ['class' => 'image-manager-input']);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function renderMultiplyInput()
    {
        if (!$this->models) {
            $this->models = ImageHelper::getModels($this->model->{$this->attribute});
        }

        $i = 1;
        return $this->getModalOpenBtn() . MultipleInput::widget([
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
                'columns' => array_merge([
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
                ], $this->additionalFields),
            ]);
    }

    /**
     * Registers js Input
     *
     * @return void
     */
    public function registerClientScript()
    {
        ImageManagerInputAsset::register($this->view);

        $this->view->registerJs("imageManagerInput.baseUrl = '" . Url::to(['/imagemanager/manager']) . "';");
        $this->view->registerJs("imageManagerInput.message = " . Json::encode([
                'imageManager' => Yii::t('imagemanager','Image manager'),
                'detachWarningMessage' => Yii::t('imagemanager', 'Are you sure you want to detach the image?'),
            ]) . ";");
    }
}
