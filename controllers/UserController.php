<?php

namespace app\controllers;

use app\models\Role;
use app\models\User;
use Yii;
use yii\filters\auth\HttpBearerAuth;

class UserController extends \yii\rest\ActiveController
{
    public $enableCsrfValidation = '';
    public $modelClass = '';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => [(isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "http://" . $_SERVER['REMOTE_ADDR'])],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ],
            'actions' => [
                'logout' => [
                    'Access-Control-Allow-Credentials' => true,
                ]
            ]
        ];

        $auth =  [
            'class' => HttpBearerAuth::class,
            'only' => ['logout']
        ];
        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }
    public function actions()
    {
        $actions = parent::actions();

        // disable the "delete" and "create" actions
        unset($actions['delete'], $actions['create']);

        // customize the data provider preparation with the "prepareDataProvider()" method
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }

    public function actionRegister()
    {
        $model = new User();
        $model->scenario = 'register';
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $model->role_id = Role::getRoleId('user');
            $model->token = Yii::$app->security->generateRandomString();
            $model->password = Yii::$app->security->generatePasswordHash($model->password);
            $model->save(false);
            Yii::$app->response->statusCode = 201;
            $result = [
                'data' => [
                    'user' => [
                        'id' => $model->id,
                        'name' => $model->name,
                        'email' => $model->email,
                    ],
                    'code' => 201,
                    'message' => 'Пользователь создан'
                ]
            ];
        } else {
            $result = [
                'error' => [
                    'errors' => $model->errors,
                    'code' => 422,
                    'message' => 'Validation Error'
                ]
            ];
            Yii::$app->response->statusCode = 422;
        }
        return $result;
    }

    public function actionLogin()
    {
        $model = new User();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $user = User::findOne(['email' => $model->email]);
            if ($user && $user->validatePassword($model->password)) {

                $user->token = Yii::$app->security->generateRandomString();
                $user->save(false);
                Yii::$app->response->statusCode = 200;
                $result = [
                    'data' => [
                        'token' => $user->token,
                        'user' => [
                            'id' => $user->id,
                            'name' => $model->name,
                            'email' => $model->email,
                        ],
                        'code' => 200,
                        'message' => 'Пользователь авторизован'
                    ]
                ];
            } else {
                Yii::$app->response->statusCode = 401;
                return '';
            }
        } else {
            $result = [
                'error' => [
                    'errors' => $model->errors,
                    'code' => 422,
                    'message' => 'Validation Error'
                ]
            ];
            Yii::$app->response->statusCode = 422;
        }
        return $result;
    }

    public function actionLogout()
    {
        $user = User::findOne(Yii::$app->user->id);
        $user->token = null;
        $user->save(false);
        Yii::$app->response->statusCode = 204;
        return '';
    }
}
