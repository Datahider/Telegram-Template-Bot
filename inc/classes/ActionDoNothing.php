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
class ActionDoNothing extends AbstractAction {
    //put your code here
    public function run() {
        throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
    }
}
