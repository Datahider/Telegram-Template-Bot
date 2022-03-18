<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of TTBot
 *
 * @author drweb
 */

use Telegram\Bot\Api; 

class TTBot extends Api {
    protected $session;
    protected $update;
    protected $command_processed = false;
    
    public function isBotAdmin() {
        global $config;
        $admins = is_array($config->admin) ? $config->admin : [$config->admin];
        if (array_search($this->session->get('user_id'), $admins) === false) {
            return false;
        } else {
            return true;
        }
    }
    
    public function commandProcessed($true=false) {
        if ($true) {
            $this->command_processed = true;
        } else {
            return $this->command_processed;
        }
    }

    public function processUpdate($update) {
        
        $message = $update->getMessage();
        
        if ($message) {
            if ( substr($message->getText(), 0, 1) == '/' ) {
                if ( !$this->commandProcessed() ) {
                    $this->processNotProcessedCommand($update);
                }
            } else {
                $this->processMessageUpdate($update);
            }
        } else {
            $this->processNonMessageUpdate($update);
        }
    }
    
    protected function processNotProcessedCommand($update) {
        $this->answerPlainText('Override processNotProcessedCommand($update) to process commands not processed by Command children classes.');
    }
    
    protected function processMessageUpdate($update) {
        $this->answerPlainText('Override processMessageUpdate($update) to process message updates.');
    }

    protected function processNonMessageUpdate($update) {
        $this->answerPlainText('Override processNonMessageUpdate($update) to process non-message updates');
    }

    public function processWebhook() {
        $update = $this->commandsHandler(true);
        $this->processUpdate($update);
    }

    public function processUpdates() {
        $data = $this->commandsHandler(false);
        
        foreach ($data as $update) {
            $this->processUpdate($update);
        }
    }

    public function session() {
        return $this->session;
    }

    protected function sendRequest($method, $endpoint, array $params = []): \Telegram\Bot\TelegramResponse {
        
        switch ($endpoint) {
            case 'sendMessage':
            case 'editMessageText':
                $this->writeMessageHistory($params);
                break;
        }
        
        return parent::sendRequest($method, $endpoint, $params);
    }
    
    public function writeMessageHistory($params) {
        $this->sqlInsertHistory(
            $params['form_params']['chat_id'], 
            0, 
            1, 
            $params['form_params']['text']
        );
    }

    public function getWebhookUpdates(): \Telegram\Bot\Objects\Update {
        $update = parent::getWebhookUpdates();
        
        $this->initSession($update);
        $this->writeUpdateHistory();
        return $update;
    }

    public function getUpdates(array $params = []) {
        $data = parent::getUpdates($params);
        
        foreach ($data as $update) {
            $this->initSession($update);
            $this->writeUpdateHistory();
        }
        return $data;
    }

    protected function sqlInsertHistory($chat_id, $user_id, $is_text, $history_data) {
        global $pdo, $config;
        
        $sth = $pdo->prepare(
            "INSERT INTO {$config->db_prefix}chathistory "
            . "( datetime, chat_id, user_id, is_text, history_data) "
            . "VALUES( :datetime, :chat_id, :user_id, :is_text, :history_data )"
        );

        $sth->execute([
            'datetime' => date('Y-m-d H:i:s'), // MySQL date format
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'is_text' => $is_text,
            'history_data' => $history_data
        ]);        
    }
    
    public function getOption($name, $default=null) {
        global $config, $pdo;
        $sql = "SELECT param_value FROM {$config->db_prefix}settings WHERE param_name = :option_name";
        $sth = $pdo->prepare($sql);
        
        $sth->execute([
            'option_name' => $name
        ]);
        
        $value = $sth->fetchColumn(0);
        if ( $value === false ) {
            if (isset($config->$name)) {
                return $config->$name;
            }
            return $default;
        } else {
            $unserialized = unserialize($value);
            if ($unserialized === false && serialize($unserialized) != $value) {
                return $value;
            }
            return $unserialized;
        }
    }
    
    public function setOption($name, $value) {
        global $config, $pdo;
        $sth = $pdo->prepare(
                "INSERT INTO {$config->db_prefix}settings (param_name, param_value)"
                . "VALUES (:param_name, :param_value) "
                . "ON DUPLICATE KEY UPDATE param_value = :param_value"
        );
                
        $sth->execute([
            'param_name' => $name,
            'param_value' => serialize($value)
        ]);
        
    }
    
    public function unsetOption($name) {
        global $config, $pdo;
        $sth = $pdo->prepare(
            "DELETE FROM {$config->db_prefix}settings WHERE param_name = :param_name"
        );
        $sth->execute([ 'param_name' => $name ]);
    }

    protected function initSession($update) {
        try {
            $this->initSessionByMessage($update);
            $this->initSessionByCallbackQuery($update);
            // TODO - 
            // $this->initSessionByEditedMessage($update)
            // $this->initSessionByChannelPost($update)
            // $this->initSessionByEditedChannelPost($update)
            // $this->initSessionByInlineQuery($update)
            // $this->initSessionByChosenInlineResult($update)
            // $this->initSessionByShippingQuery($update)
            // $this->initSessionByPreCheckoutQuery($update)
            // $this->initSessionByPool($update)
            // $this->initSessionByPoolAnswer($update)
            // $this->initSessionByMyChatMember($update)
            // $this->initSessionByChatMember($update)
            // $this->initSessionByChatJoinRequest($update)
            throw new Exception('Session not initialized');
        } catch (Exception $ex) {
            if ($ex->getMessage() != 'Session initialized') {
                throw $ex;
            }
        }
    }

    protected function initSessionByMessage($update) {
        $message = $update->get('message');
        if (!$message) { return; }

        $from = $message->getFrom();
        $chat = $message->getChat();
        $this->session = new TTSession($from, $chat);   
        $text = $message->getText();
        
        if ($text) {
            $this->session->set('current_history_data', $text, false);
            $this->session->set('current_history_is_text', true, false);
        } else {
            $this->session->set('current_history_data', '--Non-text-message--', false);
            $this->session->set('current_history_is_text', false, false);
        }
        throw new Exception('Session initialized');
    }
    
    protected function initSessionByCallbackQuery($update) {
        $callback_query = $update->get('callback_query');
        if (!$callback_query) { return; }

        $from = $callback_query->getFrom();
        $chat = $callback_query->getMessage()->getChat();
        $this->session = new TTSession($from, $chat);   
        $text = 'Callback data: ' . $callback_query->get('data');
        
        $this->session->set('current_history_data', $text, false);
        $this->session->set('current_history_is_text', false, false);

        throw new Exception('Session initialized');
    }

    

    protected function writeUpdateHistory() {
            $this->sqlInsertHistory(
                    $this->session->get('chat_id'), 
                    $this->session->get('user_id'), 
                    $this->session->get('current_history_is_text'), 
                    $this->session->get('current_history_data')
            );
        
    }
    
    public function replaceVars($text) {
        if (!preg_match_all("/\{\{([^{}]+)\}\}/", $text, $matches, PREG_SET_ORDER)) {
            return $text;
        }
        
        $count = 1;
        foreach ( $matches as $match ) {
            if (preg_match("/^file\:(.+)$/", $match[1], $filematches)) {
                ob_start();
                include "tpl/$filematches[1]";
                $text = ob_get_clean();
                if (!$text) {
                    $text = $match[1];
                }
            } else {
                $text = str_replace($match[0], $this->session->get($match[1], "$match[1]"), $text, $count);
            }
        }
        return $this->replaceVars($text);
    }
    
    public function replaceVarsArray($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->replaceVarsArray($value);
            } else {
                $array[$key] = $this->replaceVars($value);
            }
        }
        return $array;
    }

    public function answerCallbackQuery($params) {
        $this->post('answerCallbackQuery', $params);
    }
    
    public function editMessageText($params) {
        $this->post('editMessageText', $params);
    }
    
    public function answerHTML($text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->answer($text, 'HTML', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    public function answerMarkdown($text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->answer($text, 'Markdown', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    public function answerMarkdownV2($text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->answer($text, 'MarkdownV2', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    public function answerPlainText($text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->answer($text, '', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    

    protected function answer($text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params) {
        $params = $this->prepareMessageParams(null, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params);
        $this->sendMessage($params);
    }
    
    public function editHTML($message_id, $text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->edit($message_id, $text, 'HTML', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    public function editMarkdown($message_id, $text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->edit($message_id, $text, 'Markdown', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    public function editMarkdownV2($message_id, $text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->edit($message_id, $text, 'MarkdownV2', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    public function editPlainText($message_id, $text, $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $this->edit($message_id, $text, '', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    protected function edit($message_id, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params) {
        $params = $this->prepareMessageParams($message_id, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params);
        $this->editMessageText($params);
    }
    
    protected function prepareMessageParams($message_id, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params) {
        $params = [
            'chat_id' => $this->session->get('chat_id'),
            'text' => $this->replaceVars($text),
        ];
        
        if ($parse_mode) {
            $params['parse_mode'] = $parse_mode;
        }
        
        $reply_markup = $this->prepareReplyMarkup($keyboard, $custom_keyboard, $keyboard_params);
        if ($reply_markup) {
            $params['reply_markup'] = $reply_markup;
        }

        if ($message_id !== null) {
            $params['message_id'] = $message_id;
        }
        
        return $params;
    }

    protected function prepareReplyMarkup($keyboard, $custom_keyboard, array $params=[]) {
        global $config;

        if ($keyboard === null) {
            $key = 'remove_keyboard';
            $reply_markup = true;
        } elseif ($custom_keyboard === true) {
            $key = 'keyboard';
            if (is_string($keyboard)) {
                $keyboard = $config->custom_keyboards[$keyboard];
            }
            $reply_markup = $this->replaceVarsArray($keyboard);
        } elseif ($custom_keyboard === false) {
            $key = 'inline_keyboard';
            if (is_string($keyboard)) {
                $keyboard = $config->inline_keyboards[$keyboard];
            }
            $reply_markup = $this->replaceVarsArray($keyboard);
        }

        $params[$key] = $reply_markup;
        $reply_markup = $this->replyKeyboardMarkup($params);
        return $reply_markup;
    }
    
}
