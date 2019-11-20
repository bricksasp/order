<?php
namespace bricksasp\order\controllers;

use Yii;
use yii\web\Controller;

class DefaultController extends Controller {

	public function actions() {
		return [
			'error' => [
				'class' => \bricksasp\base\actions\ErrorAction::className(),
			],
			'api-docs' => [
				'class' => 'genxoft\swagger\ViewAction',
				'apiJsonUrl' => \yii\helpers\Url::to(['api-json'], true),
			],
			'api-json' => [
				'class' => 'genxoft\swagger\JsonAction',
				'dirs' => [
                    Yii::getAlias('@bricksasp/base'),
					dirname(__DIR__)
				],
			],
		];
	}

	public function actionIndex() {
		return $this->render('index');
	}
}
