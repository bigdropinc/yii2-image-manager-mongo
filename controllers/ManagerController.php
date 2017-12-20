<?php

namespace noam148\imagemanager\controllers;

use noam148\imagemanager\helpers\ImageHelper;
use Yii;
use noam148\imagemanager\models\ImageManager;
use noam148\imagemanager\models\ImageManagerSearch;
use yii\base\InvalidConfigException;
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
	public function beforeAction($action)
    {
		if ($this->action->id != 'index') {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }

		return parent::beforeAction($action);
	}

    /**
     * Lists all ImageManager models.
     *
     * @return string
     * @throws \Exception
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $selectType = $request->get("select-type", "input");
        $viewMode = $request->get("view-mode", "page");
        $multiple = (bool) $request->get("multiple", false);

        if ($viewMode == "iframe") {
            $this->layout = "blank";
        }

        \noam148\imagemanager\widgets\ImageManager::widget([
            'selectType' => $selectType,
            'viewMode' => $viewMode,
            'multiple' => $multiple,
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
        if ($this->module->canUploadImage == false) {
            return [];
        }

        Yii::$app->imagemanager->uploadImage();

        return $this->redirect(['/imagemanager']);
    }

    /**
     * Crop image and create new ImageManager model.
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionCrop()
    {
        $model = $this->findModel(Yii::$app->request->post("ImageManager_id"));

        return Yii::$app->imagemanager->cropImage($model, Yii::$app->request->post("CropData"));
    }

	/**
	 * Get view details
     *
	 * @return mixed
     * @throws NotFoundHttpException|InvalidConfigException
	 */
    public function actionView()
    {
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
            'tags' => implode(', ', $model->tags ?? []),
            'image' => Yii::$app->imagemanager->getImagePath($model->id, 200, 200, "inset", true),
        ];
    }

	/**
	 * Get full image
     *
	 * @return mixed
     * @throws NotFoundHttpException
	 */
    public function actionGetOriginalImage()
    {
        $model = $this->findModel(Yii::$app->request->get("ImageManager_id"));

        return ImageHelper::getImageUrl($model);
    }

    /**
     * Deletes an existing ImageManager model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return array
     * @throws NotFoundHttpException|\Exception|\yii\db\StaleObjectException
     */
	public function actionDelete()
    {
		$return = ['delete' => false];

		if (Yii::$app->controller->module->canRemoveImage == false) {
		    return $return;
		}

		$model = $this->findModel(Yii::$app->request->post("ImageManager_id"));

		if ($model && $model->delete()) {
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
