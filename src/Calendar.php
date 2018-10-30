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

    public function isWorkday()
    {
        return !$this->isHoliday();
    }

    public function isHoliday()
    {
        return $this->inCalendar('holidays');
    }

    public function isShortened()
    {
        return $this->inCalendar('preholidays');
    }

    public function addWorkday($value = 1)
    {
        return $this->addWorkdays($value);
    }

    public function addWorkdays($value)
    {
        $workdays = 0;
        $step = $value >= 0 ? 1 : -1;

        while ($workdays < $value) {
            $this->addDay($step);
            $workdays += $this->isWorkday();
        }

        return $this;
    }

    public function subWorkday($value = 1)
    {
        return $this->subWorkdays($value);
    }

    public function subWorkdays($value)
    {
        return $this->addWorkdays(-1 * $value);
    }

    public function workdaysBetween($date = null)
    {
        $workdays = 0;

        $date = $this->resolveCarbon($date)->copy()->startOfDay();

        $dates = [$this, $date];
        if ($this > $date) {
            $dates = array_reverse($dates);
        }
        $date = $dates[0]->copy()->startOfDay();
        $end = $dates[1]->copy()->startOfDay();

        while ($date <= $end) {
            $workdays += $date->isWorkday();
            $date->addWorkday();
        }

        return $workdays;
    }

    protected function inCalendar($type)
    {
        return in_array($this->toDateString(), static::calendar()[$type]);
    }
}
