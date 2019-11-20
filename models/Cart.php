<?php
namespace bricksasp\order\models;

use Yii;
use bricksasp\spu\models\Product;
use bricksasp\base\models\File;

/**
 * This is the model class for table "{{%order_cart}}".
 */
class Cart extends \bricksasp\base\BaseActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_cart}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
            [
                'class' => \bricksasp\helpers\behaviors\UidBehavior::className(),
                'createdAtAttribute' => 'user_id',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'image_id', 'owner_id', 'product_id', 'num', 'created_at', 'updated_at'], 'integer'],
            [['num'], 'default', 'value' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'owner_id' => 'Owner ID',
            'product_id' => 'Product ID',
            'num' => 'Num',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id'])->select(['id', 'price']);
    }

    public function getImage()
    {
        return $this->hasOne(File::className(), ['id' => 'image_id'])->select(['id', 'file_url']);
    }
}
