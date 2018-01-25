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
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\BaseFileHelper;
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
     *
     * @return void
     * @throws InvalidConfigException
     */
	public function init()
    {
		parent::init();

		\Yii::$app->set('imageresize', [
			'class' => 'noam148\imageresize\ImageResize',
			'cachePath' => $this->cachePath,
			'useFilename' => $this->useFilename,
			'absoluteUrl' => $this->absoluteUrl,
		]);

		if (is_callable($this->databaseComponent)) {
		    $this->databaseComponent = call_user_func($this->databaseComponent);
        }

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
     * @throws Exception
     */
    public function getImagePath($model, $width = 400, $height = 400, $thumbnailMode = "outbound", $timestamp = false)
    {
        if (is_object($model) || (is_string($model) && ($model = Model::findOne($model)))) {
            /** @var Model $model */
            $mode = $thumbnailMode == "outbound" ? "outbound" : "inset";

            $filePath = ImageHelper::getFilePath($model);
            $fileExtension = ImageHelper::getFileExtension($model);

            if (file_exists($filePath) && in_array($fileExtension, Module::IMAGE_EXTENSIONS)) {
                if ($fileExtension === 'gif') {
                    $dir = \Yii::getAlias(sprintf('@webroot/%s/%s', $this->cachePath, ImageHelper::getDir($model)));
                    BaseFileHelper::createDirectory($dir);

                    $newPath = sprintf('%s/%s', $dir, ImageHelper::getFileName($model));

                    if (!file_exists($newPath)) {
                        Image::getImagine()->open($filePath)->save($newPath);
                    }

                    $filePath = $newPath;
                }

                return \Yii::$app->imageresize->getUrl($filePath, $width, $height, $mode, null, $model->fileName) . ($timestamp ? "?t=" . time() : '');
            }
        }

        return null;
    }

    /**
     * Check if the user configurable variables match the criteria
     * @throws InvalidConfigException
     */
	private function _checkVariables()
    {
        if (!is_string($this->databaseComponent)) {
            throw new InvalidConfigException("Image Manager Component - Init: Database component '$this->databaseComponent' is not a string");
        }

        if (Yii::$app->get($this->databaseComponent, false) === null) {
            throw new InvalidConfigException("Image Manager Component - Init: Database component '$this->databaseComponent' does not exists in application configuration");
        }
    }

    /**
     * @param Model $modelOriginal
     * @param $cropData
     * @return null|string
     * @throws Exception
     * @throws \Exception
     * @throws \yii\db\StaleObjectException
     */
    public function cropImage(Model $modelOriginal, $cropData)
    {
        $imagePathPrivate = ImageHelper::getFilePath($modelOriginal);

        if ($imagePathPrivate && $cropData) {
            $width = round($cropData['width']);
            $height = round($cropData['height']);

            $model = new Model([
                'fileName' => ImageHelper::getCropFileName($modelOriginal, $width, $height),
            ]);

            if ($model->save()) {
                try {
                    $imageOriginal = Image::getImagine()->open($imagePathPrivate);
                    $imageOriginalSize = $imageOriginal->getSize();

                    list($imageCanvasWidth, $imageOriginalPositionX, $imageCropPositionXRounded)
                        = $this->handlingCropData($cropData['x'], $cropData['width'], $imageOriginalSize->getWidth());
                    list($imageCanvasHeight, $imageOriginalPositionY, $imageCropPositionYRounded)
                        = $this->handlingCropData($cropData['y'], $cropData['height'], $imageOriginalSize->getHeight());

                    Image::getImagine()->create(new Box($imageCanvasWidth, $imageCanvasHeight), (new RGB())->color('#FFF', 0))
                        ->paste($imageOriginal, new Point($imageOriginalPositionX, $imageOriginalPositionY))
                        ->rotate(round($cropData['rotate']))
                        ->crop(new Point($imageCropPositionXRounded, $imageCropPositionYRounded), new Box($width, $height))
                        ->save(ImageHelper::getFilePath($model));

                    /** @var Module $module */
                    $module = \Yii::$app->controller->module;
                    if ($module->deleteOriginalAfterEdit) {
                        $modelOriginal->delete();
                    }
                } catch (\Exception $e) {
                    $model->delete();
                }
            }
        }

        return isset($model) ? $model->id : null;
    }

    /**
     * @param integer $coordinate
     * @param integer $side
     * @param integer $imageOriginalSide
     * @return array
     */
    protected function handlingCropData($coordinate, $side, $imageOriginalSide)
    {
        $imageOriginalPosition = $imageCropPosition = 0;
        $canvas = $imageOriginalSide;

        if ($coordinate < 0) {
            $absolutePos = abs($coordinate);
            $imageOriginalPosition = $absolutePos;
            $canvas += $absolutePos;
            $cropWithoutAbsolutePos = $side - $absolutePos;
            if ($cropWithoutAbsolutePos > $imageOriginalSide) {
                $canvas += $cropWithoutAbsolutePos - $imageOriginalSide;
            }
        } else {
            $imageCropPosition = round($coordinate);
            $cropHeightWithPos = $side + $coordinate;
            if ($cropHeightWithPos > $imageOriginalSide) {
                $canvas += ($cropHeightWithPos - $imageOriginalSide);
            }
        }

        return [
            round($canvas),
            round($imageOriginalPosition),
            $imageCropPosition
        ];
    }

    /**
     * @return string
     */
    public function uploadImage()
    {
        $tags = \Yii::$app->request->post('tags', []);

        foreach (UploadedFile::getInstancesByName('imagemanagerFiles') as $file) {
            if (!$file->error) {
                $model = new Model();
                $model->fileName = str_replace("_", "-", $file->name);
                $model->tags = $tags;

                if ($model->save()) {
                    $file->saveAs(ImageHelper::getFilePath($model));
                }
            }
        }

        return isset($model) ? ImageHelper::getFileExtension($model) : 'jpg';
    }
}
