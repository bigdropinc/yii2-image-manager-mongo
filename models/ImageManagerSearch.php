<?php

namespace noam148\imagemanager\models;

use Yii;
use noam148\imagemanager\Module;
use yii\mongodb\ActiveQuery;

/**
 * ImageManagerSearch represents the model behind the search form about `common\modules\imagemanager\models\ImageManager`.
 */
class ImageManagerSearch extends ImageManager
{
	public $globalSearch;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['globalSearch'], 'safe'],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param bool $viewAll
     * @param bool $showImages
     *
     * @return ActiveQuery
     */
    public function search($params, $viewAll = false, $showImages = true)
    {
        $this->load($params);

        $query = ImageManager::find();

        $module = Module::getInstance();

        if ($module->setBlameableBehavior && !$viewAll) {
            $query->andWhere(['createdBy' => Yii::$app->user->id]);
        }

        if ($this->globalSearch) {
            $query->orWhere(['fileName' => ['$regex' => $this->globalSearch, '$options' => 'i']])
                ->orWhere(['created' => ['$regex' => $this->globalSearch, '$options' => 'i']])
                ->orWhere(['modified' => ['$regex' => $this->globalSearch, '$options' => 'i']])
                ->orWhere(['tags' => $this->globalSearch]);
        }

        $or = [];
        foreach (Module::IMAGE_EXTENSIONS as $IMAGE_EXTENSION) {
            $or[] = ['fileName' => ['$regex' => '.' . $IMAGE_EXTENSION, '$options' => 'i']];
        }

        $query->andWhere([$showImages ? '$or' : '$nor' => $or]);

        return $query;
    }
}
