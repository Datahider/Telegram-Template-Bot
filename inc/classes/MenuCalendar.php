<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of MenuCalendar
 *
 * @author drweb
 */
class MenuCalendar extends UserMenu {
    const SELECTED_MONTH = 'MenuCalendar::selected_month';
    const SELECTED_YEAR = 'MenuCalendar::selected_year';
    const SELECTED_DAY = 'MenuCalendar::selected_day';
    const SELECTED_HOUR = 'MenuCalendar::selected_hour';
    const STARTING_HOUR = 'MenuCalendar::starting_hour';
    
    const JAN = 'Январь';
    const FEB = 'Февраль';
    const MAR = 'Март';
    const APR = 'Апрель';
    const MAY = 'Май';
    const JUN = 'Июнь';
    const JUL = 'Июль';
    const AUG = 'Август';
    const SEP = 'Сентябрь';
    const OCT = 'Октябрь';
    const NOV = 'Ноябрь';
    const DEC = 'Декабрь';
    
    protected $input_time_text;
    protected $input_date_text;


    public function __construct(string $name, string $representation, string $text, string $input_time_text, string $session_param) {
        
        parent::__construct($name, $representation, $text, []);
        $this->input_time_text = $input_time_text;
        $this->input_date_text = $text;
    }
    
    protected function makeOptionsCalendar() {
        $header = $this->selectedMonthYearAsText();
        $this->text = $this->input_date_text;
        
        $this->options = [
            new SetFromString(self::SELECTED_YEAR, "/^\d{4}$/"),
            new ActionDoNothing('cur_month', $header),
            new LineSeparator(),
            new ActionCalendarPrevYear('prev_year', "««"),
            new ActionCalendarPrevMonth('prev_month', "«"),
            new ActionCalendarNow('now', "Тек."),
            new ActionCalendarNextMonth('next_month', "»"),
            new ActionCalendarNextYear('next_year', "»»"),
            new LineSeparator(),
            new ActionDoNothing('mon', 'пн'),
            new ActionDoNothing('tue', 'вт'),
            new ActionDoNothing('wed', 'ср'),
            new ActionDoNothing('thu', 'чт'),
            new ActionDoNothing('fri', 'пт'),
            new ActionDoNothing('sat', 'сб'),
            new ActionDoNothing('sun', 'вс'),
        ];
        
        $loop_data = $this->monthLoopData($this->api->session()->get(self::SELECTED_MONTH), $this->api->session()->get(self::SELECTED_YEAR));
        $count = 0;
        for( $i = $loop_data['loopstart']; $i <= $loop_data['loopstop']; $i++) {
            if ($count == 0) {
                $this->options[] = new LineSeparator();
            }
            if (($i <= 0) || ($i > $loop_data['days'])) {
                $this->options[] = new ActionDoNothing("empty$i", ' ');
            } else {
                $this->options[] = new ActionCalendarDay("day$i", $i, $i);
            }
            $count++;
            if ($count == 7) {
                $count = 0;
            }
        }
    }

    protected function makeOptionsHours() {
        $header = $this->selectedDateAsText();
        $this->text = $this->input_time_text;
        
        $this->options = [
            new ActionCalendarBackToDate('selected_date', "« $header"),
            new SetFromString(MenuCalendar::SELECTED_HOUR, "/^\d\d?\:?\d\d$/")
        ];    
    }
    
    protected function makeOptionsMinutes() {
        
    }
    
    protected function makeOptions() {
        if ($this->api->session()->get(self::SELECTED_DAY, false)) {
            $this->makeOptionsHours();
        } else {
            $this->makeOptionsCalendar();
        }
        $this->bindApi($this->api);
    }

    public function show() {
        $this->makeOptions();
        parent::show();
    }
    
    public function handle($update) {
        $this->makeOptions();
        parent::handle($update);
    }
    
    protected function selectedDateAsText() {
        $noday = $this->selectedMonthYearAsText();
        $day = $this->api->session()->get(self::SELECTED_DAY, 1);
        
        return "$day $noday";
    }
    
    protected function selectedMonthYearAsText() {
        if (!$this->api->session()->get(self::SELECTED_YEAR, false)) {
            $this->setYear(localtime(time(), true)['tm_year']+1900);
        }
        if (!$this->api->session()->get(self::SELECTED_MONTH, false)) {
            $this->setMonth(localtime(time(), true)['tm_mon']+1);
        }
        $year = $this->api->session()->get(self::SELECTED_YEAR);
        $month = $this->api->session()->get(self::SELECTED_MONTH);
        $month_name = ['', self::JAN, self::FEB, self::MAR, self::APR, self::MAY, self::JUN, self::JUL, self::AUG, self::SEP, self::OCT, self::NOV, self::DEC][$month];
        return "$month_name $year";
    }
    
    protected function setYear($year) {
        $this->api->session()->set(self::SELECTED_YEAR, $year);
    }

    protected function setMonth($month) {
        $this->api->session()->set(self::SELECTED_MONTH, $month);
    }
    
    protected function monthLoopData($month, $year) {
        $time = mktime(0, 0, 0, $month, 8, $year);
        $days = (int)date('t', $time);
        $weekday = (int)date('w', $time);
        $correct_weekday = $weekday == 0 ? 7 : $weekday;
        $loopstart = 2 - $correct_weekday;
        $totaldays = $days - $loopstart + 1;
        $remainder = $totaldays % 7; 
        $loopstop = $remainder == 0 ? $days : $days - $remainder + 7;
        
        return [
            'days' => $days,
            'loopstart' => $loopstart,
            'loopstop' => $loopstop
        ];
    }
    
}
