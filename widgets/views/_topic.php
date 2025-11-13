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

/**
 * Mask IP:
 * - IPv4: скрывает последние 2 октета -> 46.211.**.**
 * - IPv6: скрывает последние 4 секции -> xxxx:xxxx:xxxx:xxxx:****:****:****:****
 */
$maskIp = function (?string $ip) {
    if (!$ip) {
        return null;
    }

    // IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return "{$parts[0]}.{$parts[1]}.**.**";
        }
        return $ip;
    }

    // IPv6
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $bin = @inet_pton($ip);
        if ($bin !== false && strlen($bin) === 16) {
            $words = unpack('n8', $bin); // 8 16-bit parts
            $segments = array_map(function($v){ return sprintf('%04x', $v); }, $words);
            // скрываем последние 4 сегмента
            for ($i = 4; $i < 8; $i++) {
                $segments[$i] = '****';
            }
            return implode(':', $segments);
        }
        // fallback — простая операция на ':'
        $parts = explode(':', $ip);
        $len = count($parts);
        if ($len >= 8) {
            for ($i = $len - 4; $i < $len; $i++) {
                $parts[$i] = '****';
            }
            return implode(':', $parts);
        }
        // если не удалось — вернуть оригинал
        return $ip;
    }

    return $ip;
};

/**
 * Попытка подсчитать общее число сообщений автора по IP.
 * Возвращает int|null — null если подсчитать нельзя (нет таблицы/колонки и т.п.)
 */
$getMessagesCountByIp = function (?string $ip) {
    if (!$ip) {
        return null;
    }

    try {
        $sql = 'SELECT COUNT(*) FROM {{%topics}} WHERE ip = :ip';
        $count = (int) Yii::$app->db->createCommand($sql, [':ip' => $ip])->queryScalar();
        return $count;
    } catch (\Throwable $e) {
        Yii::info('Не удалось посчитать сообщения по IP: ' . $e->getMessage(), __METHOD__);
        return null;
    }
};


/* --- Подготовка переменных --- */

$title = $get('title', 'Без названия');
$url   = $get('url', '#');
$datetime = $get('published_at', $get('created_at', null));

$excerpt = $get('excerpt', $get('summary', $get('content', '')));
if ($excerpt != null && $excerpt !== '') {
    $excerpt = str_contains($excerpt, "\n") ? explode("\n", $excerpt) : $excerpt;
}

$authorName = $get('authorName', $get('author', ''));
$authorUrl  = $get('authorUrl', $get('author_url', null));
$authorIp = $get('ip');

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

// $datetime — исходная дата публикации
$relativeTime = null;
if (!empty($datetime)) {
    try {
        Yii::$app->formatter->locale = 'ru-RU';
        $relativeTime = Yii::$app->formatter->asRelativeTime($datetime);
    } catch (\Throwable $e) {
        $relativeTime = Html::encode((string)$datetime);
    }
}

$ip = $authorIp ?? null;
$maskedIp = $maskIp($ip);
$messagesCount = $getMessagesCountByIp($ip);
?>

<article class="card mb-4 shadow-sm">
  <div class="card-body">
    <h2 class="card-title h5 mb-2">
      <?= $encodedTitle?>
    </h2>

    <div class="card-subtitle mb-3 text-muted small d-flex flex-wrap align-items-center gap-2">
      <?php if (!empty($authorName)): ?>
        <span class="me-2">
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
        <time datetime="<?= Html::encode($datetime) ?>" class="text-muted"><?= Html::encode($relativeTime) ?></time>
      <?php endif; ?>
    </div>

    <div class="card-text topic-content-text">
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

    <div class="card-footer d-flex justify-content-between align-items-center small text-muted border-0 mt-3">
    <div>
      <?php if ($messagesCount !== null): ?>
        <span>Всего топиков: <strong><?= Html::encode((string)$messagesCount) ?></strong></span>
      <?php else: ?>
        <span>Всего топиков: <em>н/д</em></span>
      <?php endif; ?>
    </div>

    <div class="text-end">
      <?php if ($maskedIp): ?>
        <span title="<?= Html::encode($ip) ?>">IP: <?= Html::encode($maskedIp) ?></span>
      <?php else: ?>
        <span>IP: <em>н/д</em></span>
      <?php endif; ?>
    </div>
  </div>
  </div>
</article>
