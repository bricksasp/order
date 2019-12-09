<?php
namespace bricksasp\order\controllers;

use Yii;
use bricksasp\order\models\Cart;
use yii\data\ActiveDataProvider;
use bricksasp\base\BaseController;
use yii\web\HttpException;
use bricksasp\base\Config;
/**
 * CartController implements the CRUD actions for Cart model.
 */
class CartController extends BaseController
{
    /**
     * 登录可访问 其他需授权
     * @return array
     */
    public function allowAction() {
        return [
            'add',
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
     * Lists all Cart models.
     * @OA\Get(path="/order/cart/index",
     *   summary="购物车列表",
     *   tags={"order模块"},
     *   @OA\Parameter(
     *     description="用户请求token",
     *     name="X-Token",
     *     in="header",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
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
     *     description="列表结构",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/cartlist"),
     *     ),
     *   ),
     * )
     *
     * @OA\Schema(
     *   schema="cartlist",
     *   description="单品列表结构",
     *   allOf={
     *     @OA\Schema(
     *       @OA\Property(property="id", type="integer", description="单品id"),
     *       @OA\Property(property="num", type="string", description="单品数量"),
     *       @OA\Property( property="price", type="string", description="价格"),
     *       @OA\Property(property="product", description="单品标签", ref="#/components/schemas/product"),
     *       @OA\Property(property="image", description="封面图", ref="#/components/schemas/file")
     *     )
     *   }
     * )
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->queryParams;
        $dataProvider = new ActiveDataProvider([
            'query' => Cart::find($this->dataOwnerUid())->with(['image', 'product']),
            'pagination' => [
                'pageSize' => $params['pageSize'] ?? 10,
            ],
        ]);

        return $this->pageFormat($dataProvider,['product'=>false, 'image' => [['file_url'=>['implode',['',[Config::instance()->web_url,'###']],'array']]]]);
    }

    /**
     * Creates a new Cart model.
     * @OA\Post(path="/order/cart/add",
     *   summary="添加购物车",
     *   tags={"order模块"},
     *   @OA\Parameter(
     *     description="用户请求token",
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
     *           description="单品id",
     *           property="product_id",
     *           type="integer"
     *         ),
     *         @OA\Property(
     *           description="数量",
     *           property="num",
     *           type="integer"
     *         ),
     *         @OA\Property(
     *           description="图片id",
     *           property="image_id",
     *           type="string"
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
    public function actionAdd()
    {
        $data = Yii::$app->request->post();
        $model = Cart::find()->where(['user_id' => $this->uid, 'owner_id' => $this->ownerId, 'product_id' => $data['product_id']])->one();
        if ($model) {
            $data['num'] = $model->num + (int)$data['num'];
            $data['num'] = $data['num'] < 1 ? 1: $data['num'];
        }else{
            $model = new Cart();
            $data['owner_id'] = $this->ownerId;
        }
        // print_r($data);exit;
        if ($model->load($data) && $model->save()) {
            return $this->success();
        }

        return $this->success($model->errors);
    }

    /**
     * Deletes an existing Cart model.
     * @OA\Post(path="/order/cart/delete",
     *   summary="删除购物车",
     *   tags={"order模块"},
     *   @OA\Parameter(
     *     description="用户请求token",
     *     name="X-Token",
     *     in="header",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/carts"),
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(ref="#/components/schemas/carts")
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
     *
     * @OA\Schema(
     *   schema="carts",
     *   description="单品列表结构",
     *   allOf={
     *     @OA\Schema(
     *       @OA\Property(
     *         description="购物车",
     *         property="cart",
     *         type="array", @OA\Items(
     *           @OA\Property(
     *             description="多个购物车id",
     *             property="id",
     *             type="integer",
     *             default="1",
     *           ),
     *           example=1,
     *         )
     *       )
     *     )
     *   }
     * )
     */
    public function actionDelete()
    {
        $params = Yii::$app->request->post('cart');
        if (!is_array($params)) {
            $params = explode(',', trim($params,','));
        }
        return Cart::deleteAll(['in', 'id', $params]) ? $this->success():$this->fail();
    }
}
