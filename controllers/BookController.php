<?php

namespace app\controllers;

use app\models\Book;
use app\models\File;
use app\models\Pager;
use app\models\Progress;
use app\models\Role;
use app\models\User;
use Codeception\Constraint\Page;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;

class BookController extends \yii\rest\ActiveController
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
                'new' => [
                    'Access-Control-Allow-Credentials' => true,
                ],
                'delete-book' => [
                    'Access-Control-Allow-Credentials' => true,
                ],
                'edit-book' => [
                    'Access-Control-Allow-Credentials' => true,
                ],
                'save-progress' => [
                    'Access-Control-Allow-Credentials' => true,
                ],
            ]
        ];

        $auth =  [
            'class' => HttpBearerAuth::class,
            'optional' => ['get-books', 'get-book']
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

    public function actionGetBooks()
    {
        if ($data = Yii::$app->request->post()) {
            $model = new Pager();
            $model->load($data, '');
            if ($model->validate()) {
                $books = Book::getBook();
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $books,
                    'pagination' => [
                        'page' => $data['page']--,
                        'pageSize' => $data['count']
                    ]

                ]);
                $result = [
                    'data' => [
                        'books' => [...$dataProvider->getModels()],
                        'total_books' => $dataProvider->getTotalCount(),
                        'code' => 200,
                        'message' => 'Список книг страницы получен'
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
            return $this->asJson($result);
        }

        if (Yii::$app->user->isGuest) {
            $books = Book::getBook([
                'is_public' => 1
            ]);
            $result = [
                'data' => [
                    'books' => $books,
                    'code' => 200,
                    'message' => 'Список книг получен'
                ]
            ];
        } else {
            if (!Yii::$app->user->identity->isAdmin) {
                $books = Book::getBook([
                    'user_id' =>  Yii::$app->user->id
                ]);
                $result = [
                    'data' => [
                        'books' => $books,
                        'code' => 200,
                        'message' => 'Список книг пользователя получен'
                    ]
                ];
            } else {
                $books = Book::getBook();
                $result = [
                    'data' => [
                        'books' => $books,
                        'code' => 200,
                        'message' => 'Список книг пользователя получен'
                    ]
                ];
            }
        }
        return $this->asJson($result);
    }
    public function actionGetBook($id)
    {

        if (Yii::$app->user->isGuest) {
            $books = Book::getBook([
                'book_id' => $id
            ]);
            if ($books) {
                if ($books['is_public']) {

                    $result = [
                        'data' => [
                            'books' => $books,
                            'code' => 200,
                            'message' => 'книга получена'
                        ]
                    ];
                } else {
                    Yii::$app->response->statusCode = 403;
                    return '';
                }
            } else {
                Yii::$app->response->statusCode = 404;
                return '';
            }
        } else {
            if (!Yii::$app->user->identity->isAdmin) {

                $book = Book::getBook([
                    'book_id' => $id
                ]);
                if ($book) {
                    if ($book['user_id'] == Yii::$app->user->id) {
                        $result = [
                            'data' => [
                                'books' => $book,
                                'code' => 200,
                                'message' => 'Информаиция о книге получена'
                            ]
                        ];
                    }
                } else {
                    Yii::$app->response->statusCode = 403;
                    return '';
                }
            } else {
                Yii::$app->response->statusCode = 404;
                return '';
            }
        }
        return $this->asJson($result);
    }


    public function actionNew()
    {
        $model = new Book();
        $model->scenario = 'create';
        $model->load(Yii::$app->request->post(), '');
        $model->file = UploadedFile::getInstanceByName('file');
        if ($model->validate()) {
            $file = new File();
            $file->title = $model->upload();
            $file->save(false);
            $model->file_id = $file->id;
            $model->user_id = Yii::$app->user->id;
            $model->is_public = 1;
            $model->save(false);
            $result = [
                'data' => [
                    'book' => [
                        'title' => $model->title,
                        'author' => $model->author,
                        'description' => $model->description,
                        'file' => Yii::$app->request->getHostInfo() . '/books/' . $file->title,
                    ],
                    'code' => 200,
                    'message' => 'Книга создана'
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
        return $this->asJson($result);
    }


    public function actionDeleteBook($id)
    {
        $book = Book::findOne($id);
        if ($book) {
            if ($book->user_id == Yii::$app->user->id) {
                $book->delete();
                return  [
                    'data' => [
                        'code' => 200,
                        'message' => 'Книга успешно удалена'
                    ]
                ];
            } else {
                Yii::$app->response->statusCode = 403;
                return '';
            }
        } else {
            Yii::$app->response->statusCode = 404;
            return '';
        }
    }

    public function actionEditBook($id)
    {
        $book = Book::findOne($id);
        if ($book) {
            if ($book->user_id == Yii::$app->user->id) {
                $book->load(Yii::$app->request->post(), '');
                $book->save(false);
                $result = [
                    'data' => [
                        'book' => [
                            'title' => $book->title,
                            'author' => $book->author,
                            'description' => $book->description,
                            'file' => Yii::$app->request->getHostInfo() . '/books/' . $book->title,
                        ],
                        'code' => 200,
                        'message' => 'Информация о книге обновлена'
                    ]
                ];
                return $result;
            } else {
                Yii::$app->response->statusCode = 403;
                return '';
            }
        } else {
            Yii::$app->response->statusCode = 404;
            return '';
        }
    }

    public function actionSaveProgress($id)
    {
        $book = Book::findOne($id);
        if ($book) {
            if ($book->user_id == Yii::$app->user->id) {
                $progress = new Progress();
                $progress->load(Yii::$app->request->post(), '');
                if ($progress->validate()) {
                    $progress->book_id = $book->id;
                    $progress->user_id = Yii::$app->user->id;
                    $progress->Save(false);
                    $result = [
                        'data' => [
                            'book_id' => $book->id,
                            'progress' => $progress->progress,
                            'code' => 200,
                            'message' => 'Прогресс чтения saved'
                        ]
                    ];
                    return $result;
                } else {
                    return    [
                        'error' => [
                            'errors' => $progress->errors,
                            'code' => 422,
                            'message' => 'Validation Error'
                        ]
                    ];
                    Yii::$app->response->statusCode = 422;
                }
            } else {
                Yii::$app->response->statusCode = 403;
                return '';
            }
        } else {
            Yii::$app->response->statusCode = 404;
            return '';
        }
    }
    public function actionGetReadingBooks()
    {
        $books = Book::getBook([
            'user_p_id' => Yii::$app->user->id,
            'in_progress' => 1
        ]);
        $result = [
            'data' => [
                'books' => $books,
                'code' => 200,
                'message' => 'Список книг получен'
            ]
        ];
        return $result;
    }
}
