<?php

namespace noam148\imagemanager\helpers;

use noam148\imagemanager\models\ImageManager;
use yii\helpers\Url;

/**
 * Class Image
 *
 * @package common\helpers
 */
class ImageHelper
{
    /**
     * @param ImageManager $model
     * @return string
     */
    public static function getDir($model)
    {
        return substr($model->fileHash, 0, 3);
    }

    /**
     * @param ImageManager $model
     * @return string
     */
    public static function getPathToFile($model)
    {
        return sprintf('%s/%s', \Yii::$app->imagemanager->mediaPath, self::getDir($model));
    }

    /**
     * @param ImageManager $model
     * @return string
     */
    public static function getFilePath($model)
    {
        return sprintf('%s/%s', self::getPathToFile($model), self::getFileName($model));
    }

    /**
     * @param ImageManager $model
     * @param bool $absolute
     * @return string
     */
    public static function getImageUrl($model, $absolute = true)
    {
        $url = sprintf('%s/%s/%s',
            \Yii::$app->imagemanager->yiiMediaPath,
            self::getDir($model),
            self::getFileName($model));

        return Url::to($url, $absolute);
    }

    /**
     * @param ImageManager $model
     * @return string
     */
    public static function getFileName($model)
    {
        $fileExtension = pathinfo($model->fileName, PATHINFO_EXTENSION);

        return sprintf('%s_%s.%s', $model->id, $model->fileHash, $fileExtension);
    }

    /**
     * Get image data dimension/size
     *
     * @param ImageManager $model
     * @return array The image sizes
     */
    public static function getImageDetails($model)
    {
        $return = ['width' => 0, 'height' => 0, 'size' => 0];

        $filePath = self::getFilePath($model);

        if (file_exists($filePath)) {
            $imageDimension = getimagesize($filePath);
            $return['width'] = $imageDimension[0] ?? 0;
            $return['height'] = $imageDimension[1] ?? 0;
            $return['size'] = \Yii::$app->formatter->asShortSize(filesize($filePath), 2);
        }

        return $return;
    }
}
