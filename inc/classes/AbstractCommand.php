<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of AbstractCommand
 *
 * @author drweb
 */
abstract class AbstractCommand extends Telegram\Bot\Commands\Command {
    protected $api;
    
    public function handle($arguments) {
        $this->api = $this->getTelegram();
        $this->api->session()->set(AbstractMenuMember::TOP_MENU_CLASS, false);
        $this->api->session()->set(UserMenu::CALLBACK_MESSAGE_ID, false);
    }
}
