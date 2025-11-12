<?php
/**
 * @var $this \yii\web\View
 * @var $model array|\yii\db\ActiveRecord
 */

use yii\helpers\Html;
use yii\helpers\Url;

$get = function($key, $default = null) use ($model) {
    if (is_array($model)) {
        return $model[$key] ?? $default;
    }
    if (is_object($model)) {
        // ActiveRecord / DTO: сначала пробуем свойство, затем метод-геттер
        if (isset($model->{$key})) {
            return $model->{$key};
        }
        $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));
        if (method_exists($model, $getter)) {
            try {
                return $model->{$key};
            } catch (\Throwable $e) {
            }
        }
        return $default;
    }
    return $default;
};

$title = $get('title', 'Без названия');
$url   = $get('url', '#');
$datetime = $get('datetime', $get('created_at', null));

$excerpt = $get('excerpt', $get('summary', $get('content', '')));
if ($excerpt != null && $excerpt !== '') {
    $excerpt = str_contains($excerpt, "\n") ? explode("\n", $excerpt) : $excerpt;
}

$authorName = $get('authorName', $get('author', ''));
$authorUrl  = $get('authorUrl', $get('author_url', null));

if (is_object($model) && isset($model->author) && $model->author) {
    // author может быть AR или массив
    if (is_object($model->author)) {
        $authorName = $model->author->name ?? $authorName;
        $authorUrl  = $model->author->email ? 'mailto:' . ($model->author->email) : ($model->author->url ?? $authorUrl);
    } elseif (is_array($model->author)) {
        $authorName = $model->author['name'] ?? $authorName;
        $authorUrl  = isset($model->author['email']) ? 'mailto:' . $model->author['email'] : ($model->author['url'] ?? $authorUrl);
    }
}

if (empty($url)) {
    $url = '#';
}

$encodedTitle = Html::encode($title);
$encodedUrl = Url::to($url);

$relativeTime = '';
if ($datetime) {
    try {
        $relativeTime = Yii::$app->formatter->asRelativeTime($datetime) . ' | ' . Yii::$app->formatter->asDate($datetime, 'php:d.m.Y');
    } catch (\Throwable $e) {
        $relativeTime = Html::encode((string)$datetime);
    }
}
?>
<article class="bg-white rounded shadow-sm p-4 mb-4">
  <header class="mb-3">
    <h2 class="h5 mb-2">
      <?= Html::a($encodedTitle, $encodedUrl, ['class' => 'text-dark text-decoration-none']) ?>
    </h2>

    <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
      <?php if (!empty($authorName)): ?>
        <span>
          Автор:
          <?= Html::a(Html::encode($authorName), $authorUrl ?: '#', [
              'rel' => 'author',
              'class' => 'text-muted',
              'target' => $authorUrl && strpos($authorUrl, 'mailto:') !== 0 ? '_blank' : null,
              'data-pjax' => 0,
          ]) ?>
        </span>
      <?php endif; ?>

      <?php if ($datetime): ?>
        <time datetime="<?= Html::encode($datetime) ?>"><?= Html::encode($relativeTime) ?></time>
      <?php endif; ?>
    </div>
  </header>

  <div class="topic-content-text">
    <?php if (is_array($excerpt)): ?>
        <?php foreach ($excerpt as $ex): ?>
            <?php if ($ex === null || $ex === '') continue; ?>
            <p class="mb-2" style="line-height:1.6;"><?= Html::encode((string)$ex) ?></p>
        <?php endforeach; ?>
    <?php else: ?>
        <?php if ($excerpt !== null && $excerpt !== ''): ?>
            <p class="mb-0" style="line-height:1.6;"><?= Html::encode((string)$excerpt) ?></p>
        <?php endif; ?>
    <?php endif; ?>
  </div>
</article>
