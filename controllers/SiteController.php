<?php

namespace app\controllers;

use app\models\form\AuthorForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
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
                'only' => ['index'],
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@', '?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                'index' => ['GET', 'POST'],
                'edit-topic' => ['GET', 'POST'],
                'delete' => ['POST'],
                'delete-confirm' => ['GET'],
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
     * Display the main topics list page.
     *
     * Shows published (non-deleted) topics and prepares the contact/author form.
     *
     * @return string Rendered index page.
    */
    public function actionIndex()
    {
        $faker = \Faker\Factory::create();
        $model = new AuthorForm();

        $res = $this->prepareSubmit($model);
        if ($res !== null) {
            return $res;
        }

        $topics = Topic::find()
        ->with('author')
        ->where(['deleted_at' => null])
        ->orderBy(['published_at' => SORT_DESC])
        ->all();

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

         $paragraphs = [];
        if (mb_strpos($author->msg, "\n") !== false) {
            $parts = preg_split("/\r\n|\n|\r/", $author->msg);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $paragraphs[] = $p;
                }
            }
            if (empty($paragraphs)) {
                $paragraphs[] = trim($author->msg);
            }
        } else {
            $paragraphs[] = trim($author->msg);
        }
        $topic->setExcerpt($paragraphs);

        $author_exist = Author::findOne(['email' => $author->email]);
        if ($author_exist) {
            $author_exist->name = $author->name;
            $author_exist->msg = $author->msg;
            $author = $author_exist;
            $topic->author_id = $author_exist->id;
        }

        $topic->generateManagementTokens();

        if ($topic->validate()) {
            $record_saved = $topic->setAuthor($author);
            if ($record_saved && $topic->save(false)) {
                $this->sendManagementMail($author->email, $author->name, $topic);

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
                Yii::$app->session->setFlash('error', implode('. ', $msgs));
            }
        }

        return $this->redirect($request->referrer ?: ['site/index']);
    }

    /**
     * Display edit form or update topic via a private edit token.
     *
     * GET: show the edit form if the edit token is valid and the edit window (12 hours) is open.
     * POST: accept edited message content, validate the same rules as on creation, save changes.
     *
     * @param string $token Private edit token sent to the author by email.
     * @return string|\yii\web\Response Render form (GET) or redirect after POST.
     * @throws \yii\web\NotFoundHttpException if token is invalid or topic not found.
     */
    public function actionEditTopic($token)
    {
        $topic = Topic::find()->where(['edit_token' => $token])->one();
        if (!$topic || !$topic->canEdit()) {
            Yii::$app->session->setFlash('error', 'Ссылка недействительна или срок редактирования истёк.');
            return $this->redirect(['site/index']);
        }

        // позволяем редактировать только содержание (excerpt)
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $msg = $post['excerpt'] ?? null;
            if ($msg === null) {
                Yii::$app->session->setFlash('error', 'Данные не переданы.');
                return $this->refresh();
            }
            // trim and validate not only-spaces
            $msgTrim = trim($msg);
            if ($msgTrim === '' || preg_replace('/\s+/u', '', $msgTrim) === '') {
                Yii::$app->session->setFlash('error', 'Сообщение не может состоять исключительно из пробелов.');
                return $this->refresh();
            }
            // параграфы
            $paragraphs = [];
            if (mb_strpos($msgTrim, "\n") !== false) {
                $parts = preg_split("/\r\n|\n|\r/", $msgTrim);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '') {
                        $paragraphs[] = $p;
                    }
                }
                if (empty($paragraphs)) {
                    $paragraphs[] = $msgTrim;
                }
            } else {
                $paragraphs[] = $msgTrim;
            }

            $topic->setExcerpt($paragraphs);

            if ($topic->validate(['excerpt'])) {
                if ($topic->save(false, ['excerpt', 'updated_at'])) {
                    Yii::$app->session->setFlash('success', 'Топик успешно обновлён.');
                    return $this->redirect(['site/index']);
                } else {
                    Yii::$app->session->setFlash('error', 'Не удалось сохранить изменения.');
                    return $this->refresh();
                }
            } else {
                Yii::$app->session->setFlash('error', implode('. ', $topic->getFirstErrors()));
                return $this->refresh();
            }
        }

        return $this->render('edit-topic', ['topic' => $topic]);
    }

    /**
     * Show delete confirmation page for a topic by private delete token.
     *
     * GET: display a confirmation page if the delete token is valid and the delete window (14 days) is open.
     *
     * @param string $token Private delete token sent to the author by email.
     * @return string Render confirmation page.
     * @throws \yii\web\NotFoundHttpException if token is invalid or topic not found.
     */
    public function actionDeleteConfirm($token)
    {
        $topic = Topic::find()->where(['delete_token' => $token])->one();
        if (!$topic || !$topic->canDelete()) {
            Yii::$app->session->setFlash('error', 'Ссылка недействительна или срок удаления истёк.');
            return $this->redirect(['site/index']);
        }

        return $this->render('delete-confirm', ['topic' => $topic]);
    }


    /**
     * Perform soft delete of a topic (POST only).
     *
     * Expects a POST request with a valid delete token. Marks the topic as deleted
     * by setting deleted_at (soft delete). Returns a PRG redirect and a flash message.
     *
     * @param string $token Private delete token.
     * @return \yii\web\Response Redirect to index after action.
     * @throws \yii\web\BadRequestHttpException if request is not POST.
     * @throws \yii\web\NotFoundHttpException if token is invalid or topic not found.
     */
    public function actionDelete($token)
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return $this->redirect(['site/index']);
        }

        $topic = Topic::find()->where(['delete_token' => $token])->one();
        if (!$topic) {
            Yii::$app->session->setFlash('error', 'Топик не найден.');
            return $this->redirect(['site/index']);
        }

        if (!$topic->canDelete()) {
            Yii::$app->session->setFlash('error', 'Срок удаления истёк или пост уже удалён.');
            return $this->redirect(['site/index']);
        }

        if ($topic->softDelete()) {
            Yii::$app->session->setFlash('success', 'Пост помечен как удалённый.');
        } else {
            Yii::$app->session->setFlash('error', 'Не удалось пометить пост как удалённый.');
        }

        return $this->redirect(['site/index']);
    }

   /**
     * Send management email with private edit/delete links.
     *
     * Composes and sends an email to the post author containing private edit and delete URLs.
     * Must set a From header (either via messageConfig or setFrom here).
     *
     * @param string $toEmail Recipient email address.
     * @param string $toName Recipient display name.
     * @param \app\models\Topic $topic Topic model (must have tokens).
     * @return bool True when mailer->send() returns true.
     */
    protected function sendManagementMail(string $toEmail, string $toName, Topic $topic)
    {
        $editUrl = $topic->getEditUrl(true);
        $deleteUrl = $topic->getDeleteUrl(true);

        $subject = 'Управление вашим опубликованным сообщением';
        $body = "Здравствуйте, {$toName}!\n\n"
            . "Спасибо — ваше сообщение было опубликовано.\n\n"
            . "С помощью приватных ссылок ниже вы можете управлять своим постом:\n\n"
            . "Редактировать (доступно в течение 12 часов):\n{$editUrl}\n\n"
            . "Удалить (подтверждение, доступно в течение 14 дней):\n{$deleteUrl}\n\n"
            . "Если вы не отправляли это сообщение, проигнорируйте это письмо.\n\n"
            . "С уважением,\nАдминистрация сайта";

        // mailer (в debug-режиме Yii покажет содержимое письма в runtime/log)
        try {
            $mailer = Yii::$app->mailer->compose()
                ->setTo($toEmail)
                ->setSubject($subject)
                ->setTextBody($body);

            if (!$mailer->send()) {
                Yii::warning("Mailer returned false when sending management mail to {$toEmail}", __METHOD__);
            }
        } catch (\Throwable $e) {
            Yii::error("Failed to send management mail: " . $e->getMessage(), __METHOD__);
        }
    }
}
