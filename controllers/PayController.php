<?php
namespace bricksasp\order\controllers;

use Yii;
use bricksasp\base\BaseController;
use bricksasp\order\models\FormValidate;
use bricksasp\order\models\Order;
use bricksasp\payment\models\PlaceOrder;
use bricksasp\payment\models\platform\Wechat;
use bricksasp\payment\models\BillPay;
use WeChat\Pay;
use WeChat\Contracts\Tools;

class PayController extends BaseController {
	/**
	 * 免登录可访问
	 * @return array
	 */
	public function allowNoLoginAction() {
		return [
			'payed',
			'wxnotify',
			'alinotify',
			'cmbnotify',
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
	 * @OA\Post(path="/order/pay/params",
	 *   summary="获取支付参数",
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
	 *           type="string",
	 *           default="wechatpay"
	 *         ),
	 *         @OA\Property(
	 *           description="支付类型 wechatpay 对应(app:app支付,bar:刷卡支付,lite:小程序支付,pub:公众号,qr:扫码支付,wap:H5手机网站支付) alipay 对应(app:app支付,bar:刷卡支付,qr:扫码支付,wap:支付宝手机网站支付,web:电脑支付（即时到账）) cmbpay 对应(无)",
	 *           property="order_type",
	 *           type="string",
	 *           default="lite"
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
	public function actionWxnotify() {
		try {
			$xml = file_get_contents('php://input');
        	$data = Tools::xml2arr($xml);
        	$map = json_decode(base64_decode($data['attach']),true);
        	if (!is_array($map)) {
				return $this->asXml(['return_code' => 'FAIL', 'return_msg' => 'FAIL']);
        	}
			$config = Wechat::config($map['owner_id']);
			$wechat = \WeChat\Pay::instance($config);

		    if (isset($data['sign']) && $wechat->getPaySign($data) === $data['sign'] && $data['return_code'] === 'SUCCESS' && $data['result_code'] === 'SUCCESS') {
				file_put_contents(Yii::getAlias('@runtime') . '/pay.log', $xml . PHP_EOL,FILE_APPEND);
		        $bill = BillPay::find()->where(['payment_id' => $data['out_trade_no']])->one();
		        $bill->status = 2;
		        Order::updateAll(['pay_status' => 2],['id' => $bill->order_id]);
		        $bill->save();
		        ob_clean();
				return $this->asXml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
		    }
			return $this->asXml(['return_code' => 'FAIL', 'return_msg' => 'FAIL']);
		} catch (Exception $e) {
			return $this->asXml(['return_code' => 'FAIL', 'return_msg' => $e->getMessage()]);
		}
	}

	/**
	 * 阿里支付回调
	 * @return mixed
	 */
	public function actionAlinotify() {
		try {

		} catch (Exception $e) {
			return $e->getMessage();
		}

		return $ret;
	}

	/**
	 * 招行支付回调
	 * @return mixed
	 */
	public function actionCmbnotify() {
		try {

		} catch (Exception $e) {
			return $e->getMessage();
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
