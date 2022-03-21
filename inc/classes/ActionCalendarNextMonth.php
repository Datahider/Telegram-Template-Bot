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
class ActionCalendarNextMonth extends AbstractAction {
    //put your code here
    public function run() {
        $selected_month = $this->api->session()->get(MenuCalendar::SELECTED_MONTH, 1);
        
        if ($selected_month == 12) {
            $this->api->session()->set(MenuCalendar::SELECTED_MONTH, 1);
            $this->api->session()->set(MenuCalendar::SELECTED_YEAR, 
                    $this->api->session()->get(MenuCalendar::SELECTED_YEAR, 1)+1);
        } else {
            $this->api->session()->set(MenuCalendar::SELECTED_MONTH, $selected_month+1);
        }
        return AbstractMenuMember::HANDLE_RESULT_PROGRESS;
    }
}
