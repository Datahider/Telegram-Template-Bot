<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */
use Telegram\Bot\Api; 
global $config;

if (file_exists('./etc/bot-config.php')) {
    require './tpl/already-setup.php';
    exit();
}

require_once './inc/db-struct.php';

function configAsString() {
    global $config;
    $config_text = "<?php\n\n\$config = (object)[";
    
    foreach ($config as $key => $value) {
        $escaped_value = addslashes($value);
        $config_text .= "\n    '$key' => '$escaped_value',";
    }
    
    $config_text .= "\n];";
    
    return $config_text;
}

function checkConfig() {
    global $config;
    $errors = [];
    $errors[] = checkToken();
    $errors[] = checkDB();
    
    $errors = array_diff($errors, [true]);
    
    return $errors;
}

function checkToken() {
    global $config;
    
    $telegram = new Api($config->token);
    try {
        $response = $telegram->getMe();
    } catch (Exception $e) {
        if ($e->getMessage() == "Unauthorized") {
            return 'Указан не верный токен';
        } else {
            throw $e;
        }
    }
    return true;
}

function countDBTables() {
    global $pdo, $config;
    
    $sth = $pdo->prepare('SHOW TABLES LIKE :prefix');
    $sth->execute(['prefix' => $config->db_prefix . '%']);
    $data = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

    return count($data);
}
function checkDB() {
    global $config;
    try {
        require './inc/db.php';
    } catch (Exception $ex) {
        return 'Не удалось подключиться к базе данных: ' . $ex->getMessage();
    }
    
    try {
        checkPrefix();
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
    
    if (countDBTables() > 0) {
        try {
            checkDBTables();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    return true;
}

function initDatabase() {
    global $pdo, $config;
    
    if (countDBTables() == 0) {
        require './tpl/sql-createtables.php';
        
        $query_result = $pdo->query($sql_query);
        
        if ($query_result === false) {
            throw new Exception('Ошибка создания таблиц базы данных. Повторите попытку.');
        }
    }
}

function saveConfig() {
    $config_data = configAsString();
    
    $writen = file_put_contents('./etc/bot-config.php', $config_data);
    if ($writen === false) {
        return $config_data;
    }
    
    return true;
}

function getBotLink() {
    global $config;
    
    $telegram = new Api($config->token);
    $response = $telegram->getMe();
    
    $bot_user = $response->getUsername();

    return "https://t.me/$bot_user";
}

function setWebhook() {
    global $config;
    
    $telegram = new Api($config->token);
    $telegram->setWebhook(['url' => $config->webhook]);
}

$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
if ( $request_method == 'GET') {
    require './tpl/setup-params.php';
    exit();
} elseif ( $request_method == 'POST' ) {
    $config = (object)[
        'token' => filter_input(INPUT_POST, 'input-bot-token'),
        'webhook' => filter_input(INPUT_POST, 'input-bot-webhook'),
        'admin' => filter_input(INPUT_POST, 'input-bot-admin'),

        'db_host' => filter_input(INPUT_POST, 'input-db-host'),
        'db_name' => filter_input(INPUT_POST, 'input-db-name'),
        'db_user' => filter_input(INPUT_POST, 'input-db-user'),
        'db_pass' => filter_input(INPUT_POST, 'input-db-pass'),
        'db_prefix' => filter_input(INPUT_POST, 'input-db-prefix')
    ];
    
    try {
        $errors = checkConfig();
        if (count($errors) == 0) {
            initDatabase();
            $config_data = saveConfig();
            $bot_link = getBotLink();
            setWebhook();
            require './tpl/setup-done.php';
        } else {
            require './tpl/setup-errors.php';
        }
    } catch (Exception $e) {
        $errors[] = "При установке бота произошла непредвиденная ошибка:\n\"" 
                . $e->getMessage() 
                . "\"\nПовторите попытку или обратитесь к разработчику.";
        require './tpl/setup-errors.php';
    }
} else {
    die('Request method not supported');
}

