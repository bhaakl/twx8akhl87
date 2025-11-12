<?php

namespace app\helpers;

use yii\helpers\VarDumper;

class SvAppHelper
{
    public static function showErrors($model, $title = 'Ошибки валидации')
    {
        if (!$model->hasErrors()) {
            return;
        }

        echo "<div style='background: #fff3f3; padding: 15px; margin: 10px 0; border: 1px solid #ffcdd2; border-radius: 4px;'>";
        
        echo "<h4 style='color: #d32f2f; margin-top: 0;'>$title</h4>";
        echo '<pre>';
        echo VarDumper::dumpAsString($model->getErrors(), 10, true);
        echo '</pre>';

        echo "</div>";
    }
}
