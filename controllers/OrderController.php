<?php
namespace bricksasp\order\controllers;

use bricksasp\base\BaseController;
use bricksasp\base\Config;
use bricksasp\helpers\Tools;
use bricksasp\order\models\FormValidate;
use bricksasp\order\models\Order;
use bricksasp\order\models\OrderItem;
use bricksasp\order\models\OrderSearch;
use bricksasp\payment\models\PlaceOrder;
use Yii;
use yii\web\HttpException;
use bricksasp\base\models\File;
use bricksasp\rbac\models\redis\Token;

/**
 * OrderController implements the CRUD actions for Order model.
 */
class OrderController extends BaseController {

	/**
	 * 登录可访问 其他需授权
	 * @return array
	 */
	public function allowAction() {
		return [
			'create',
			'update',
			'delete',
			'view',
			'index',
		];
	}

	/**
	 * 免登录可访问
	 * @return array
	 */
	public function allowNoLoginAction() {
		return [];
	}

	/**
	 * Lists all Order models.
	 * 
	 * @OA\Get(path="/order/order/index",
	 *   summary="订单列表",
	 *   tags={"order模块"},
	 *   @OA\Parameter(
	 *     description="登录凭证",
	 *     name="X-Token",
	 *     in="header",
     *     required=true,
	 *     @OA\Schema(
	 *       type="string"
	 *     )
	 *   ),
	 *   @OA\Parameter(
	 *     description="订单类型 1通用(默认) 2充值 3长期订单 4其他",
	 *     name="type",
	 *     in="query",
	 *     @OA\Schema(
	 *       type="integer",
	 *       default=1
	 *     )
	 *   ),
	 *   @OA\Parameter(
	 *     description="当前叶数",
	 *     name="page",
	 *     in="query",
	 *     @OA\Schema(
	 *       type="integer"
	 *     )
	 *   ),
	 *   @OA\Parameter(
	 *     description="每页行数",
	 *     name="pageSize",
	 *     in="query",
	 *     @OA\Schema(
	 *       type="integer"
	 *     )
	 *   ),
	 *   @OA\Response(
	 *     response=200,
	 *     description="列表数据",
	 *     @OA\MediaType(
	 *       mediaType="application/json",
	 *       @OA\Schema(ref="#/components/schemas/orderList"),
	 *     ),
	 *   ),
	 * )
	 *
	 * @OA\Schema(
	 *   schema="orderList",
	 *   description="订单列表结构",
	 *   allOf={
	 *     @OA\Schema(
	 *       @OA\Property(property="id", type="integer", description="订单号"),
	 *       @OA\Property(property="order_amount", type="integer", description="订单总价"),
	 *       @OA\Property( property="pay_amount", type="integer", description="订单实际销售总额"),
	 *       @OA\Property( property="pay_status", type="integer", description="支付状态" ),
	 *       @OA\Property( property="ship_status", type="integer", description="发货状态" ),
	 *       @OA\Property(property="order_status", type="integer", description="订单状态"),
	 *       @OA\Property(property="created_at", type="integer", description="创建时间"),
	 *       @OA\Property(property="items", type="array", description="商品列表", @OA\Items(
	 *           @OA\Property(
	 *         	 description="商品id",
	 *             property="goods_id",
	 *             type="integer"
	 *           ),
	 *           @OA\Property(
	 *         	 description="图片id",
	 *             property="image_id",
	 *             type="integer"
	 *           ),
	 *           @OA\Property(
	 *         	 description="商品名称",
	 *             property="name",
	 *             type="string"
	 *           ),
	 *           @OA\Property(
	 *         	 description="商品简介",
	 *             property="brief",
	 *             type="string"
	 *           ),
	 *		   )
	 *		 ),
	 *       @OA\Property(property="itemImages", type="array", description="商品图片", @OA\Items(
	 *           @OA\Property(
	 *         	 description="图片地址",
	 *             property="file_url",
	 *             type="string"
	 *           ),
	 *           @OA\Property(
	 *         	 description="图片id",
	 *             property="image_id",
	 *             type="integer"
	 *           ),
	 *		   )
	 *		 )
	 *     )
	 *   }
	 * )
	 */
	public function actionIndex() {
		$searchModel = new OrderSearch();
		$dataProvider = $searchModel->search($this->queryFilters());

		return $this->pageFormat($dataProvider, ['items' => false, 'itemImages' => [
			['file_url' => ['implode', ['', [Config::instance()->web_url, '###']], 'array']],
		]], 2, 2);
	}

	/**
	 * Displays a single Order model.
	 * @OA\Get(path="/order/order/view",
	 *   summary="订单详情",
	 *   tags={"order模块"},
	 *   @OA\Parameter(
	 *     description="登录凭证",
	 *     name="X-Token",
	 *     in="header",
	 *     required=true,
	 *     @OA\Schema(
	 *       type="string"
	 *     )
	 *   ),
	 *   @OA\Parameter(
	 *     description="订单id",
	 *     name="id",
	 *     in="query",
	 *     required=true,
	 *     @OA\Schema(
	 *       type="integer"
	 *     )
	 *   ),
	 *   @OA\Response(
	 *     response=200,
	 *     description="响应结构",
	 *     @OA\MediaType(
	 *       mediaType="application/json",
	 *       @OA\Schema(
	 *         @OA\Property(
	 *           description="订单总额",
	 *           property="order_amount",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="实际总额",
	 *           property="pay_amount",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="支付状态 1=未付款 2=已付款 3=部分付款 4=部分退款 5=已退款",
	 *           property="pay_status",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="发货状态 1=未发货 2=已发货 3=部分发货 4=部分退货 5=已退货",
	 *           property="ship_status",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="订单状态 1=正常 2=完成 3=取消",
	 *           property="order_status",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="配送方式名称",
	 *           property="logistics_name",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="收货详细地址",
	 *           property="ship_address",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="收货人姓名",
	 *           property="ship_name",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="收货电话",
	 *           property="ship_phone",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="是否开发票 1=不发票 2=个人发票 3=公司发票",
	 *           property="tax_type",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="订单优惠金额",
	 *           property="order_pmt",
	 *           type="string"
	 *         ),
	 *         @OA\Property(
	 *           description="1未评论，2已评论",
	 *           property="is_comment",
	 *           type="integer"
	 *         ),
	 *         @OA\Property(
	 *           description="创建时间",
	 *           property="created_at",
	 *           type="integer"
	 *         ),
	 *         @OA\Property(
	 *           description="单品列表",
	 *           property="items",
	 *           type="array",
	 *           @OA\Items(
	 *             @OA\Property(
	 *               description="单品id",
	 *               property="id",
	 *               type="integer"
	 *             ),
	 *             @OA\Property(
	 *               description="商品id",
	 *               property="goods_id",
	 *               type="integer"
	 *             ),
	 *             @OA\Property(
	 *               description="商品名称",
	 *               property="name",
	 *               type="string"
	 *             ),
	 *             @OA\Property(
	 *               description="商品价格",
	 *               property="price",
	 *               type="string"
	 *             ),
	 *             @OA\Property(
	 *               description="商品数量",
	 *               property="num",
	 *               type="string"
	 *             ),
	 *             @OA\Property(
	 *               description="图片id",
	 *               property="image_id",
	 *               type="integer"
	 *             ),
	 *             @OA\Property(
	 *         	 	description="商品简介",
	 *           	  property="brief",
	 *           	  type="string"
	 *             ),
	 *           )
	 *         ),
	 *         @OA\Property(
	 *           description="单品图片",
	 *           property="imageItem",
	 *           type="array",
	 *           @OA\Items(
	 *             @OA\Property(
	 *               description="图片id",
	 *               property="id",
	 *               type="integer"
	 *             ),
	 *             @OA\Property(
	 *               description="图片地址",
	 *               property="file_url",
	 *               type="string"
	 *             ),
	 *           )
	 *         ),
	 *         @OA\Property(
	 *           description="拓展字段",
	 *           property="ext",
	 *           type="object", 
	 *           @OA\Property(
	 *             description="预约时间",
	 *             property="appointment",
	 *             type="string",
	 *           ),
	 *           @OA\Property(
	 *             description="图片",
	 *             property="images",
	 *             type="array", @OA\Items(
	 *               @OA\Property(
	 *             	 description="多个image_id",
	 *                 property="image_id",
	 *                 type="integer"
	 *               ),
	 *               example=1112233,
	 *			   )
	 *           ),
	 *           @OA\Property(
	 *             description="文字信息",
	 *             property="content",
	 *             type="string",
	 *           )
	 *         ),
	 *       ),
	 *     ),
	 *   ),
	 * )
	 */
	public function actionView() {
		if ($this->request_entrance == Token::TOKEN_TYPE_FRONTEND) {
			$field = ['id', 'order_amount', 'pay_amount', 'pay_status', 'ship_status', 'order_status', 'logistics_name', 'ship_address', 'ship_name', 'ship_phone', 'tax_type', 'order_pmt', 'is_comment', 'created_at' ];
		}else{
			$field = [];
		}

		$model = Order::find()->select($field)->with(['items', 'itemImages', 'ext'])->where(['id' => Yii::$app->request->get('id')])->one();
		$data = $model->toArray();
		$data['items'] = $model->items;
		$data['imageItem'] = $model->itemImages ? Tools::format_array($model->itemImages, ['file_url' => ['implode', ['', [Config::instance()->web_url, '###']], 'array']], 2) : (object) [];
		$data['userShipArea'] = $model->userShipArea();
		foreach ($model->ext as $item) {
			if ($item['field'] == 'images') {
				$image_ids = json_decode($item['val'], true);
				$images = File::find()->select(['file_url'])->where(['id'=>$image_ids])->asArray()->all();
				$item['val'] = $images ? Tools::format_array($images, ['file_url' => ['implode', ['', [Config::instance()->web_url, '###']], 'array']], 2) : (object) [];
			}
			$data['ext'][$item['field']] = $item['val'];
		}
		return $this->success($data);
	}

	/**
	 * Creates a new Order model.
	 * @OA\Post(path="/order/order/create",
	 *   summary="下单,购物车参数-单品参数二选一",
	 *   tags={"order模块"},
	 *   @OA\Parameter(
	 *     description="登录凭证",
	 *     name="X-Token",
	 *     in="header",
	 *     required=true,
	 *     @OA\Schema(
	 *       type="string"
	 *     )
	 *   ),
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\MediaType(
	 *       mediaType="application/json",
	 *       @OA\Schema(
	 *         @OA\Property(
	 *           description="购物车",
	 *           property="cart",
	 *           type="array", @OA\Items(
	 *             @OA\Property(
	 *           	 description="多个购物车id",
	 *               property="id",
	 *               type="integer"
	 *             ),
	 *             example=1,
	 *			 )
	 *         ),
	 *         @OA\Property(
	 *           description="单品id",
	 *           property="products",
	 *           type="array", @OA\Items(
	 *             @OA\Property(
	 *           	 description="单品id",
	 *               property="id",
	 *               type="integer"
	 *             ),
	 *             @OA\Property(
	 *           	 description="单品数量",
	 *               property="num",
	 *               type="integer"
	 *             ),
	 *			 )
	 *         ),
	 *         @OA\Property(
	 *           description="收货地址id",
	 *           property="ship_id",
	 *           type="integer",
	 *           example=1
	 *         ),
	 *         @OA\Property(
	 *           description="是否开发票 1=不发票 2=个人发票 3=公司发票",
	 *           property="tax_type",
	 *           type="string",
	 *           example="1"
	 *         ),
	 *         @OA\Property(
	 *           description="备注",
	 *           property="memo",
	 *           type="string",
	 *           example="备注信息"
	 *         ),
	 *         @OA\Property(
	 *           description="优惠券",
	 *           property="coupons",
	 *           type="array", @OA\Items(
	 *             @OA\Property(
	 *           	 description="多个优惠券id",
	 *               property="coupon_id",
	 *               type="integer"
	 *             ),
	 *             example=1,
	 *			 )
	 *         ),
	 *         @OA\Property(
	 *           description="返回支付参数 1是 2否",
	 *           property="pay",
	 *           type="integer",
	 *           example=2,
	 *         ),
	 *         @OA\Property(
	 *           description="订单类型 1通用(默认) 2充值 3长期订单 4其他",
	 *           property="type",
	 *           type="integer",
	 *           example=1,
	 *         ),
	 *         @OA\Property(
	 *           description="支付方式 (查看获取支付参数接口)",
	 *           property="order_code",
	 *           type="string",
	 *           example="wechatpay",
	 *         ),
	 *         @OA\Property(
	 *           description="支付类型",
	 *           property="order_type",
	 *           type="string",
	 *           example="lite",
	 *         ),
	 *         @OA\Property(
	 *           description="拓展字段",
	 *           property="ext",
	 *           type="object", 
	 *           @OA\Property(
	 *             description="预约时间",
	 *             property="appointment",
	 *             type="string",
	 *           ),
	 *           @OA\Property(
	 *             description="图片",
	 *             property="images",
	 *             type="array", @OA\Items(
	 *               @OA\Property(
	 *             	 description="多个image_id",
	 *                 property="image_id",
	 *                 type="integer"
	 *               ),
	 *               example=1112233,
	 *			   )
	 *           ),
	 *           @OA\Property(
	 *             description="文字信息",
	 *             property="content",
	 *             type="string",
	 *           )
	 *         ),
	 *       )
	 *     )
	 *   ),
	 *   @OA\Response(
	 *     response=200,
	 *     description="响应结构",
	 *     @OA\MediaType(
	 *         mediaType="application/json",
	 *         @OA\Schema(ref="#/components/schemas/response"),
	 *     ),
	 *   ),
	 * )
	 */
	public function actionCreate() {
		$parmas = Yii::$app->request->post();
		$validator = new FormValidate($parmas, ['scenario' => 'create_order']);
		if ($validator->validate()) {
			$model = new Order();
			// print_r($parmas);exit();
			$parmas['owner_id'] = $this->ownerId;
			$status = $model->saveData($parmas);
			if ($status && !empty($parmas['pay']) && $parmas['pay'] == 1) {
				$parmas['order_id'] = $model->id;
				$vtro = new FormValidate($parmas, ['scenario' => 'create_bill']);
				if ($vtro->validate()) {
					$payData['order_id'] = $model->id;
					$payData['money'] = $model->pay_amount;
					$payData['owner_id'] = $this->ownerId;
					$payData['user_id'] = $this->uid;
					$res = PlaceOrder::newBill(ucfirst(str_replace('pay', '', $parmas['order_code'])), $parmas['order_type'], $payData);
					return $res ? $this->success($res) : $this->fail(PlaceOrder::$error);
				}
				return $this->fail($vtro->errors);
			}
			return $status ? $this->success($model) : $this->fail($model->errors);
		}

		return $this->fail($validator->errors);
	}

	/**
	 * Updates an existing Order model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param string $id
	 * @return mixed
	 * @throws HttpException if the model cannot be found
	 */
	public function actionUpdate() {
		$model = $this->findModel($id);

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->success($model);
		}

		return $this->fail($model->errors);
	}

	/**
	 * Deletes an existing Order model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param string $id
	 * @return mixed
	 * @throws HttpException if the model cannot be found
	 */
	public function actionDelete() {
		$id = Yii::$app->request->post('id');
		$transaction = Order::getDb()->beginTransaction();

		try {
			if (is_array($id)) {
				$n = Order::deleteAll(['id' => $id, 'user_id' => $this->uid]);
			} else {
				$item = $this->findModel($id);
			}

			OrderItem::deleteAll(['order_id' => $id]);
			if (is_array($id)) {
				if ($n != count($id)) {
					$transaction->rollBack();
					Tools::exceptionBreak(40003);
				}
			} else {
				$item->delete();
			}

			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}

		return $this->success($n);
	}

	/**
	 * Finds the Order model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param string $id
	 * @return Order the loaded model
	 * @throws HttpException if the model cannot be found
	 */
	protected function findModel($id) {
		$model = Order::find($this->dataOwnerUid())->andWhere(['id' => $id])->one();
		if ($model !== null) {
			return $model;
		}

		throw new HttpException(200, Yii::t('base', 40001));
	}
}
