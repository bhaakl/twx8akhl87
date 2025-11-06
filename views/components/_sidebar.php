<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\captcha\Captcha;

/**
 * @var $this yii\web\View
 * @var $model app\models\ContactForm
 */
?>
<aside class="col-12 col-lg-4 sidebar">
  <div class="other-topics">
    <h2 class="h5 mb-3">Оставить сообщение</h2>

    <div class="mb-4 shadow-sm px-3">
      <?php $form = ActiveForm::begin([
          'id' => 'send-form',
          'options' => ['class' => 'needs-validation'],
          'fieldConfig' => [
                'options' => ['class' => 'mb-3'], 
                'inputOptions' => ['class' => 'form-control'],
                'errorOptions' => ['class' => 'invalid-feedback d-block'], 
            ],
      ]); ?>

      <div class="mb-3">
        <?= $form->field($model, 'name')->textInput(['autofocus' => true, 'class' => 'form-control']) ?>
      </div>

      <div class="mb-3">
        <?= $form->field($model, 'email')->input('email', ['class'=>'form-control']) ?>
      </div>

      <div class="mb-3">
        <?= $form->field($model, 'msg')->textarea(['rows' => 3, 'class'=>'form-control']) ?>
      </div>

      <div class="mb-3">
        <?= $form->field($model, 'verifyCode')->widget(Captcha::class, [
            'template' => '<div class="row g-2 align-items-center"><div class="col-auto">{image}</div><div class="col">{input}</div></div>',
            'options' => ['class' => 'form-control'],
        ])->label('Капча') ?>
      </div>

      <div class="mb-0">
        <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary w-100', 'name' => 'send-button']) ?>
      </div>

      <?php ActiveForm::end(); ?>
    </div>

  </div>
</aside>
