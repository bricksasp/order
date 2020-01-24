<?php

namespace bricksasp\order\models;

use Yii;

/**
 * This is the model class for table "{{%order_log}}".
 */
class OrderLog extends \bricksasp\base\BaseActiveRecord
{
    const LOG_TYPE_DEFAULT = 1; // 可清除
    const LOG_TYPE_LONGTERM = 2; // 长期订单
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_log}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_id', 'user_id', 'type', 'created_at', 'updated_at'], 'integer'],
            [['data'], 'string'],
            [['order_id'], 'string', 'max' => 20],
            [['info'], 'string', 'max' => 255],
            [['type'], 'default', 'value' => self::TYPE_DEFAULT]
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
