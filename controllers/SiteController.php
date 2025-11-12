<?php

namespace app\controllers;

use app\models\dto\AuthorDto;
use app\models\form\AuthorForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\widgets\ActiveForm;
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
                    // 'send-msg' => ['post'],
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

        // $aut = Author::find()->where(['id' => ])->one();
        // $top = Topic::find()->where(['id' => 265664753])->one();
        // dd($aut);
        // for ($i = 0; $i < 3; $i++) {
        //     $author = new Author();
        //     $author->email = $faker->email;
        //     $author->name = $faker->name;
        //     $author->msg = $faker->text;

        //     $topic = new Topic();
        //     $topic->title = $faker->sentence;
        //     $topic->datetime = date('Y-m-d');
        //     $topic->setExcerpt($faker->paragraphs(10));
        //     $topic->url = $faker->url();
        //     $topic->setAuthor($author);
        //     $topics[] = $topic;
        // }

        
        $res = $this->prepareSubmit($model);
        if ($res !== null) {
            return $res;
        }

        $topics = Topic::find()->all();
        
        return $this->render('index', [
            'topics' => $topics,
            'model' => $model
        ]);
    }

    /**
     * Handle form submit. Returns:
     *  - null        => no POST or render should continue (render index)
     *  - Response    => redirect 
     */
    private function prepareSubmit($aform)
     {
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
        // dd($topic->excerpt);
        $record_saved = $topic->setAuthor($author);
        if ($record_saved) {
            Yii::$app->session->setFlash('success', 'Ваше сообщение отправлено.');
            return $this->redirect($request->referrer ?: ['site/index']);
        } 
        
        Yii::error($author->getErrors(), __METHOD__);

        Yii::$app->session->setFlash('error', 'Не удалось сохранить данные. Попробуйте позже.');
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
