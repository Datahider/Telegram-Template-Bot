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
        $text = $update->getMessage()->getText();
        $this->sendMessage([
            'chat_id' => $this->session()->get('chat_id'),
            'text' => "$text\n\nRedefine processUpdate(\$update) to do something smarter"
        ]);
    }
    
    public function processWebhook() {
        $update = $this->getWebhookUpdates();
        $this->processUpdate($update);
    }

    public function processUpdates() {
        $data = $this->getUpdates();
        
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
        
        $this->writeUpdateHistory($update);
        return $update;
    }

    public function getUpdates(array $params = []) {
        $data = parent::getUpdates($params);
        
        foreach ($data as $update) {
            $this->writeUpdateHistory($update);
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

    protected function writeUpdateHistory($update) {
        
        $message = $update->getMessage();
        if ($message) {
            if ($message->getText()) {
                $history_data = $message->getText();
                $is_text = true;
            } else {
                $history_data = print_r($message, true);
                $is_text = false;
            }
        
            $from = $message->getFrom();
            if ($from) {
                $from_id = $from->getId();
            } else {
                $from_id = -1;
            }
            $chat = $message->getChat();
            $chat_id = $chat->getId();
            
            $this->session = new TTSession($from, $chat);

            $this->sqlInsertHistory($chat_id, $from_id, $is_text, $history_data);
        }
    }
}
