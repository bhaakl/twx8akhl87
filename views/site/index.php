<?php

use app\widgets\TopicsWidget;
use app\widgets\SideBarWidget;

/** @var yii\web\View $this */
/** @var app\models\form\AuthorForm $model */

$this->title = 'StoryValut';
?>

<section class="topic-page py-4 py-lg-5">
  <div class="container">
    <div class="row gx-4 gy-4">
      <!-- MAIN -->
        <?= TopicsWidget::widget(['topics' => $topics, 'options' => ['class' => 'col-12 col-lg-8']]) ?>
      <!-- SIDEBAR -->
        <?= SideBarWidget::widget(['model' => $model, 'options' => ['class' => 'col-12 col-lg-4']]) ?>
    </div>
  </div>
</section>