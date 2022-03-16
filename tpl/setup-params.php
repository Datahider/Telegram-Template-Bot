<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require_once './tpl/header.php';
?>
<link rel="stylesheet" type="text/css" media="all" href="setup.css" />

<script language="Javascript">
    $().ready(function() {
        $('#input-bot-webhook').val($(location).attr('href'));
    });
</script>

<form method="POST" id="setup-form">
    <div id="setup-bot">
        <div id="setup-bot-token">
            <label id="label-bot-token">Токен бота</label>
            <input type="text" name="input-bot-token" id="input-bot-token" placeholder="Токен полученный от @BotFather"/>
        </div>
        <div id="setup-bot-webhook">
            <label id="label-bot-webhook">Адрес вебхука</label>
            <input type="text" name="input-bot-webhook" id="input-bot-webhook" readonly="true"/>
        </div>
        <div id="setup-bot-admin">
            <label id="label-bot-admin">Телеграм id администратора</label>
            <input type="text" name="input-bot-admin" id="input-bot-admin" placeholder="111222333"/>
        </div>
        <div id="setup-bot-class">
            <label id="label-bot-class">Имя класса</label>
            <input type="text" name="input-bot-class" id="input-bot-class" placeholder="MyBotClass"/>
        </div>
    </div>
    <div id="setup-db">
        <div id="setup-db-host">
            <label id="label-db-host">Хост БД</label>
            <input type="text" name="input-db-host" id="input-db-host" placeholder="localhost"/>
        </div>
        <div id="setup-db-name">
            <label id="label-db-name">Имя БД</label>
            <input type="text" name="input-db-name" id="input-db-name" placeholder="database"/>
        </div>
        <div id="setup-db-user">
            <label id="label-db-user">Пользователь БД</label>
            <input type="text" name="input-db-user" id="input-db-user" placeholder="user"/>
        </div>
        <div id="setup-db-pass">
            <label id="label-db-pass">Пароль к БД</label>
            <input type="text" name="input-db-pass" id="input-db-pass" placeholder="password"/>
        </div>
        <div id="setup-db-prefix">
            <label id="label-db-prefix">Префикс таблиц БД</label>
            <input type="text" name="input-db-prefix" id="input-db-prefix" value="ttb_" placeholder="ttb_"/>
        </div>
    </div>
    <div id="setup-submit">
        <input type="submit" value="Установить бот"/>
    </div>
</form>

<?php
require_once './tpl/footer.php';
