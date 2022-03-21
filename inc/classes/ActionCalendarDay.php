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
class ActionCalendarDay extends AbstractAction {
    //put your code here
    public function run() {
        $this->api->session()->set(MenuCalendar::SELECTED_DAY, $this->value);
        throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
    }
}
