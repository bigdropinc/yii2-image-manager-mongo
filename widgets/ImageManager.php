<?php

namespace noam148\imagemanager\widgets;

use Yii;
use noam148\imagemanager\assets\ImageManagerModuleAsset;
use yii\base\Widget;
use yii\bootstrap\BootstrapAsset;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Class ImageManager
 *
 * @package backend\widgets
 */
class ImageManager extends Widget
{
    public $selectType;

    public $viewMode;

    public $multiple;

    public function run()
    {
        ImageManagerModuleAsset::register($this->view);

        if ($this->viewMode == "iframe") {
            if ($cssFiles = \Yii::$app->controller->module->cssFiles) {
                foreach ($cssFiles as $cssFile) {
                    $this->view->registerCssFile($cssFile, ['depends' => BootstrapAsset::className()]);
                }
            }
        }

        $request = Yii::$app->request;

        $this->view->registerJs("imageManagerModule.baseUrl = '" . Url::to(['/imagemanager/manager']) . "';", 3);
        $this->view->registerJs("imageManagerModule.defaultImageId = '" . $request->get("image-id") . "';", 3);
        $this->view->registerJs("imageManagerModule.fieldId = '" . $request->get("input-id") . "';", 3);
        $this->view->registerJs("imageManagerModule.cropRatio = '" . $request->get("aspect-ratio") . "';", 3);
        $this->view->registerJs("imageManagerModule.cropViewMode = '" . $request->get("crop-view-mode", 1) . "';", 3);
        $this->view->registerJs("imageManagerModule.selectType = '" . $this->selectType . "';", 3);
        $this->view->registerJs("imageManagerModule.multiple = " . ($this->multiple ? 'true' : 'false') . ";", 3);
        $this->view->registerJs("imageManagerModule.message = " . Json::encode([
                'deleteMessage' => Yii::t('imagemanager', 'Are you sure you want to delete this image?'),
            ]) . ";", 3);
    }
}
