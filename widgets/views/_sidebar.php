<?php

/** @var yii\web\View $this */
/** @var app\models\form\AuthorForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;

$formId = 'send-form';
$emailId = 'form-email';

?>

<?= Html::beginTag('aside', $options) ?>
  <div class="sidebar-block shadow-sm">
    <div class="px-3 pb-2">
        <h2 class="h5 mb-3">Оставить сообщение</h2>
        <?php $form = ActiveForm::begin([
            'id' => $formId,
            'fieldConfig' => [
                'options' => ['class' => 'mb-2'],
            ],
        ]); ?>

        <?= $form->field($model, 'name')->textInput() ?>

        <?= $form->field($model, 'email') ?>

        <?= $form->field($model, 'msg')->textarea(['rows' => 3]) ?>

        <?= $form->field($model, attribute: 'verifyCode')->widget(Captcha::class, [
            'template' => '<div class="row g-2 align-items-center"><div class="col-auto">{image}</div><div class="col">{input}</div></div>',
            'imageOptions' => ['alt' => 'Код с картинки', 'title' => 'Обновить код'],
        ]) ?>

      <div class="form-group">
        <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary w-100 mt-3', 'name' => 'send-button']) ?>
      </div>

      <?php ActiveForm::end(); ?>
    </div>

  </div>
<?= Html::endTag('aside') ?>