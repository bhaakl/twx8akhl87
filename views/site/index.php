<?php

use app\components\widgets\TopicWidget;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;

/** @var yii\web\View $this */

$this->title = 'StoryValut';
?>

<section class="topic-page py-4 py-lg-5">
  <div class="container">
    <div class="row gx-4 gy-4">
      <!-- MAIN -->
        <?= TopicWidget::widget(['topics' => $topics, 'options' => ['class' => 'col-12 col-lg-8']]) ?>

      <!-- SIDEBAR -->
        <?= $this->render('//components/_sidebar', [
          'model' => $model,
        ]) ?>
      <!-- <aside class="col-12 col-lg-4">
        <div class="other-topics">
          <h2 class="h5 mb-3">Оставить сообщение</h2>

            <div class="shadow-sm px-3 pb-3 card-like">
                <?php $form = ActiveForm::begin([
                    'id' => 'send-form',
                    'fieldConfig' => [
                        'template' => "{label}\n{input}\n{error}",
                        'labelOptions' => ['class' => 'col-lg-1 col-form-label mr-lg-3 w-100'],
                        'inputOptions' => ['class' => 'col-lg-3 form-control'],
                        'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
                    ],
                ]); ?>

                <?= $form->field($model, 'name')->textInput(['autofocus' => true]) ?>

                <?= $form->field($model, 'email') ?>

                <?= $form->field($model, 'msg')->textarea(['rows' => 3]) ?>

                <?= $form->field($model, 'verifyCode')->widget(Captcha::class, [
                    'template' => '<div class="row"><div class="col-lg-5">{image}</div><div class="col-lg-7">{input}</div></div>',
                ]) ?>

                <div class="form-group">
                    <div>
                        <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary', 'name' => 'send-button']) ?>
                    </div>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
      </aside> -->
    </div>
  </div>
</section>