<?php

namespace noam148\imagemanager\models;

use Yii;
use yii\base\Model;
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
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveQuery
     */
    public function search($params)
    {
        $this->load($params);

        $query = ImageManager::find();

        $module = Module::getInstance();

        if ($module->setBlameableBehavior) {
            $query->andWhere(['createdBy' => Yii::$app->user->id]);
        }

        if ($this->globalSearch) {
            $query->orFilterWhere(['fileName' => ['$regex' => $this->globalSearch, '$options' => 'i']])
                ->orFilterWhere(['created' => ['$regex' => $this->globalSearch, '$options' => 'i']])
                ->orFilterWhere(['modified' => ['$regex' => $this->globalSearch, '$options' => 'i']]);
        }

        return $query;
    }
}
