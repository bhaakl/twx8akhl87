<?php

/* @var $this yii\web\View */
/* @var $topic app\models\Topic */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Подтвердите удаление поста';
?>
<div class="container py-4">
  <h1 class="h5 mb-3">Удалить пост?</h1>

  <div class="card p-3">
    <h2 class="h6"><?= Html::encode($topic->title) ?></h2>
    <p class="text-muted small">Опубликаван: <?= Yii::$app->formatter->asDatetime($topic->published_at, 'php:d.m.Y H:i:s') ?></p>

    <p>Вы уверены, что хотите пометить этот пост как удалённый? Действие необратимо (в UI), но запись останется в базе.</p>

    <form method="post" action="<?= Url::to(['site/delete', 'token' => $topic->delete_token]) ?>">
      <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
      <button type="submit" class="btn btn-danger">Да, удалить</button>
      <a href="<?= Url::to(['site/index']) ?>" class="btn btn-secondary">Отмена</a>
    </form>
  </div>
</div>
