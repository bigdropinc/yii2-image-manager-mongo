<?php

namespace noam148\imagemanager\behaviors;

use noam148\imagemanager\helpers\ImageHelper;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class ThumbsBehavior
 *
 * @package noam148\imagemanager\behaviors
 *
 * @property BaseActiveRecord $owner
 */
class ThumbsBehavior extends Behavior
{
    public $sizes = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'onBeforeSave',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'onBeforeSave',
        ];
    }

    public function onBeforeSave()
    {
        foreach ($this->sizes as $size) {
            $attribute = $size['attribute'];
            if ($attribute = $this->owner->$attribute) {
                if (is_string($attribute)) {
                    $attribute = ImageHelper::getModelFromUrl($attribute);
                }

                if (!$attribute) continue;

                if (!is_array($attribute)) {
                    $attribute = [$attribute];
                }
                
                foreach ($attribute as $model) {
                    $sizeName = ImageHelper::getSizeName($size['width'], $size['height']);

                    if (in_array($sizeName, (array) $model->sizes)) continue;

                    $path = ImageHelper::getPathByUrl(ImageHelper::getThumbByUrl($model, $size['width'], $size['height']));

                    \Yii::$app->imagemanager->s3->put(ImageHelper::getFileName($model), \Yii::getAlias($path), $sizeName);

                    $model->updateAttributes(['sizes' => ArrayHelper::merge((array) $model->sizes, [$sizeName])]);
                }
            }
        }
    }
}
