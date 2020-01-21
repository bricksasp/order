<?php
namespace bricksasp\order\models;

use Yii;

/**
 * This is the model class for table "{{%order_item}}".
 */
class OrderItem extends \bricksasp\base\BaseActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_item}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_id', 'product_id', 'num', 'delivery_num', 'created_at', 'updated_at'], 'integer'],
            [['price', 'costprice', 'mktprice', 'amount', 'promotion_amount', 'weight', 'volume'], 'number'],
            [['order_id'], 'string', 'max' => 20],
            [['pn', 'gn'], 'string', 'max' => 30],
            [['name', 'brief'], 'string', 'max' => 255],
            [['image_id'], 'string', 'max' => 64],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ]
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
            'goods_id' => 'Goods ID',
            'product_id' => 'Product ID',
            'pn' => 'Pn',
            'gn' => 'Gn',
            'name' => 'Name',
            'price' => 'Price',
            'costprice' => 'Costprice',
            'mktprice' => 'Mktprice',
            'image_id' => 'Image ID',
            'num' => 'Num',
            'amount' => 'Amount',
            'promotion_amount' => 'Promotion Amount',
            'weight' => 'Weight',
            'volume' => 'Volume',
            'delivery_num' => 'Delivery Num',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
