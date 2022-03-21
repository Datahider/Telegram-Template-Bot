<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of SetValue
 *
 * @author drweb
 */
class SetValue extends AbstractMenuMember {
    protected $value;
    protected $session_param;
    protected $return_value;

    public function __construct(string $representation, string $session_param, $value, $return_value= AbstractMenuMember::HANDLE_RESULT_FINISHED) {
        $this->representation = $representation;
        $this->session_param = $session_param;
        $this->value = $value;
        $this->return_value = $return_value;
    }
    
    public function value() {
        return serialize($this->value);
    }
    
    public function set() {
        $this->api->session()->set($this->session_param, $this->value);
        return $this->return_value;
    }
    
}
