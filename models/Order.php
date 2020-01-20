<?php
namespace bricksasp\order\models;

use Yii;
use bricksasp\base\models\File;
use bricksasp\spu\models\Goods;
use bricksasp\spu\models\Product;
use bricksasp\helpers\Tools;
use bricksasp\base\models\Region;
use bricksasp\promotion\models\PromotionCoupon;
use bricksasp\promotion\models\PromotionConditions;

/**
 * This is the model class for table "{{%order}}".
 */
class Order extends \bricksasp\base\BaseActiveRecord
{
    const ORDER_IS_COMMENT = 2; //已评论
    const ORDER_NO_COMMENT = 1; //未评论
    const ORDER_TYPE_DEFAULT = 1; // 默认类型
    const ORDER_TYPE_RECHARGE = 2; // 充值
    const ORDER_TYPE_LONGTERM = 3; // 长期订单

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \bricksasp\helpers\behaviors\SnBehavior::className(),
                'attribute' => 'id',
                'type' => \bricksasp\helpers\behaviors\SnBehavior::SN_ORDER,
            ],
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
            [['owner_id', 'pay_status', 'ship_status', 'order_status', 'payment_at', 'user_id', 'seller_id', 'confirm', 'confirm_at', 'store_id', 'ship_area_id', 'tax_type', 'type', 'point', 'source', 'is_comment', 'status', 'created_at', 'updated_at'], 'integer'],
            [['order_amount', 'pay_amount', 'payed', 'cost_freight', 'total_weight', 'total_volume', 'point_money', 'order_pmt', 'goods_pmt', 'coupon_pmt'], 'number'],
            [['id', 'payment_code', 'logistics_id'], 'string', 'max' => 20],
            [['logistics_name', 'ship_name', 'tax_code', 'tax_title', 'ip'], 'string', 'max' => 50],
            [['ship_address'], 'string', 'max' => 200],
            [['ship_phone'], 'string', 'max' => 16],
            [['tax_content', 'promotion_info', 'memo', 'mark'], 'string', 'max' => 255],
            [['coupon'], 'string', 'max' => 5000],
            [['id'], 'unique'],
            [['pay_status', 'ship_status', 'order_status', 'confirm', 'status', 'type'], 'default', 'value' => 1],
            [['ip'], 'default', 'value' => Tools::client_ip()]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '订单id',
            'owner_id' => 'Owner ID',
            'order_amount' => 'Order Amount',
            'pay_amount' => 'Pay Amount',
            'payed' => 'Payed',
            'pay_status' => 'Pay Status',
            'ship_status' => 'Ship Status',
            'order_status' => 'Order Status',
            'payment_code' => 'Payment Code',
            'payment_at' => 'Payment At',
            'logistics_id' => 'Logistics ID',
            'logistics_name' => 'Logistics Name',
            'cost_freight' => 'Cost Freight',
            'user_id' => 'User ID',
            'seller_id' => 'Seller ID',
            'confirm' => 'Confirm',
            'confirm_at' => 'Confirm At',
            'store_id' => 'Store ID',
            'ship_area_id' => 'Ship Area ID',
            'ship_address' => 'Ship Address',
            'ship_name' => 'Ship Name',
            'ship_mobile' => 'Ship Mobile',
            'total_weight' => 'Total Weight',
            'total_volume' => 'Total Volume',
            'tax_type' => 'Tax Type',
            'tax_content' => 'Tax Content',
            'type' => 'Type',
            'tax_code' => 'Tax Code',
            'tax_title' => 'Tax Title',
            'point' => 'Point',
            'point_money' => 'Point Money',
            'promotion_info' => 'Promotion Info',
            'order_pmt' => 'Order Pmt',
            'goods_pmt' => 'Goods Pmt',
            'coupon_pmt' => 'Coupon Pmt',
            'coupon' => 'Coupon',
            'memo' => 'Memo',
            'ip' => 'Ip',
            'mark' => 'Mark',
            'source' => 'Source',
            'is_comment' => 'Is Comment',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function excelField()
    {
        $require = [''];
        $fields = [];
        foreach ($this->attributeLabels() as $name => $label) {
            $field['name']    = $name;
            $field['label']   = $label;
            $field['require'] = in_array($name, $fields) ? 1 : 0;
            $fields[] = $field;
        }
        return $fields;
    }

    public function getItems()
    {
        return $this->hasMany(OrderItem::className(), ['order_id' => 'id'])
        ->select(['order_id', 'goods_id', 'image_id', 'price',])
        ->asArray();
    }

    public function getItemImages()
    {
        return $this->hasMany(File::className(), ['id' => 'image_id'])->via('items')->select(['id', 'file_url'])->asArray();
    }

    public function getLogistics()
    {
        return $this->hasOne(LogisticsCompany::className(), ['id' => 'logistics_id'])->asArray();
    }

    public function getExt()
    {
        return $this->hasMany(OrderExt::className(), ['order_id' => 'id'])->asArray();
    }

    public function userShipArea()
    {
        $model = new Region();
        return $model->cascader($this->ship_area_id);
    }

    public function saveData($parmas)
    {
        list($data, $orderItems) = $this->formatData($parmas);
        $this->load($data);
        // print_r($data);exit;
        $transaction = self::getDb()->beginTransaction();
        try {
            $this->save();
            if (!$this->id) {
                $transaction->rollBack();
                return false;
            }

            foreach ($orderItems as $k => $product) {
                $product['order_id']    = $this->id;
                $model = new OrderItem();
                $model->load($product);
                $model->save();
            }
            if (!empty($parmas['cart'])) {
                Cart::deleteAll(['id'=>$parmas['cart']]);
            }
            if (!empty($parmas['ext'])) {
                $fields = [];
                foreach ($parmas['ext'] as $field => $val) {
                    $f['order_id'] = $this->id;
                    $f['order_type'] = $this->type;
                    $f['field'] = $field;
                    $f['val'] = is_array($val) ? json_encode($val) : $val;
                    $fields[] = $f;
                }
                self::getDb()->createCommand()
                ->batchInsert(OrderExt::tableName(),['order_id','order_type','field','val'],$fields)
                ->execute();
            }

            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 格式化数据
     * @return array
     */
    public function formatData($parmas)
    {
        $orderItems = [];
        if (empty($parmas['cart'])) {
            if (empty($parmas['products'])) {
                Tools::exceptionBreak(Yii::t('base', 40002, '单品'));
            }
            $pids = array_column($parmas['products'],'id');
            $nums = array_column($parmas['products'], 'num', 'id');
        }else{
            $carts = Cart::find()->where(['id' => $parmas['cart']])->all();
            $pids = array_column($carts,'product_id');
            $nums = array_column($carts, 'num', 'product_id');
        }
        // print_r($pids);exit;
        if (!array_filter($pids)) {
            Tools::exceptionBreak(950001);
        }

        // 收货地址
        $shipAdr = ShipAddress::find()->with([])->where(['id' => $parmas['ship_id']])->one();
        if ($shipAdr) {
            $parmas['ship_area_id'] = $shipAdr->area_id;
            $parmas['ship_address'] = $shipAdr->detail;
            $parmas['ship_name'] = $shipAdr->name;
            $parmas['ship_phone'] = $shipAdr->phone;
        }

        $products = Product::find()->with(['goods'])->where(['id' => $pids])->all();
        
        // 优惠券处理
        $coupons = $cat_ids = [];
        if ($parmas['coupons']) {
            $model = new PromotionCoupon();
            $coupons =  $model->checkEffectiveness($parmas['coupons']);

            //商品分类
            $goods = Goods::find()->select(['id', 'cat_id'])->where(['id' => array_map(function ($item)
            {
                return $item->goods_id;
            },$products)])->asArray()->all();

            $cat_ids = array_combine(array_column($goods,'id'), array_column($goods,'cat_id'));
        }
        
        // print_r($cat_ids);exit;

        foreach ($products as $p) {

            $item['product_id'] = $p->id;
            $item['goods_id'] = $p->goods->id;
            $item['name'] = $p->goods->name;
            $item['image_id'] = $p->goods->image_id;
            $item['gn'] = $p->goods->gn;
            $item['price'] = $p->price;
            $item['costprice'] = $p->costprice;
            $item['mktprice'] = $p->mktprice;
            $item['pn'] = $p->pn;
            $item['num'] = $nums[$p->id];

            // 计算单品价格 
            $price = 0;
            if ($coupons) {
                // 全部商品
                if (isset($coupons[PromotionConditions::TYPE_ALL])) {
                    $res = $coupons[PromotionConditions::TYPE_ALL]['result'][PromotionCoupon::RESULT_GOODS_AMOUT] ?? [];
                    if ($res) $price = $p->price - array_sum($res);

                    $res = $coupons[PromotionConditions::TYPE_ALL]['result'][PromotionCoupon::RESULT_GOODS_DISCOUNT] ?? [];
                    if ($res) $price = $p->price * array_product($res) / (count($res) * 100);
                }
                // 商品分类
                if (isset($coupons[PromotionConditions::TYPE_CAT])) {
                    $res = $coupons[PromotionConditions::TYPE_CAT]['result'][PromotionCoupon::RESULT_GOODS_AMOUT] ?? [];
                    if ($res && in_array($cat_ids[$p->goods_id], $coupons[PromotionConditions::TYPE_CAT]['content'])) {
                        $price = $p->price - array_sum($res);
                    }

                    $res = $coupons[PromotionConditions::TYPE_CAT]['result'][PromotionCoupon::RESULT_GOODS_DISCOUNT] ?? [];
                    if ($res && in_array($cat_ids[$p->goods_id], $coupons[PromotionConditions::TYPE_CAT]['content'])) {
                        $price = $p->price * array_product($res) / (count($res) * 100);
                    }
                }
                // 指定部分商品
                if (isset($coupons[PromotionConditions::TYPE_PART])) {
                    $res = $coupons[PromotionConditions::TYPE_PART]['result'][PromotionCoupon::RESULT_GOODS_AMOUT] ?? [];
                    if ($res && in_array($p->id, $coupons[PromotionConditions::TYPE_PART]['content'])) {
                        $price = $p->price - array_sum($res);
                    }

                    $res = $coupons[PromotionConditions::TYPE_PART]['result'][PromotionCoupon::RESULT_GOODS_DISCOUNT] ?? [];
                    if ($res && in_array($p->id, $coupons[PromotionConditions::TYPE_PART]['content'])) {
                        $price = $p->price * array_product($res) / (count($res) * 100);
                    }

                    $res = $coupons[PromotionConditions::TYPE_PART]['result'][PromotionCoupon::RESULT_GOODS_PRICE] ?? [];
                    if ($res && in_array($p->id, $coupons[PromotionConditions::TYPE_PART]['content'])) {
                        $price = $coupons[PromotionConditions::TYPE_PART]['result'][$p->id];
                    }
                }
            }

            if ($price){
                $item['promotion_amount'] = $p->price - $price;
            }else{
                $price = $p->price;
            }

            $item['amount'] = $price * ($item['num'] ? $item['num'] : 1);
            $item['weight'] = $p->weight * ($item['num'] ? $item['num'] : 1);
            $item['volume'] = $p->volume * ($item['num'] ? $item['num'] : 1);

            $data['order_amount'] = ($data['order_amount'] ?? 0) + $item['amount'];
            $data['total_weight'] = ($data['total_weight'] ?? 0) + $item['weight'];
            $data['total_volume'] = ($data['total_volume'] ?? 0) + $item['volume'];
            $orderItems[] = $item;
        }

        // 计算订单价格
        if ($coupons) {
            if (isset($coupons[PromotionConditions::TYPE_REDUCTION])) {
                $res = $coupons[PromotionConditions::TYPE_REDUCTION]['result'][PromotionCoupon::RESULT_ORDER_AMOUT] ?? [];
                if ($res) $data['pay_amount'] = $data['order_amount'] - array_sum($res);
                
                $res = $coupons[PromotionConditions::TYPE_REDUCTION]['result'][PromotionCoupon::RESULT_ORDER_DISCOUNT] ?? [];
                if ($res) $data['pay_amount'] = $data['order_amount'] * array_product($res);

                $res = $coupons[PromotionConditions::TYPE_REDUCTION]['result'][PromotionCoupon::RESULT_ORDER_PRICE] ?? [];
                if ($res) {
                    sort($res);
                    $data['pay_amount'] = array_pop($res);
                }
            }
        }
        if (isset($data['pay_amount'])) {
            $data['coupon'] = json_encode($coupons);
        }else{
            $data['pay_amount'] = $data['order_amount'];
        }
        if ($data['pay_amount'] <= 0) {
            $data
        }
        return [$data, $orderItems];
    }

}
