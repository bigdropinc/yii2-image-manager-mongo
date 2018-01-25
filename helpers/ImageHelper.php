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
     * @param string|bool|null $absolute
     * @return string
     */
    public static function getImageUrl($model, $absolute = null)
    {
        if (!$model) return null;

        $url = sprintf('%s/%s/%s',
            \Yii::$app->imagemanager->yiiMediaPath,
            self::getDir($model),
            self::getFileName($model));

        return Url::to($url, $absolute ?? \Yii::$app->imagemanager->absoluteUrl);
    }

    /**
     * @param ImageManager $model
     * @return string
     */
    public static function getFileExtension($model)
    {
        return pathinfo($model->fileName, PATHINFO_EXTENSION);
    }

    /**
     * @param ImageManager $model
     * @return string
     */
    public static function getFileName($model)
    {
        return sprintf('%s_%s.%s', $model->id, $model->fileHash, self::getFileExtension($model));
    }

    /**
     * @param ImageManager $model
     * @param integer $width
     * @param integer $height
     * @return string
     */
    public static function getCropFileName($model, $width, $height)
    {
        $fileNameReplace = preg_replace("/_crop_\d+x\d+/", "", $model->fileName);
        $fileName = pathinfo($fileNameReplace, PATHINFO_FILENAME);
        $fileExtension = pathinfo($fileNameReplace, PATHINFO_EXTENSION);

        return sprintf('%s_crop_%sx%s.%s', $fileName, $width, $height, $fileExtension);
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

    /**
     * Get image path by url
     *
     * @param string $url
     * @param string $alias
     * @return string
     */
    public static function getPathByUrl($url, $alias = '@frontend/web')
    {
        $path = parse_url($url, PHP_URL_PATH);

        return $alias . $path;
    }

    /**
     * Get thumb url by origin url
     *
     * @param string $url
     * @param string $alias
     * @param string $mode
     * @param integer $width
     * @param integer $height
     * @return string
     */
    public static function getThumbByUrl($url, $width = 600, $height = 600, $mode = 'inset', $alias = '@frontend/web')
    {
        if (!$url) return null;

        $path = self::getPathByUrl($url, $alias);

        return file_exists(\Yii::getAlias($path)) ? \Yii::$app->imageresize->getUrl($path, $width, $height, $mode) : null;
    }
}
