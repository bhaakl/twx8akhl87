<?php
namespace app\widgets;

use yii\base\Widget;

class SideBarWidget extends Widget
{
    /**
     * @var \app\models\form\AuthorForm 
     */
    public $model;

    // public $itemView = '@app/components/widgets/views/_topic.php';

    /**
     * @var array html-атрибуты для wrapper <aside>
     */
    public $options = ['class' => 'sidebar'];


    public function run()
    {
        return $this->render('_sidebar', [
            'model' => $this->model,
            'options' => $this->options
        ]);
    }
}
