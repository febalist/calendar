<?php

namespace Febalist\Calendar;

use Carbon\Carbon;

class Calendar extends Carbon
{
    protected static $calendar;

    protected static function data()
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
        return $this->sumBetweenDays($date, function (Calendar $date) use ($full) {
            return $date->isWorkday($full) ? 1 : 0;
        });
    }

    public function workdaysInMonth($full = false)
    {
        return $this->clone()->startOfMonth()
            ->workdaysBetween($this->clone()->endOfMonth(), $full);
    }

    public function holidaysBetween($date = null)
    {
        return $this->sumBetweenDays($date, function (Calendar $date) {
            return $date->isHoliday() ? 1 : 0;
        });
    }

    public function holidaysInMonth()
    {
        return $this->clone()->startOfMonth()
            ->holidaysBetween($this->clone()->endOfMonth());
    }

    public function workhoursInDay($workhoursInWeek = 40)
    {
        if ($this->isHoliday()) {
            return 0;
        }

        $workhours = $workhoursInWeek / 5;

        return $this->isWorkday(true) ? $workhours : $workhours - 1;
    }

    public function workhoursBetween($date = null, $workhoursInWeek = 40)
    {
        return $this->sumBetweenDays($date, function (Calendar $date) use ($workhoursInWeek) {
            return $date->workhoursInDay($workhoursInWeek);
        });
    }

    public function workhoursInMonth($workhoursInWeek = 40)
    {
        return $this->clone()->startOfMonth()
            ->workhoursBetween($this->clone()->endOfMonth(), $workhoursInWeek);
    }

    protected function inCalendar($type)
    {
        return in_array($this->toDateString(), static::data()[$type]);
    }

    protected function sumBetweenDays($date, callable $callback)
    {
        $sum = 0;

        $date = $this->resolveCarbon($date);

        $dates = [$this, $date];
        if ($this > $date) {
            $dates = array_reverse($dates);
        }
        $date = $dates[0]->copy()->startOfDay();
        $end = $dates[1]->copy()->startOfDay();

        while ($date <= $end) {
            $sum += $callback($date);
            $date->addDay();
        }

        return $sum;
    }
}
