<?php
namespace bricksasp\order\controllers;

use Payment\Client\Notify;
use Payment\Common\PayException;
use Payment\Config;
use bricksasp\base\BaseController;
use bricksasp\order\models\FormValidate;
use bricksasp\order\models\Order;
use bricksasp\payment\models\PlaceOrder;
use Yii;

class PayController extends BaseController {
	/**
	 * 免登录可访问
	 * @return array
	 */
	public function allowNoLoginAction() {
		return [
			'payed',
			'wx',
			'ali',
			'cmb',
		];
	}

	/**
	 * 登录可访问 其他需授权
	 * @return array
	 */
	public function allowAction() {
		return [
			'params',
		];
	}

	/**
	 * 获取支付参数
	 * @OA\Post(path="/pay/params",
	 *   summary="获取支付参数",
	 *   tags={"order模块"},
	 *   @OA\Parameter(
	 *     description="用户请求token,登录后填写",
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
	 *       mediaType="multipart/form-data",
	 *       @OA\Schema(
	 *         @OA\Property(
	 *           description="订单id",
	 *           property="order_id",
	 *           type="integer"
	 *         ),
	 *         @OA\Property(
	 *           description="支付方式 (wechatpay,alipay,cmbpay)",
	 *           property="order_code",
	 *           type="integer"
	 *         ),
	 *         @OA\Property(
	 *           description="支付类型 wechatpay 对应(app:app支付,bar:刷卡支付,lite:小程序支付,pub:公众号,qr:扫码支付,wap:H5手机网站支付) alipay 对应(app:app支付,bar:刷卡支付,qr:扫码支付,wap:支付宝手机网站支付,web:电脑支付（即时到账）) cmbpay 对应(无)",
	 *           property="order_type",
	 *           type="string"
	 *         )
	 *       )
	 *     )
	 *   ),
	 *   @OA\Response(
	 *     response=200,
	 *     description="商品详情结构",
	 *     @OA\MediaType(
	 *         mediaType="application/json",
	 *         @OA\Schema(ref="#/components/schemas/payParam"),
	 *     ),
	 *   ),
	 * )
	 *
	 *
	 *
	 * @OA\Schema(
	 *   schema="payParam",
	 *   description="对应支付参数结构"
	 * )
	 */
	public function actionParams() {
		$param = Yii::$app->request->post();
		$validator = new FormValidate($param, ['scenario' => 'create_bill']);
		if ($validator->validate()) {
			$order = Order::find()->select(['id', 'pay_amount'])->where(['id' => $param['order_id']])->one();
			$data['order_id'] = $order['id'];
			$data['money'] = $order['pay_amount'];
			$data['owner_id'] = $this->ownerId;
			$data['user_id'] = $this->uid;
			$res = PlaceOrder::newBill(ucfirst(str_replace('pay', '', $param['order_code'])), $param['order_type'], $data);
			return $res ? $this->success($res) : $this->fail(PlaceOrder::$error);
		}
		return $this->fail($validator->errors);
	}

	/**
	 * 微信支付回调
	 * @return mixed
	 */
	public function actionWx() {
		try {
			//$retData = Notify::getNotifyData($type, $config);// 获取第三方的原始数据，未进行签名检查

			$ret = Notify::run(Config::WX_CHARGE, $config, $callback); // 处理回调，内部进行了签名检查
			return $this->asXml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
		} catch (PayException $e) {
			$e->errorMessage();
		}
		return $this->asXml(['return_code' => 'FAIL', 'return_msg' => 'FAIL']);
	}

	/**
	 * 阿里支付回调
	 * @return mixed
	 */
	public function actionAli() {
		try {
			//$retData = Notify::getNotifyData($type, $config);// 获取第三方的原始数据，未进行签名检查

			$ret = Notify::run(Config::ALI_CHARGE, $config, $callback); // 处理回调，内部进行了签名检查
		} catch (PayException $e) {
			return $e->errorMessage();
		}

		return $ret;
	}

	/**
	 * 招行支付回调
	 * @return mixed
	 */
	public function actionCmb() {
		try {
			//$retData = Notify::getNotifyData($type, $config);// 获取第三方的原始数据，未进行签名检查

			$ret = Notify::run(Config::CMB_CHARGE, $config, $callback); // 处理回调，内部进行了签名检查
		} catch (PayException $e) {
			return $e->errorMessage();
		}

		return $ret;
	}

	/**
	 * 支付成功后同步跳转页面
	 */
	public function actionPayed() {
		return $this->render('view', [
			'model' => $this->findModel($id),
		]);
	}
}
