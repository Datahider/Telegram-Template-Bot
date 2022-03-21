<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of SetFromString
 *
 * @author drweb
 */
class SetFromString extends AbstractMessageHandler {
    
    const TYPE_ANY = -1;
    const TYPE_BOOL = 0;
    const TYPE_INT = 1;
    const TYPE_FLOAT = 2;
    const TYPE_STRING = 3;
    
    const REGEX_TYPE_ANY = "/.*/";
    const REGEX_TYPE_BOOL = "/^(true|false)$/";
    const REGEX_TYPE_INT = "/^\s*((\+|\-)?\d+)\s*$/";
    const REGEX_TYPE_FLOAT = "/^\s*((\+|\-)?\d*\.?\d+)\s*$/";
    const REGEX_TYPE_STRING = "/.*/";
    
    protected $session_param;
    protected $regex;
    protected $type;
    protected $finish_processing;


    public function __construct($session_param, $regex=self::REGEX_TYPE_ANY, $type= self::TYPE_ANY, $finish_processing=true) {
        $this->session_param = $session_param;
        $this->regex = $regex;
        $this->type = $type;
        $this->finish_processing = $finish_processing;
    }
    
    public function value() {
        return null;
    }
    
    public function set($text) {
        if (preg_match($this->regex, $text)) {
            switch ($this->type) {
                case self::TYPE_ANY:
                    $this->api->session()->set($this->session_param, $this->valueOfAnyType($text));
                    break;
                case self::TYPE_BOOL:
                    $this->api->session()->set($this->session_param, $this->valueOfBool($text));
                    break;
                case self::TYPE_INT:
                    $this->api->session()->set($this->session_param, (int)$text);
                    break;
                case self::TYPE_FLOAT:
                    $this->api->session()->set($this->session_param, (float)$text);
                    break;
                case self::TYPE_STRING:
                    $this->api->session()->set($this->session_param, (string)$text);
                    break;
                default:
                    throw new Exception("Unknown value type");
            }
            $this->hideMessageButtons();
            if ($this->finish_processing) {
                throw new TTException(AbstractMenuMember::HANDLE_RESULT_FINISHED);
            } else {
                throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
            }
        } 
        throw new TTException("Input format mismatch");
    }
    
    protected function valueOfBool($text) {
        if ($text == 'true') {
            return true;
        } elseif ($text == 'false') {
            return false;
        } else {
            return null;
        }
    }
    
    protected function valueOfAnyType($text) {
        if (preg_match(self::REGEX_TYPE_BOOL, $text)) {
            return $this->valueOfBool($text);
        } elseif (preg_match(self::REGEX_TYPE_INT, $text)) {
            return (int)$text;
        } elseif (preg_match(self::REGEX_TYPE_FLOAT, $text)) {
            return (float)$text;
        } else {
            return $text;
        }
    }

    public function handle($message) {
        if ($text = $message->get('text')) {
            $this->set($text);
        }
    }

}
