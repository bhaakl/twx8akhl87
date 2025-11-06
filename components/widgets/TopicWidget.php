<?php
namespace app\components\widgets;

use Yii;
use yii\base\Widget;
use yii\data\DataProviderInterface;
use yii\helpers\Html;
use yii\widgets\ListView;

class TopicWidget extends Widget
{
    /**
     * @var DataProviderInterface|null 
     */
    public $dataProvider;

    /**
     * @var array|null 
     */
    public $topics;

    /**
     * @var string путь к файлу item view (относительно @app/views). По умолчанию widgets/_topic.php
     */
    public $itemView = '@app/components/widgets/views/_topic.php';

    /**
     * @var array html-атрибуты для wrapper <main>
     */
    public $options = ['class' => 'col-12 col-lg-8 order-1 order-lg-0'];

    /**
     * @var array опции для ListView (будут слиты с дефолтными)
     */
    public $listViewOptions = [];

    public function run()
    {
        echo Html::beginTag('main', $this->options) . PHP_EOL;

        if ($this->dataProvider instanceof DataProviderInterface) {
            $default = [
                'dataProvider' => $this->dataProvider,
                'itemView' => $this->itemView,
                'summary' => false,
                'emptyText' => '<div class="alert alert-secondary">Новостей нет</div>',
                'options' => ['tag' => 'div', 'class' => 'list-view'],
                'itemOptions' => ['tag' => false], // item view сам содержит нужную обёртку
                'pager' => [
                    'options' => ['class' => 'mt-3'],
                ],
            ];

            $config = array_replace_recursive($default, $this->listViewOptions);
            echo ListView::widget($config);
        } elseif (is_array($this->topics)) {
            if (empty($this->topics)) {
                echo '<div class="alert alert-secondary">Новостей нет</div>';
            } else {
                foreach ($this->topics as $topic) {
                    // рендерим item view, передаём модель как $model
                    echo $this->getView()->renderFile(Yii::getAlias($this->itemView), ['model' => $topic]);
                }
            }
        } else {
            echo '<div class="alert alert-secondary">Данные для виджета не переданы</div>';
        }

        echo Html::endTag('main') . PHP_EOL;
    }
}
