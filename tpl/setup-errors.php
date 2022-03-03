<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

?>

<div>Установка бота завершилась не удачно</div>
<div id="errors-wrapper">
    <h3 id="errors-header">Список ошибок</h3>
    <ul id="errors-list">
        <?php
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
        ?>
    </ul>
</div>