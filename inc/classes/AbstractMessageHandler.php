<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of AbstractMessageHandler
 *
 * @author drweb
 */
abstract class AbstractMessageHandler extends AbstractMenuMember {
    
    public function value() {
        return null;
    }
    
    public function handle($message) {
        $this->api->session()->set(self::CALLBACK_MESSAGE_ID, false);
    } 

}
