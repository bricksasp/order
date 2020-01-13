<?php
namespace bricksasp\order\models;

use Yii;

/**
 * This is the model class for Module form validate.
 */
class FormValidate extends \bricksasp\base\FormValidate
{
    const CREATE_ORDER = 'create_order';
    const UPDATE_ORDER = 'update_order';
    const CREATE_BILL = 'create_bill';
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id'], 'exist', 'targetClass'=>Order::class, 'targetAttribute' => ['order_id' => 'id']],
            [['order_code'], 'in', 'range'=> ['wechatpay', 'alipay', 'cmbpay']],
            [['order_type'], 'in', 'range'=> ['app', 'bar', 'lite', 'pub', 'qr', 'wap', 'web']],
            // [['ship_id'], 'required', 'on' => ['create_order']],
            [['cart'], 'each', 'rule' => ['integer'], 'on' => ['create_order']],
            [['products'], 'checkco', 'on' => ['create_order']],
            [['order_code', 'order_type','order_id'], 'required', 'on' => ['create_bill']],
        ];
    }

    /**
     * 使用场景
     */
    public function scenarios()
    {
        return [
            self::CREATE_ORDER => ['cart', 'products', 'ship_id'],
            self::CREATE_BILL => ['order_code', 'order_type', 'order_id'],
        ];
    }

    public function checkco()
    {
        if(!$this->cart && $this->products && !is_array($this->products)){
            $this->addError('products', 'cart || products 二选一必须为数组');
        }
    }
}