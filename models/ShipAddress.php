<?php
namespace bricksasp\order\models;

use Yii;
use bricksasp\base\models\Region;

/**
 * This is the model class for table "{{%ship_address}}".
 */
class ShipAddress extends \bricksasp\base\BaseActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ship_address}}';
    }
    
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => time(),
            ],
            [
                'class' => \bricksasp\helpers\behaviors\UidBehavior::className(),
                'createdAtAttribute' => 'user_id',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'area_id', 'is_def', 'created_at', 'updated_at'], 'integer'],
            [['detail'], 'string', 'max' => 128],
            [['name'], 'string', 'max' => 64],
            [['phone'], 'string', 'max' => 16],
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
            'area_id' => 'Area ID',
            'detail' => 'Detail',
            'name' => 'Name',
            'phone' => 'Phone',
            'is_def' => 'Is Def',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getProvince()
    {
        return $this->hasOne(Region::className(), ['id' => 'parent_id'])->via('city')->select(['id', 'parent_id']);
    }

    public function getCity()
    {
        return $this->hasOne(Region::className(), ['id' => 'parent_id'])->via('area')->select(['id', 'parent_id']);
    }

    public function getArea()
    {
        return $this->hasOne(Region::className(), ['id' => 'parent_id'])->via('town')->select(['id', 'parent_id']);
    }

    public function getTown()
    {
        return $this->hasOne(Region::className(), ['id' => 'area_id'])->select(['id', 'parent_id']);
    }
}