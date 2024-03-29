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
use Telegram\Bot\Message;

class TTBot extends Api {
    
    // Telegram API exceptions
    const TGEX_SAME_MESSAGE = 'Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message';
    const TGEX_BLOCKED = 'Forbidden: bot was blocked by the user';
    
    const KNOWN_EXCEPTIONS = [
        self::TGEX_BLOCKED, self::TGEX_SAME_MESSAGE
    ];
    
    protected $session;
    protected $update;
    protected $command_processed = false;
    
    public $object; 
    
    public function isBotAdmin() {
        global $config;
        $admins = is_array($config->admin) ? $config->admin : [$config->admin];
        if (array_search($this->session->get('user_id'), $admins) === false) {
            return false;
        } else {
            return true;
        }
    }

    public function __call($name, $arguments) {
        $mod_name = strtolower(preg_replace("/([A-Z])/", "_$1", $name));
        $matches = [];
        
        if (preg_match("/^(my_)(.+)$/", $mod_name, $matches)) {
            return $this->_get($matches[2], $arguments);
        } elseif (preg_match("/^(set_)(.+)/", $mod_name, $matches)) {
            return $this->_set($matches[2], $arguments);
        } else {
            parent::__call($name, $arguments);
        }
    }
    
    protected function _get($name, $arguments) {
        $arg_count = count($arguments);

        if ($arg_count == 0) {
            return $this->session->get($name);
        } elseif (count($arguments) == 1) {
            return $this->session->get($name, $arguments[0]);
        } else {
            throw new Exception("Too many arguments.");
        }
    }
    
    protected function _set($name, $arguments) {
        $arg_count = count($arguments);
        
        if ($arg_count == 1) {
            $this->session->set($name, $arguments[0]);
        } else {
            throw new Exception("Can accept only one argument.");
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

        if ($this->commandProcessed()) {
            return;
        }
        
        if ($menu_class = $this->session->get(AbstractMenuMember::TOP_MENU_CLASS, false)) {
            $menu = new $menu_class();
            $menu->bindApi($this);
            
            try {
                $menu->handle($update);
                $this->session->set(AbstractMenuMember::TOP_MENU_CLASS, false);
            } catch (Exception $e) {
                if ($e->getMessage() == AbstractMenuMember::HANDLE_RESULT_FINISHED) {
                    $this->session->set(AbstractMenuMember::TOP_MENU_CLASS, false);
                }
                return;
            }
        }
        
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
            $this->initSessionByEditedMessage($update);
            $this->initSessionByChannelPost($update);
            $this->initSessionByEditedChannelPost($update);
            $this->initSessionByInlineQuery($update);
            // TODO - 
            // $this->initSessionByChosenInlineResult($update)
            // $this->initSessionByShippingQuery($update)
            // $this->initSessionByPreCheckoutQuery($update)
            // $this->initSessionByPool($update)
            // $this->initSessionByPoolAnswer($update)
            $this->initSessionByMyChatMember($update);
            $this->initSessionByChatMember($update);
            // $this->initSessionByChatJoinRequest($update)
            throw new Exception('Session not initialized');
        } catch (Exception $ex) {
            if ($ex->getMessage() != 'Session initialized') {
                throw $ex;
            }
        }
    }
    
    protected function initSessionByInlineQuery($update) {
        $inline_query = $update->getInlineQuery();
        
        if (!$inline_query) {
            return;
        }
        
        $this->initSessionByInlineQueryUpdateObject($inline_query);
        
    }
    
    protected function initSessionByMyChatMember($update) {
        $chat_member_update = $update->get('my_chat_member');
        if (!$chat_member_update) {
            return;
        }
        
        $this->initSessionByChatmemberUpdateObject($chat_member_update);
    }
    
    protected function initSessionByChatMember($update) {
        $chat_member_update = $update->get('chat_member');
        if (!$chat_member_update) {
            return;
        }
        
        $this->initSessionByChatmemberUpdateObject($chat_member_update);
    }
    
    
    protected function initSessionByChatmemberUpdateObject($chat_member_update) {
        $from = $chat_member_update->getFrom();
        $chat = $chat_member_update->getChat();
        $this->session = $this->makeSession($from, $chat);   
        
        //TODO - Сделать добавление истории по этому событию
        throw new Exception('Session initialized');
    }
    
    protected function initSessionByInlineQueryUpdateObject($inline_query) {
        $from = $inline_query->getFrom();
        $this->session = $this->makeSession($from, false);   
        
        //TODO - Сделать добавление истории по этому событию
        throw new Exception('Session initialized');
    }
    
    protected function initSessionByMessageObject($message) {
        $from = $message->getFrom();
        $chat = $message->getChat();
        $this->session = $this->makeSession($from, $chat);   
        $text = $message->getText();
        
        if ($text) {
            $this->session->set('current_history_data', $text, false);
            $this->session->set('current_history_is_text', 1, false);
        } else {
            $this->session->set('current_history_data', '--Non-text-message--', false);
            $this->session->set('current_history_is_text', 0, false);
        }
        throw new Exception('Session initialized');
    }

    protected function initSessionByMessage($update) {
        $message = $update->get('message');
        if (!$message) { return; }

        $this->initSessionByMessageObject($message);
    }
    
    protected function initSessionByEditedMessage($update) {
        $message = $update->get('edited_message');
        if (!$message) { return; }

        $this->initSessionByMessageObject($message);
    }
    
    protected function initSessionByChannelPost($update) {
        $message = $update->get('channel_post');
        if (!$message) { return; }

        $this->initSessionByMessageObject($message);
    }
    
    protected function initSessionByEditedChannelPost($update) {
        $message = $update->get('edited_channel_post');
        if (!$message) { return; }

        $this->initSessionByMessageObject($message);
    }
    
    protected function initSessionByCallbackQuery($update) {
        $callback_query = $update->get('callback_query');
        if (!$callback_query) { return; }

        $from = $callback_query->getFrom();
        $chat = $callback_query->getMessage()->getChat();
        $this->session = $this->makeSession($from, $chat);   
        $text = 'Callback data: ' . $callback_query->get('data');
        
        $this->session->set('current_history_data', $text, false);
        $this->session->set('current_history_is_text', 0, false);

        throw new Exception('Session initialized');
    }

    

    protected function writeUpdateHistory() {
            $this->sqlInsertHistory(
                    $this->session->get('chat_id'), 
                    $this->session->get('user_id'), 
                    $this->session->get('current_history_is_text', 0), 
                    $this->session->get('current_history_data', '--none is set--')
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
                $replacement = ob_get_clean();
                if (!$replacement) {
                    $replacement = $match[1];
                }
                $text = str_replace($match[0], $replacement, $text, $count);
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
    
    public function editMessageReplyMarkup($params) {
        try {
            $this->post('editMessageReplyMarkup', $params);
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
    }
    
    public function editMessageText($params) {
        try {
            $this->post('editMessageText', $params);
        } catch (Exception $e) {
            if ($e->getMessage() == self::TGEX_SAME_MESSAGE) {
                error_log($e->getMessage());
            } else {
                throw $e;
            }
        }
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
        $this->answer($text, 'Plain', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    

    public function answer($text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params) {
        $params = $this->prepareMessageParams(null, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params);
        try {
            $this->sendMessage($params);
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
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
        $this->edit($message_id, $text, 'Plain', $keyboard, $custom_keyboard, $keyboard_params);
    }   
    
    public function edit($message_id, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params) {
        $params = $this->prepareMessageParams($message_id, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params);
        try {
            $this->editMessageText($params);
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
    }
    
    public function toAdmin($text, $parse_mode='Plain', $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        global $config;
        $chat_id = $config->admin;
        
        if (!is_string($text)) {
            $text = mb_substr(print_r($text, true), 0, 2048);
        }
        $this->toChat($chat_id, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params);
    }
    
    public function toChat($chat_id, $text, $parse_mode='Plain', $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $params = $this->prepareMessageParams(null, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params);
        $params['chat_id'] = $chat_id;
        try {
            $this->sendMessage($params);
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
    }
    
    protected function prepareMessageParams($message_id, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params) {
        $this->object = $this->session->get('object', false);
        
        $params = [
            'chat_id' => $this->session->get('chat_id'),
            'text' => $this->replaceVars($text),
        ];
        
        if ( $parse_mode == 'Plain' ) {
            $params['parse_mode'] = 'MarkdownV2';
            $params['text'] = preg_replace("/([\\\_\*\[\]\(\)\~\`\>\#\+\-\=\|\{\}\.\!])/", "\\\\$1", $params['text']);
        } elseif ( $parse_mode ) {
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
    
    protected function makeSession($user, $chat) {
        return new TTSession($user, $chat);
    }
    
    protected function exceptionHandler($e) {
        $message = $e->getMessage();
        if (array_search($message, self::KNOWN_EXCEPTIONS) === false) {
            throw $e;
        }
        
        error_log($message);
        
        switch ($message) {
            case self::TGEX_BLOCKED:
                // Just an example
                break;
            default:
                break;
        }
    }
    
    public function copyMessage(array $params)
    {
        $response = $this->post('copyMessage', $params);

        return new Telegram\Bot\Objects\Message($response->getDecodedBody());
    }

    public function getChat($chat_id) {
        $response = $this->post('getChat', ['chat_id' => $chat_id]);

        return new Telegram\Bot\Objects\Chat($response->getDecodedBody());
    }
    
    public function closeGeneralForumTopic($chat_id) {
        $response = $this->post('closeGeneralForumTopic', ['chat_id' => $chat_id]);

        return new Telegram\Bot\Objects\Chat($response->getDecodedBody());
    }
    
    public function hideGeneralForumTopic($chat_id) {
        $response = $this->post('hideGeneralForumTopic', ['chat_id' => $chat_id]);

        return new Telegram\Bot\Objects\Chat($response->getDecodedBody());
    }
    
    public function createForumTopic(array $params) {
        $response = $this->post('createForumTopic', $params);

        return $response->getDecodedBody();
    }
    
    public function setChatAdministratorCustomTitle(array $params) {
        $response = $this->post('setChatAdministratorCustomTitle', $params);

        return $response->getDecodedBody();
    }
    
    public function toForumTopic($chat_id, $topic_id, $text, $parse_mode='Plain', $keyboard=null, $custom_keyboard=true, $keyboard_params=[]) {
        $params = $this->prepareMessageParams(null, $text, $parse_mode, $keyboard, $custom_keyboard, $keyboard_params);
        $params['chat_id'] = $chat_id;
        $params['message_thread_id'] = $topic_id;
        try {
            $this->sendMessage($params);
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
    }
    
    public function setChatDescription(array $params) {
        $response = $this->post('setChatDescription', $params);

        return $response->getDecodedBody();
    }
    
    public function setChatPhoto(array $params) {
        $response = $this->uploadFile('setChatPhoto', $params);

        return $response->getDecodedBody();
    }
    
    public function getChatAdministrators($chat_id) {
        $response = $this->post('getChatAdministrators', [ 'chat_id' => $chat_id ]);
        
        return $response->getDecodedBody()['result'];
    }
    
    public function deleteMessage($params) {
        return $this->post('deleteMessage', $params)->getDecodedBody();
    }
    
    public function setMyCommands($params) {
        return $this->post('setMyCommands', $params)->getDecodedBody();
    }
}
