<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of Action
 *
 * @author drweb
 */
abstract class AbstractAction extends AbstractMenuMember {
    protected $name;
    protected $value;

    public function __construct($name, $representation, $value=null) {
        $this->name = $name;
        $this->representation = $representation;
        $this->value = $value;
    }
    
    public function value() {
        return $this->name;
    }
    
    public abstract function run();
}
