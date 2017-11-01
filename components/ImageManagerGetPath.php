<?php

namespace noam148\imagemanager\components;

use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use noam148\imagemanager\helpers\ImageHelper;
use noam148\imagemanager\Module;
use Yii;
use yii\base\Component;
use noam148\imagemanager\models\ImageManager as Model;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\imagine\Image;
use yii\web\UploadedFile;

class ImageManagerGetPath extends Component
{
    public $yiiMediaPath;

	/**
	 * @var null|string $mediaPath Folder path in which the images are stored
	 */
	public $mediaPath = null;

	/**
	 * @var string $cachePath cache path where store the resized images (relative from webroot (index.php))
	 */
	public $cachePath = "assets/imagemanager";

	/**
	 * @var boolean $useFilename use original filename in generated cache file
	 */
	public $useFilename = true;

	/**
	 * @var boolean $useFilename use original filename in generated cache file
	 */
	public $absoluteUrl = false;

    /**
     * @var string The DB component name that the image model uses
     * This defaults to the default Yii DB component: Yii::$app->db
     * If this component is not set, the model will default to DB
     */
	public $databaseComponent = 'mongodb';

	/**
	 * Init set config
	 */
	public function init() {
		parent::init();
		// initialize the compontent with the configuration loaded from config.php
		\Yii::$app->set('imageresize', [
			'class' => 'noam148\imageresize\ImageResize',
			'cachePath' => $this->cachePath,
			'useFilename' => $this->useFilename,
			'absoluteUrl' => $this->absoluteUrl,
		]);

		if (is_callable($this->databaseComponent)) {
		    // The database component is callable, run the user function
		    $this->databaseComponent = call_user_func($this->databaseComponent);
        }

        // Check if the user input is correct
        $this->_checkVariables();
	}

    /**
     * Get the path for the given ImageManager_id record
     * @param Model|int $model ImageManager record for which the path needs to be generated
     * @param int $width Thumbnail image width
     * @param int $height Thumbnail image height
     * @param string $thumbnailMode Thumbnail mode
     * @param bool $timestamp
     * @return null|string Full path is returned when image is found, null if no image could be found
     */
    public function getImagePath($model, $width = 400, $height = 400, $thumbnailMode = "outbound", $timestamp = false) {
        if (is_object($model) || (is_string($model) && ($model = Model::findOne($model)))) {
            /** @var Model $model */
            $mode = $thumbnailMode == "outbound" ? "outbound" : "inset";

            $filePath = ImageHelper::getFilePath($model);

            if (file_exists($filePath)) {
                return \Yii::$app->imageresize->getUrl($filePath, $width, $height, $mode, null, $model->fileName) . ($timestamp ? "?t=" . time() : '');
            }
        }

        return null;
    }

    /**
     * Check if the user configurable variables match the criteria
     * @throws InvalidConfigException
     */
	private function _checkVariables() {
	    // Check to make sure that the $databaseComponent is a string
        if (! is_string($this->databaseComponent)) {
            throw new InvalidConfigException("Image Manager Component - Init: Database component '$this->databaseComponent' is not a string");
        }

        // Check to make sure that the $databaseComponent object exists
        if (Yii::$app->get($this->databaseComponent, false) === null) {
            throw new InvalidConfigException("Image Manager Component - Init: Database component '$this->databaseComponent' does not exists in application configuration");
        }
    }

    /**
     * @param Model $modelOriginal
     * @param array $cropData
     * @return string|null
     */
    public function cropImage($modelOriginal, $cropData)
    {
        $imagePathPrivate = ImageHelper::getFilePath($modelOriginal);

        if ($imagePathPrivate && $cropData) {
            $iDimensionWidth = round($cropData['width']);
            $iDimensionHeight = round($cropData['height']);

            $sFileNameReplace = preg_replace("/_crop_\d+x\d+/", "", $modelOriginal->fileName);
            $sFileName = pathinfo($sFileNameReplace, PATHINFO_FILENAME);
            $sFileExtension = pathinfo($sFileNameReplace, PATHINFO_EXTENSION);
            $sDisplayFileName = $sFileName . "_crop_" . $iDimensionWidth . "x" . $iDimensionHeight . "." . $sFileExtension;

            //create a file record
            $model = new Model();
            $model->fileName = $sDisplayFileName;
            $model->fileHash = Yii::$app->getSecurity()->generateRandomString(32);

            if ($model->save()) {
                //do crop in try catch
                try {
                    // get current/original image data
                    $imageOriginal = Image::getImagine()->open($imagePathPrivate);
                    $imageOriginalSize = $imageOriginal->getSize();
                    $imageOriginalWidth = $imageOriginalSize->getWidth();
                    $imageOriginalHeight = $imageOriginalSize->getHeight();
                    $imageOriginalPositionX = 0;
                    $imageOriginalPositionY = 0;

                    // create/calculate a canvas size (if canvas is out of the box)
                    $imageCanvasWidth = $imageOriginalWidth;
                    $imageCanvasHeight = $imageOriginalHeight;

                    // update canvas width if X position of croparea is lower than 0
                    if ($cropData['x'] < 0) {
                        //set x postion to Absolute value
                        $iAbsoluteXpos = abs($cropData['x']);
                        //set x position of image
                        $imageOriginalPositionX = $iAbsoluteXpos;
                        //add x position to canvas size
                        $imageCanvasWidth += $iAbsoluteXpos;
                        //update canvas width if croparea is biger than original image
                        $iCropWidthWithoutAbsoluteXpos = ($cropData['width'] - $iAbsoluteXpos);
                        if ($iCropWidthWithoutAbsoluteXpos > $imageOriginalWidth) {
                            //add ouside the box width
                            $imageCanvasWidth += ($iCropWidthWithoutAbsoluteXpos - $imageOriginalWidth);
                        }
                    } else {
                        // add if crop partly ouside image
                        $iCropWidthWithXpos = ($cropData['width'] + $cropData['x']);
                        if ($iCropWidthWithXpos > $imageOriginalWidth) {
                            //add ouside the box width
                            $imageCanvasWidth += ($iCropWidthWithXpos - $imageOriginalWidth);
                        }
                    }

                    // update canvas height if Y position of croparea is lower than 0
                    if ($cropData['y'] < 0) {
                        //set y postion to Absolute value
                        $iAbsoluteYpos = abs($cropData['y']);
                        //set y position of image
                        $imageOriginalPositionY = $iAbsoluteYpos;
                        //add y position to canvas size
                        $imageCanvasHeight += $iAbsoluteYpos;
                        //update canvas height if croparea is biger than original image
                        $iCropHeightWithoutAbsoluteYpos = ($cropData['height'] - $iAbsoluteYpos);
                        if ($iCropHeightWithoutAbsoluteYpos > $imageOriginalHeight) {
                            //add ouside the box height
                            $imageCanvasHeight += ($iCropHeightWithoutAbsoluteYpos - $imageOriginalHeight);
                        }
                    } else {
                        // add if crop partly ouside image
                        $iCropHeightWithYpos = ($cropData['height'] + $cropData['y']);
                        if ($iCropHeightWithYpos > $imageOriginalHeight) {
                            //add ouside the box height
                            $imageCanvasHeight += ($iCropHeightWithYpos - $imageOriginalHeight);
                        }
                    }

                    // round values
                    $imageCanvasWidthRounded = round($imageCanvasWidth);
                    $imageCanvasHeightRounded = round($imageCanvasHeight);
                    $imageOriginalPositionXRounded = round($imageOriginalPositionX);
                    $imageOriginalPositionYRounded = round($imageOriginalPositionY);
                    $imageCropWidthRounded = round($cropData['width']);
                    $imageCropHeightRounded = round($cropData['height']);
                    // set postion to 0 if x or y is less than 0
                    $imageCropPositionXRounded = $cropData['x'] < 0 ? 0 : round($cropData['x']);
                    $imageCropPositionYRounded = $cropData['y'] < 0 ? 0 : round($cropData['y']);

                    // merge current image in canvas, crop image and save
                    $imagineRgb = new RGB();
                    $imagineColor = $imagineRgb->color('#FFF', 0);
                    // create image
                    Image::getImagine()->create(new Box($imageCanvasWidthRounded, $imageCanvasHeightRounded), $imagineColor)
                        ->paste($imageOriginal, new Point($imageOriginalPositionXRounded, $imageOriginalPositionYRounded))
                        ->crop(new Point($imageCropPositionXRounded, $imageCropPositionYRounded), new Box($imageCropWidthRounded, $imageCropHeightRounded))
                        ->save(ImageHelper::getFilePath($model));

                    /** @var Module $module */
                    $module = \Yii::$app->controller->module;
                    if ($module->deleteOriginalAfterEdit) {
                        $modelOriginal->delete();
                    }
                } catch (ErrorException $e) {}
            }
        }

        return isset($model) ? $model->id : null;
    }

    /**
     * @return void
     */
    public function uploadImage()
    {
        foreach (UploadedFile::getInstancesByName('imagemanagerFiles') as $file) {
            if (!$file->error) {
                $model = new Model();
                $model->fileName = str_replace("_", "-", $file->name);
                $model->fileHash = Yii::$app->getSecurity()->generateRandomString(32);

                if ($model->save()) {
                    Image::getImagine()->open($file->tempName)->save(ImageHelper::getFilePath($model));
                }
            }
        }
    }
}
