<?php
namespace bricksasp\order\controllers;

use Yii;
use yii\web\Controller;

class DefaultController extends Controller {

	public function actions() {
		return [
			'error' => [
				'class' => \bricksasp\base\actions\ErrorAction::className(),
			]
		];
	}

	public function actionIndex() {
		return $this->render('index');
	}
}
