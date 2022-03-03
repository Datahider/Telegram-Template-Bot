<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require 'vendor/autoload.php';
use Telegram\Bot\Api; 

if (file_exists('./etc/bot-config.php')) {
    require './etc/bot-config.php';
    require './inc/bot-webhook.php';
} else {
    require './inc/bot-setup.php';
}