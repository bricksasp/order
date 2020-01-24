<?php

namespace bricksasp\order\models;

use Yii;

/**
 * This is the model class for table "{{%order_documentary}}".
 */
class OrderDocumentary extends \bricksasp\base\BaseActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_documentary}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'parent_id', 'user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['data'], 'string'],
            [['info'], 'string', 'max' => 255],
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
            'parent_id' => 'Parent ID',
            'user_id' => 'User ID',
            'status' => 'Status',
            'info' => 'Info',
            'data' => 'Data',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
