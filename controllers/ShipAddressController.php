<?php
namespace bricksasp\order\controllers;

use Yii;
use bricksasp\order\models\ShipAddress;
use yii\data\ActiveDataProvider;
use bricksasp\base\BaseController;
use yii\web\HttpException;

/**
 * ShipAddressController implements the CRUD actions for ShipAddress model.
 */
class ShipAddressController extends BaseController
{
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

    public function allowNoLoginAction() {
        return [];
    }

    /**
     * Lists all ShipAddress models.
     * @return mixed
     * 
     * @OA\Get(path="/order/ship-address/index",
     *   summary="收货地址列表",
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
     *   
     *   @OA\Response(
     *     response=200,
     *     description="返回数据",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/shipAddressList"),
     *     ),
     *   ),
     * )
     *
     * @OA\Schema(
     *   schema="shipAddressList",
     *   description="地区树结构",
     *   allOf={
     *     @OA\Schema(
     *       @OA\Property(property="id", type="integer", description="收货地址id"),
     *       @OA\Property(property="detail", type="string", description="详细地址"),
     *       @OA\Property(property="name", type="string", description="收货人"),
     *       @OA\Property(property="phone", type="integer", description="收货电话"),
     *       @OA\Property(property="is_def", type="integer", description="是否默认地址1是2否"),
     *       @OA\Property(property="area", description="是否默认地址1是2否", ref="#/components/schemas/regionData"),
     *
     * 
     *     )
     *   }
     * )
     * 
     * @OA\Schema(
     *   schema="regionData",
     *   description="地区树结构",
     *   allOf={
     *     @OA\Schema(
     *       @OA\Property(property="id", type="integer", description="地区id"),
     *       @OA\Property(property="code", type="integer", description="编码"),
     *       @OA\Property( property="name", type="string", description="区域名称"),
     *       @OA\Property( property="parent_id", type="integer", description="上级id"),
     *     )
     *   }
     * )
     * 
     */
    public function actionIndex()
    {
        $data = ShipAddress::find()->with(['province_3'])->where(['user_id' => $this->uid])->asArray()->all();
        // print_r($data[0]);exit();
        return $this->success($data);
    }

    /**
     * Displays a single ShipAddress model.
     * @OA\Get(path="/order/ship-address/view",
     *   summary="收货地址详情",
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
     *     description="数据id",
     *     name="id",
     *     in="query",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),

     *   @OA\Response(
     *     response=200,
     *     description="返回数据",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/shipAddressDetail"),
     *     ),
     *   ),
     * )
     *
     * @OA\Schema(
     *   schema="shipAddressDetail",
     *   description="收货地址结构",
     *   allOf={
     *     @OA\Schema(
     *       @OA\Property(property="id", type="integer", description="地址id"),
     *       @OA\Property(property="name", type="string", description="收货人姓名"),
     *       @OA\Property(property="detail", type="string", description="收货详细地址"),
     *       @OA\Property( property="phone", type="string", description="收货电话"),
     *       @OA\Property( property="area_id", type="integer", description="收货地区ID" )
     *     )
     *   }
     * )
     */
    public function actionView()
    {
        return $this->success($this->findModel());
    }

    /**
     * Creates a new ShipAddress model.
     * @OA\Post(path="/order/ship-address/create",
     *   summary="添加收货地址",
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
     *           description="收货人姓名",
     *           property="name",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="收货电话",
     *           property="phone",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="详细地址",
     *           property="detail",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="是否默认 1=是 2=否Z",
     *           property="is_def",
     *           type="integer"
     *         ),
     *         @OA\Property(
     *           description="地区id",
     *           property="area_id",
     *           type="integer"
     *         )
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
    public function actionCreate()
    {
        $model = new ShipAddress();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->success($model);
        }

        return $this->fail($model->errors);
    }

    /**
     * Updates an existing ShipAddress model.
     * @OA\Post(path="/order/ship-address/update",
     *   summary="更新收货地址",
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
     *           description="地址id",
     *           property="id",
     *           type="integer"
     *         ),
     *         @OA\Property(
     *           description="收货人姓名",
     *           property="name",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="收货电话",
     *           property="phone",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="详细地址",
     *           property="detail",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="是否默认 1=是 2=否Z",
     *           property="is_def",
     *           type="integer"
     *         ),
     *         @OA\Property(
     *           description="地区id",
     *           property="area_id",
     *           type="integer"
     *         )
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
    public function actionUpdate()
    {
        $model = $this->findModel();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->success($model);
        }

        return $this->fail($model->errors);
    }

    /**
     * Deletes an existing ShipAddress model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws HttpException if the model cannot be found
     */
    public function actionDelete()
    {
        return $this->success($this->findModel()->delete());
    }

    /**
     * Finds the ShipAddress model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ShipAddress the loaded model
     * @throws HttpException if the model cannot be found
     */
    protected function findModel()
    {
        $id = Yii::$app->request->get('id');
        $id = $id ? $id : Yii::$app->request->post('id');
        if (($model = ShipAddress::findOne($id)) !== null) {
            return $model;
        }

        throw new HttpException('The requested page does not exist.');
    }
}
