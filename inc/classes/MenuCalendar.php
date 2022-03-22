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
    
    const REGEX_TIME = "/^([01]?\d|2[0-3])(\:?)(\d\d)?$/";
    
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
    
    protected $session_param;


    public function __construct(string $name, string $representation, string $text, string $session_param) {
        
        parent::__construct($name, $representation, $text, []);
        $this->session_param = $session_param;
    }
    
    protected function makeOptions() {
        $header = $this->selectedMonthYearAsText();
        
        $this->options = [
            new SetFromString(self::SELECTED_HOUR, self::REGEX_TIME),
            new ActionDoNothing('cur_month', $header),
            new LineSeparator(),
            new ActionCalendarPrevYear('prev_year', "««"),
            new ActionCalendarPrevMonth('prev_month', "«"),
            new ActionCalendarNow('now', "Тек.", $this),
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
        
        $month = $this->api->session()->get(self::SELECTED_MONTH);
        $year = $this->api->session()->get(self::SELECTED_YEAR);
        
        $loop_data = $this->monthLoopData($month, $year);
        $day = $this->correctDay($loop_data['days']);
        
        $count = 0;
        for( $i = $loop_data['loopstart']; $i <= $loop_data['loopstop']; $i++) {
            if ($count == 0) {
                $this->options[] = new LineSeparator();
            }
            if (($i <= 0) || ($i > $loop_data['days'])) {
                $this->options[] = new ActionDoNothing("empty$i", ' ');
            } else {
                $this->options[] = new ActionCalendarDay("day$i", $this->dayDisplay($i), $i);
            }
            $count++;
            if ($count == 7) {
                $count = 0;
            }
        }
        $this->options[] = new LineSeparator();
        $this->options[] = new GoBack('go_back', 'Отмена', 2);

        $this->bindApi($this->api);
    }

    protected function dayDisplay($day) {
        $selected_day = $this->api->session()->get(self::SELECTED_DAY);
        $selected_month = $this->api->session()->get(self::SELECTED_MONTH);
        $selected_year = $this->api->session()->get(self::SELECTED_YEAR);
        
        //🔆☀️🔅
        if ($day == $selected_day) {
            return $this->isCurrent($selected_year, $selected_month, $selected_day) ? '☀' : '🔸';
        } else {
            return $this->isCurrent($selected_year, $selected_month, $day) ? '️🔅' : "$day";
        }
        return "$day";
    }
    
    protected function correctDay($max_day) {
        $day = $this->api->session()->get(self::SELECTED_DAY);
        if ($day > $max_day) {
            $day = $max_day;
            $this->api->session()->set(self::SELECTED_DAY, $day);
        }
        return $day;
    }
    
    public function show() {
        if (!$this->api->session()->get(self::SELECTED_YEAR, false)) {
            $selected_date = $this->api->session()->get($this->session_param, false);
            $this->setDate($selected_date);
        }
        
        $this->makeOptions();
        parent::show();
    }
    
    public function handle($update) {
        $this->makeOptions();
        try {
            parent::handle($update);
        } catch (Exception $e) {
            if ($this->isFinished($e)) {
                if ($e->getCode() == 0) {
                    $this->setSessionParam();
                } else {
                    $e = new TTException($e->getMessage(), $e->getCode()-1);
                }
                $this->cleanup();
            }
            throw $e;
        }
    }
    
    protected function cleanup() {
        $this->api->session()->setParams([
            self::SELECTED_YEAR, 
            self::SELECTED_MONTH,
            self::SELECTED_DAY,
            self::SELECTED_HOUR
        ], false);
    }
    
    protected function setSessionParam() {
        $year = $this->api->session()->get(self::SELECTED_YEAR);
        $month = $this->api->session()->get(self::SELECTED_MONTH);
        $day = $this->api->session()->get(self::SELECTED_DAY);
        
        preg_match(self::REGEX_TIME, $this->api->session()->get(self::SELECTED_HOUR), $matches);
        
        $date = sprintf("%04u-%02u-%02u %02u:%02u:00", $year, $month, $day, $matches[1], $matches[3]);
        
        $this->api->session()->set($this->session_param, $date);
    }
    protected function selectedDateAsText() {
        $noday = $this->selectedMonthYearAsText();
        $day = $this->api->session()->get(self::SELECTED_DAY, 1);
        
        return "$day $noday";
    }
    
    public function setDate($date=null) {
        $time = time();

        if ($date) {
            $datetime = new DateTime($date);
            $time = $datetime->format('U');
        }

        $this->setYear(localtime($time, true)['tm_year']+1900);
        $this->setMonth(localtime($time, true)['tm_mon']+1);
        $this->setDay(localtime($time, true)['tm_mday']);
    }
    
    protected function selectedMonthYearAsText() {
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
    
    protected function setDay($day) {
        $this->api->session()->set(self::SELECTED_DAY, $day);
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
    
    protected function exceptionHandler($e) {
        if ($this->isTTException($e) && $e->getMessage() == 'Input format mismatch') {
            $this->api->answerHTML('Введите время в формате <b>Ч:MM</b>, <b>ЧММ</b> или <b>Ч</b>');
            throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
        }
        parent::exceptionHandler($e);
    }
    
    protected function isCurrent($year, $month, $day) {
        $tm = localtime(time(), true);
        $result = (($tm['tm_year']+1900 == $year) && ($tm['tm_mon']+1 == $month) && ($tm['tm_mday'] == $day));
        return $result;
    }
}
