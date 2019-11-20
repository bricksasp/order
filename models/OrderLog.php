<?php
namespace bricksasp\order\models;

use Yii;

class OrderLog extends \bricksasp\base\BaseActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'created_at'], 'integer'],
            [['order_id'], 'string', 'max' => 20],
            [['info'], 'string', 'max' => 100],
            [['data'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'info' => 'Info',
            'data' => 'Data',
            'created_at' => 'Created At',
        ];
    }
}
