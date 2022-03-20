<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

/**
 *
 * @author drweb
 */
abstract class AbstractMenuMember {
    const TOP_MENU_CLASS = 'AbstractMenuMember::top_menu_class';
    const CURRENT_MENU_NAME = 'AbstractMenuMember::current_menu_name';
    
    const CALLBACK_MESSAGE_ID = 'AbstractMenuMember::callback_message_id';
    const LAST_CALLBACK_ID = 'AbstractMenuMember::last_callback_id';
    
    const HANDLE_RESULT_NOT_MINE = -1;
    const HANDLE_RESULT_PROGRESS = 0;
    const HANDLE_RESULT_FINISHED = 1;
    const HANDLE_RESULT_FINISHED_1 = 1;
    const HANDLE_RESULT_FINISHED_2 = 2;
    const HANDLE_RESULT_FINISHED_3 = 3;
    
    protected $api;
    protected $options;
    protected $representation;


    public function bindApi(TTBot $api) {
        $this->api = $api;
        
        if ($this->options) {
            foreach ($this->options as $option) {
                if (is_a($option, 'AbstractMenuMember')) {
                    $option->bindApi($api);
                }
            }
        }
    }

    public function representation() {
        return $this->representation;
    }
    
    abstract public function value(); 
    
    protected function checkApi() {
        if (!$this->api) {
            throw new Exception("Telegram API not binded to the class");
        }
    }
    
    public function hideMessageButtons() {
        $message_id = $this->api->session()->get(self::CALLBACK_MESSAGE_ID, 0);
        if ($message_id) {
            $this->api->editMessageReplyMarkup([
                'chat_id' => $this->api->session()->get('chat_id'),
                'message_id' => $message_id,
                'reply_markup' => $this->api->replyKeyboardMarkup(['inline_keyboard' => [[]]])
            ]);
            $this->api->session()->set(self::CALLBACK_MESSAGE_ID, 0);
        }
    }
}
