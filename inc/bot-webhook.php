<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

use Telegram\Bot\Api; 
require './inc/db.php';

if (empty($config->bot_class)) {
    $bot = new TTBot($config->token);
} else {
    $bot = new $config->bot_class($config->token);
}

$bot->processWebhook();

