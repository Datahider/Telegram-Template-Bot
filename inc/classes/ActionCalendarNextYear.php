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
class ActionCalendarNextYear extends AbstractAction {
    //put your code here
    public function run() {
        $this->api->session()->set(MenuCalendar::SELECTED_YEAR, 
                $this->api->session()->get(MenuCalendar::SELECTED_YEAR, 1)+1);
    }
}
