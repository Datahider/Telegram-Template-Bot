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
    
    public function processUpdate($update) {
        
        $message = $update->getMessage();
        
        if ($message) {
            if (substr($message->getText(), 0, 1) == '/') {
                return; // must be already processed
            }
            $this->processMessageUpdate($update);
        } else {
            $this->processNonMessageUpdate($update);
        }
    }
    
    protected function processMessageUpdate($update) {
        $this->sendMessage([
            'chat_id' => $update->getMessage()->getChat()->getId(),
            'text' => 'Redefine processMessageUpdate($update) to process message updates'
        ]);
    }

    protected function processNonMessageUpdate($update) {
        error_log('Redefine processNonMessageUpdate($update) to process non-message updates');
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
            'datetime' => date(DateTime::ATOM),
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'is_text' => $is_text,
            'history_data' => $history_data
        ]);        
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
        preg_match_all("/\{\{([^}]+)\}\}/", $text, $matches, PREG_SET_ORDER);
        $count = 1;
        foreach ( $matches as $match ) {
            $text = str_replace($match[0], $this->session->get($match[1], '--UNSET--'), $text, $count);
        }
        return $text;
    }
    
    public function answerCallbackQuery($params) {
        $this->post('answerCallbackQuery', $params);
    }
}
