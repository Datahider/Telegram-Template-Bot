<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of GoBack
 *
 * @author drweb
 */
class GoBack extends AbstractAction {
    public function run() {
        if ($this->value === null) {
            return AbstractMenuMember::HANDLE_RESULT_FINISHED;
        } else {
            return $this->value;
        }
    }
}
