<?php

namespace bricksasp\order\models;

use Yii;

/**
 * This is the model class for table "{{%order_ext}}".
 *
 * @property int $order_id
 * @property string $order_type
 * @property string $field
 * @property string $val
 * @property string $sort
 */
class OrderExt extends \bricksasp\base\BaseActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_ext}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id'], 'string', 'max' => 20],
            [['order_type', 'val', 'sort'], 'string', 'max' => 255],
            [['field'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_type' => 'Order Type',
            'field' => 'Field',
            'val' => 'Val',
            'sort' => 'Sort',
        ];
    }
}
