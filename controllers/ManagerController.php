<?php

namespace noam148\imagemanager\controllers;

use noam148\imagemanager\helpers\ImageHelper;
use Yii;
use noam148\imagemanager\models\ImageManager;
use noam148\imagemanager\models\ImageManagerSearch;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use noam148\imagemanager\Module;

/**
 * Manager controller for the `imagemanager` module
 * @property Module $module
 */
class ManagerController extends Controller
{
    public $enableCsrfValidation = false;

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['DELETE'],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action) {
		//disable CSRF Validation
		$this->enableCsrfValidation = false;
		return parent::beforeAction($action);
	}

	/**
	 * Lists all ImageManager models.
	 * @return mixed
	 */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $selectType = $request->get("select-type", "input");
        $viewMode = $request->get("view-mode", "page");

        if ($viewMode == "iframe") {
            $this->layout = "blank";
        }

        \noam148\imagemanager\widgets\ImageManager::widget([
            'selectType' => $selectType,
            'viewMode' => $viewMode,
        ]);

        $searchModel = new ImageManagerSearch();

        $dataProvider = new ActiveDataProvider([
            'query' => $searchModel->search(Yii::$app->request->queryParams),
            'pagination' => [
                'pageSize' => 100,
            ],
            'sort'=> ['defaultOrder' => ['created'=>SORT_DESC]]
        ]);

        return $this->render('@vendor/kolyasiryk/yii2-image-manager-mongo/views/manager/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'viewMode' => $viewMode,
            'selectType' => $selectType,
            'allowedFileExtensions' => $this->module->allowedFileExtensions,
            'canUploadImage' => $this->module->canUploadImage,
            'canRemoveImage' => $this->module->canRemoveImage,
        ]);
    }

	/**
	 * Creates a new ImageManager model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($this->module->canUploadImage == false) {
            return [];
        }

        Yii::$app->imagemanager->uploadImage();

        return $_FILES;
    }

	/**
	 * Crop image and create new ImageManager model.
	 * @return mixed
	 */
    public function actionCrop()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $modelOriginal = $this->findModel(Yii::$app->request->post("ImageManager_id"));

        return Yii::$app->imagemanager->cropImage($modelOriginal, Yii::$app->request->post("CropData"));
    }

	/**
	 * Get view details
	 * @return mixed
	 */
    public function actionView()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel(Yii::$app->request->get("ImageManager_id"));

        $imageDetails = ImageHelper::getImageDetails($model);

        return [
            'id' => $model->id,
            'fileName' => $model->fileName,
            'created' => Yii::$app->formatter->asDate($model->created),
            'fileSize' => $imageDetails['size'],
            'dimensionWidth' => $imageDetails['width'],
            'dimensionHeight' => $imageDetails['height'],
            'originalLink' => ImageHelper::getImageUrl($model),
            'image' => Yii::$app->imagemanager->getImagePath(
                    $model->id,
                    400,
                    400,
                    "inset"
                ) . "?t=" . time(),
        ];
    }

	/**
	 * Get full image
	 * @return mixed
	 */
    public function actionGetOriginalImage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel(Yii::$app->request->get("ImageManager_id"));

        $imageDetails = ImageHelper::getImageDetails($model);

        return \Yii::$app->imagemanager->getImagePath(
            $model->id,
            $imageDetails['width'],
            $imageDetails['height'],
            "inset"
        );
    }

	/**
	 * Deletes an existing ImageManager model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @return mixed
	 */
	public function actionDelete()
    {
		//return 
		$return = ['delete' => false];
		//set response header
		Yii::$app->getResponse()->format = Response::FORMAT_JSON;

		if (Yii::$app->controller->module->canRemoveImage == false) {
		    // User can not remove this image, return false status
		    return $return;
		}

		//get post
		$ImageManager_id = Yii::$app->request->post("ImageManager_id");
		//get details
		$model = $this->findModel($ImageManager_id);

		//delete record
		if ($model->delete()) {
			$return['delete'] = true;
		}
		return $return;
	}

	/**
	 * Finds the ImageManager model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @return ImageManager the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
    protected function findModel($id)
    {
        if ($model = ImageManager::findOne($id)) {
            /* @var $model ImageManager */
            $module = Module::getInstance();

            if ($module->setBlameableBehavior && Yii::$app->user->id != $model->createdBy) {
                throw new NotFoundHttpException(Yii::t('imagemanager', 'The requested image does not exist.'));
            }

            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('imagemanager', 'The requested image does not exist.'));
        }
    }

}
