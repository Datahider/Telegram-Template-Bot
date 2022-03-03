<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require_once './tpl/header.php';
?>

Бот установлен. Проверьте его работу перейдя по ссылке: <a href="<?=$bot_link;?>"><?=$bot_link;?></a>


<?php

if ($config_data !== true) {
    echo 'Не удалось записать файл настроек бота. Сделайте это вручную<div><pre>';
    echo $config_data;
    echo '</pre></div>';
}

require_once './tpl/footer.php';
