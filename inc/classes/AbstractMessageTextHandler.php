<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of AbstractMessageTextHandler
 *
 * @author drweb
 */
class AbstractMessageTextHandler extends AbstractMessageHandler {
    protected $text;
    
    
    public function handle($message) {
        $this->text = $message->get('text');
        
    }

}
