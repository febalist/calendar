<?php

namespace Febalist\Calendar;

use Carbon\Carbon;

class Calendar extends Carbon
{
    protected static $calendar;

    protected static function calendar()
    {
        if (!static::$calendar) {
            static::$calendar = json_decode(file_get_contents(__DIR__.'/calendar.json'), true);
        }

        return static::$calendar;
    }

    public function isWorkday($full = false)
    {
        return !$this->isHoliday() && (!$full || !$this->isShort());
    }

    public function isHoliday()
    {
        return $this->inCalendar('holidays');
    }

    public function isShort()
    {
        return $this->inCalendar('preholidays');
    }

    public function addWorkday($value = 1, $full = false)
    {
        return $this->addWorkdays($value, $full);
    }

    public function addWorkdays($value, $full = false)
    {
        $workdays = 0;
        $step = $value >= 0 ? 1 : -1;
        $value = abs($value);

        while ($workdays < $value) {
            $this->addDay($step);
            $workdays += $this->isWorkday($full);
        }

        return $this;
    }

    public function subWorkday($value = 1, $full = false)
    {
        return $this->subWorkdays($value, $full);
    }

    public function subWorkdays($value, $full = false)
    {
        return $this->addWorkdays(-1 * $value, $full);
    }

    public function previousOrCurrentWorkday($full = false)
    {
        if (!$this->isWorkday($full)) {
            $this->subWorkday(1, $full);
        }

        return $this;
    }

    public function nextOrCurrentWorkday($full = false)
    {
        if (!$this->isWorkday($full)) {
            $this->addWorkday(1, $full);
        }

        return $this;
    }

    public function workdaysBetween($date = null, $full = false)
    {
        $workdays = 0;

        $date = $this->resolveCarbon($date);

        $dates = [$this, $date];
        if ($this > $date) {
            $dates = array_reverse($dates);
        }
        $date = $dates[0]->copy()->startOfDay();
        $end = $dates[1]->copy()->startOfDay();

        while ($date <= $end) {
            $workdays += $date->isWorkday($full);
            $date->addWorkday(1, $full);
        }

        return $workdays;
    }

    protected function inCalendar($type)
    {
        return in_array($this->toDateString(), static::calendar()[$type]);
    }
}
