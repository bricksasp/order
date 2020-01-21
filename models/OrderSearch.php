<?php
namespace bricksasp\order\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use bricksasp\order\models\Order;
use bricksasp\helpers\Tools;
use Yii;

/**
 * OrderSearch represents the model behind the search form of `bricksasp\order\models\Order`.
 */
class OrderSearch extends Order
{
    const EXCEL_NAME = "订单";
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'payment_code'], 'safe'],
            [['owner_id', 'pay_status', 'ship_status', 'order_status', 'user_id', 'type', 'source', 'is_comment', 'status', 'created_at', 'updated_at'], 'integer'],
            [['type', 'status'], 'default', 'value' => 1]
        ];
    }

    /**
     * {@inheritdoc}
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
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Order::find();
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $params['pageSize'] ?? 10,
            ],
            // 'sort' => [
            //     'defaultOrder' => [
            //         'updated_at' => SORT_DESC,
            //     ]
            // ],
        ]);

        $this->load($this->filterParma($params));
        if (!$params['data_all']) {
            $query->select(['id','order_amount', 'pay_amount', 'pay_status', 'ship_status', 'order_status', 'created_at'])->with(['itemImages']);
        }
        if (!$this->validate()) {
            Tools::exceptionBreak(Yii::t('base',50006));
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'order_status' => $this->order_status,
            'ship_status' => $this->ship_status,
            'user_id' => $this->user_id,
            'owner_id' => $this->owner_id,
            'source' => $this->source,
            'is_comment' => $this->is_comment,
            'type' => $this->type,
            'status' => $this->status,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'id', $this->id])
            ->andFilterWhere(['like', 'payment_code', $this->payment_code]);

        return $dataProvider;
    }
}
