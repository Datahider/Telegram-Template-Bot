<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of ActionCalendarPrevMonth
 *
 * @author drweb
 */
class ActionCalendarNow extends AbstractAction {
    //put your code here
    public function run() {
        $this->value->setDate();
        throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
    }
}
