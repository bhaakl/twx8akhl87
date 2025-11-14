<?php

/* @var $this yii\web\View */
/* @var $topic app\models\Topic */

use yii\helpers\Html;

$this->title = 'Редактировать пост';
?>
<div class="container py-4">
  <h1 class="h5 mb-3">Редактировать пост</h1>

  <div class="card p-3">
    <h2 class="h6"><?= Html::encode($topic->title) ?></h2>

    <form method="post">
      <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
      <div class="mb-3">
        <label class="form-label">Сообщение</label>
        <textarea name="excerpt" rows="8" class="form-control"><?= Html::encode(is_array($topic->getExcerpt()) ? implode("\n\n", $topic->getExcerpt()) : $topic->getExcerpt()) ?></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="<?= \yii\helpers\Url::to(['site/index']) ?>" class="btn btn-secondary">Отмена</a>
      </div>
    </form>
  </div>
</div>
