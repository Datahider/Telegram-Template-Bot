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
    protected $finish_processing;

    public function __construct(string $representation, string $session_param, $value, $finish_processing=true) {
        $this->representation = $representation;
        $this->session_param = $session_param;
        $this->value = $value;
        $this->finish_processing = $finish_processing;
    }
    
    public function value() {
        return serialize($this->value);
    }
    
    public function set() {
        $this->api->session()->set($this->session_param, $this->value);
        if ($this->finish_processing) {
            throw new TTException(AbstractMenuMember::HANDLE_RESULT_FINISHED);
        } else {
            throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
        }
    }
    
}
