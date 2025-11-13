<?php

namespace app\controllers;

use app\models\form\AuthorForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\Topic;
use app\models\Author;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $faker = \Faker\Factory::create();
        $model = new AuthorForm();
        
        $res = $this->prepareSubmit($model);
        if ($res !== null) {
            return $res;
        }

        $topics = Topic::find()->with('author')->all();
        
        return $this->render('index', [
            'topics' => $topics,
            'model' => $model
        ]);
    }

    /**
     * Helper form submit. Returns:
     *  - null        => render should continue (render index)
     *  - Response    => redirect 
     */
    private function prepareSubmit($aform)
     {
        // dd(['14\'th author' => Author::findOne(14)]);
        $request = Yii::$app->request;

        if (!$aform->load($request->post()) || !$aform->validate()) {
            return null;
        }

        $author = new Author();
        $author->name = $aform->name;
        $author->email = $aform->email;
        $author->msg = $aform->msg;
        
        $faker = \Faker\Factory::create();
        $topic = new Topic();
        $topic->title = $author->name;
        $topic->datetime = date('Y-m-d');
        $topic->url = $faker->url();
        if (str_contains($author->msg, '\n'))
            $paragraphs = explode("\n", $author->msg);
        else $paragraphs[] = $author->msg;
        $topic->setExcerpt($paragraphs);

        $author_exist = Author::findOne(['email' => $author->email]);
        if ($author_exist) {
            $author_exist->name = $author->name;
            $author_exist->msg = $author->msg;
            $author = $author_exist;
            $topic->author_id = $author_exist->id;
        }
        if ($topic->validate()) {
            $record_saved = $topic->setAuthor($author);
            if ($record_saved && $topic->save(false)) {
                Yii::$app->session->setFlash('success', 'Сообщение успешно опубликован.');
                return $this->refresh();

            } else {
                Yii::$app->session->setFlash('error', 'Не удалось сохранить данные. Попробуйте позже.');
                return $this->redirect($request->referrer ?: ['site/index']);
            }
        } else {
            if ($author->hasErrors()) {
                Yii::error($author->getErrors(), __METHOD__);
                $msgs = $author->getErrors();
                Yii::$app->session->setFlash('error', implode('. ', $msgs));
            }
                
            Yii::error($topic->getErrors(), __METHOD__);
            if ($topic->hasErrors('author_id')) {
                $msgs = $topic->getErrors('author_id');
                dd($msgs);
                Yii::$app->session->setFlash('error', implode('. ', $msgs));
            }

        }
        
        return $this->redirect($request->referrer ?: ['site/index']);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
