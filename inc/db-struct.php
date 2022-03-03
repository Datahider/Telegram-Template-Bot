<?php

/* 
 * Содержит функции проверки структуры таблиц
 * Функции выбрасывают исключения при ошибках
 */


function checkPrefix() {
    global $config;
    
    if (!preg_match('/^[a-zA-Z]+[0-9a-zA-Z_]*$/D', $config->db_prefix)) {
        throw new Exception('Префикс может содержать только латинские буквы, цифры и знак подчеркивания и должен начинаться с буквы');
    }
    
}

function checkDBTables() {
    checkTable('settings');
    checkTable('users');
    checkTable('sessiondata');
    checkTable('chathistory');
}

function checkTable($table_name) {
    global $pdo, $config;
    
    $table_metadata = getTableMetadata($table_name);
    
    $sth = $pdo->query("SHOW COLUMNS FROM $config->db_prefix$table_name");
    if ($sth === false) {
        throw new Exception("Таблица $config->db_prefix$table_name отсутствует в базе данных");
    }
    
    while ($column = $sth->fetch(PDO::FETCH_ASSOC)) {
        $columns[$column['Field']] = [
            'Field' => $column['Field'],
            'Type' => $column['Type'],
            'Null' => $column['Null'],
            'Key' => $column['Key'],
            'Default' => $column['Default'],
            'Extra' => $column['Extra']
        ];
    }
    
    //throw new Exception(print_r($table_metadata['columns'], true));
    foreach ($table_metadata['columns'] as $field => $settings) {
        foreach ($settings as $key => $value) {
            if (empty($columns[$field])) {
                throw new Exception( <<<END
                    Нарушение структуры таблицы $config->db_prefix$table_name. 
                    Колонка $field отсутствует;    
                    Для пересоздания таблиц удалите из базы все таблицы с префиксом $config->db_prefix");
                    END
                );    
            }
            if ($columns[$field][$key] != $value) {
                throw new Exception( <<<END
                    Нарушение структуры таблицы $config->db_prefix$table_name. 
                    Колонка $field: $key = {$columns[$field][$key]}; Должно быть $value;    
                    Для пересоздания таблиц удалите из базы все таблицы с префиксом $config->db_prefix");
                    END
                );    
            }
        }
    }
}

function createTable($table_name) {
    
// CREATE TABLE `ttb`.`ttb_chathistory` ( 
//  `id` BIGINT NOT NULL AUTO_INCREMENT , 
//  `datetime` DATETIME NOT NULL , 
//  `chat_id` BIGINT NOT NULL , 
//  `user_id` BIGINT NOT NULL , 
//  `history_data` TEXT NOT NULL , 
//  PRIMARY KEY (`id`)
// ) ENGINE = InnoDB;
//    
}
function getTableMetadata($table_name=null) {
    $table_metadata = [
        'settings' => [
            'columns' => [
                'param_name' => [
                    'Field' => 'param_name',
                    'Type' => 'varchar(64)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => '',
                    'Extra' => ''
                ],
                'param_value' => [
                    'Field' => 'param_value',
                    'Type' => 'varchar(1024)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
            ],
            'indexes' => [],
        ],
        'users' => [
            'columns' => [
                'id' => [
                    'Field' => 'id',
                    'Type' => 'bigint(20)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => '',
                    'Extra' => ''
                ],
                'first_name' => [
                    'Field' => 'first_name',
                    'Type' => 'varchar(256)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'last_name' => [
                    'Field' => 'last_name',
                    'Type' => 'varchar(256)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'username' => [
                    'Field' => 'username',
                    'Type' => 'varchar(256)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'phone_number' => [
                    'Field' => 'phone_number',
                    'Type' => 'varchar(64)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
            ],
            'indexes' => [
                
            ],
        ],
        'sessiondata' => [
            'columns' => [
                'user_id' => [
                    'Field' => 'user_id',
                    'Type' => 'bigint(20)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => '',
                    'Extra' => ''
                ],
                'chat_id' => [
                    'Field' => 'chat_id',
                    'Type' => 'bigint(20)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => '',
                    'Extra' => ''
                ],
                'param_name' => [
                    'Field' => 'param_name',
                    'Type' => 'varchar(64)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'param_value' => [
                    'Field' => 'param_value',
                    'Type' => 'varchar(1024)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
            ],
            'indexes' => [
                
            ],
        ],
        'chathistory' => [
            'columns' => [
                'id' => [
                    'Field' => 'id',
                    'Type' => 'bigint(20)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => '',
                    'Extra' => 'auto_increment'
                ],
                'datetime' => [
                    'Field' => 'datetime',
                    'Type' => 'datetime',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'chat_id' => [
                    'Field' => 'chat_id',
                    'Type' => 'bigint(20)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'user_id' => [
                    'Field' => 'user_id',
                    'Type' => 'bigint(20)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'is_text' => [
                    'Field' => 'is_text',
                    'Type' => 'tinyint(1)',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
                'history_data' => [
                    'Field' => 'history_data',
                    'Type' => 'text',
                    'Null' => 'NO',
                    'Key' => '',
                    'Default' => '',
                    'Extra' => ''
                ],
            ],
            'indexes' => [
                
            ],
        ],
    ];
    
    if ($table_name === null) {
        return $table_metadata;
    } else {
        return $table_metadata[$table_name];
    }
}

