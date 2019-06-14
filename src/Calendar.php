<?php

namespace Febalist\Calendar;

use Carbon\Carbon;

class Calendar extends Carbon
{
    const IS_WORKDAY = 1;
    const IS_HOLIDAY = 2;
    const IS_SPECIAL = 4;

    const TYPE_WORKDAY_FULL = self::IS_WORKDAY;
    const TYPE_WORKDAY_SHORT = self::IS_WORKDAY | self::IS_SPECIAL;
    const TYPE_HOLIDAY_USUAL = self::IS_HOLIDAY;
    const TYPE_HOLIDAY_CELEBRATION = self::IS_HOLIDAY | self::IS_SPECIAL;

    const DEFAULT_WORKHOURS_IN_WEEK = 40;

    protected static $calendar;

    protected static function data()
    {
        if (!static::$calendar) {
            static::$calendar = json_decode(file_get_contents(__DIR__.'/data.json'), true);
        }

        return static::$calendar;
    }

    public function getType()
    {
        return static::data()[$this->year][$this->month][$this->day] ?? static::TYPE_WORKDAY_FULL;
    }

    public function isType($type)
    {
        return static::getType() & $type;
    }

    public function isWorkdayFull()
    {
        return static::isType(static::TYPE_WORKDAY_FULL);
    }

    public function isWorkdayShort()
    {
        return static::isType(static::TYPE_WORKDAY_SHORT);
    }

    public function isWorkday()
    {
        return static::isType(static::IS_WORKDAY);
    }

    public function isHolidayUsual()
    {
        return static::isType(static::TYPE_HOLIDAY_USUAL);
    }

    public function isHolidayCelebration()
    {
        return static::isType(static::TYPE_HOLIDAY_CELEBRATION);
    }

    public function isHoliday()
    {
        return static::isType(static::IS_HOLIDAY);
    }

    public function addDaysFilter($value, callable $filter)
    {
        $result = 0;
        $step = $value >= 0 ? 1 : -1;
        $value = abs($value);

        while ($result < $value) {
            $this->addDays($step);
            $result += $filter($this);
        }

        return $this;
    }

    public function subDaysFilter($value, callable $filter)
    {
        return $this->addDaysFilter(-1 * $value, $filter);
    }

    public function addDaysType($value, $type)
    {
        $this->addDaysFilter($value, function (Calendar $date) use ($type) {
            return $date->isType($type);
        });

        return $this;
    }

    public function subDaysType($value, $type)
    {
        return $this->addDaysType(-1 * $value, $type);
    }

    public function addWorkdays($value)
    {
        return $this->addDaysType($value, static::IS_WORKDAY);
    }

    public function subWorkdays($value)
    {
        return $this->addWorkdays(-1 * $value);
    }

    public function addWorkday()
    {
        return $this->addWorkdays(1);
    }

    public function subWorkday()
    {
        return $this->subWorkdays(1);
    }

    public function nextOrCurrentDayType($type)
    {
        if (!$this->isType($type)) {
            $this->addDaysType($type);
        }

        return $this;
    }

    public function previousOrCurrentDayType($type)
    {
        if (!$this->isType($type)) {
            $this->subDaysType($type);
        }

        return $this;
    }

    public function sumBetweenDays($date, callable $callback)
    {
        $sum = 0;

        $date = $this->resolveCalendar($date);

        /** @var static[] $dates */
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

    public function daysTypeBetween($date, $type)
    {
        return $this->sumBetweenDays($date, function (Calendar $date) use ($type) {
            return $date->isType($type);
        });
    }

    public function workdaysBetween($date = null)
    {
        return $this->daysTypeBetween($date, static::IS_WORKDAY);
    }

    public function workdaysInMonth()
    {
        return $this->copy()->startOfMonth()
            ->workdaysBetween($this->copy()->endOfMonth());
    }

    public function workdaysInYear()
    {
        return $this->copy()->startOfYear()
            ->workdaysBetween($this->copy()->endOfYear());
    }

    public function holidaysBetween($date = null)
    {
        return $this->daysTypeBetween($date, static::IS_HOLIDAY);
    }

    public function holidaysInMonth()
    {
        return $this->copy()->startOfMonth()
            ->holidaysBetween($this->copy()->endOfMonth());
    }

    public function holidaysInYear()
    {
        return $this->copy()->startOfYear()
            ->holidaysBetween($this->copy()->endOfYear());
    }

    public function workhoursInDay($workhoursInWeek = self::DEFAULT_WORKHOURS_IN_WEEK)
    {
        if ($this->isHoliday()) {
            return 0;
        }

        $workhours = $workhoursInWeek / 5;

        return $this->isWorkdayFull() ? $workhours : $workhours - 1;
    }

    public function workhoursBetween($date = null, $workhoursInWeek = self::DEFAULT_WORKHOURS_IN_WEEK)
    {
        return $this->sumBetweenDays($date, function (Calendar $date) use ($workhoursInWeek) {
            return $date->workhoursInDay($workhoursInWeek);
        });
    }

    public function workhoursInWeek($workhoursInWeek = self::DEFAULT_WORKHOURS_IN_WEEK)
    {
        return $this->copy()->startOfWeek()
            ->workhoursBetween($this->copy()->endOfWeek(), $workhoursInWeek);
    }

    public function workhoursInMonth($workhoursInWeek = self::DEFAULT_WORKHOURS_IN_WEEK)
    {
        return $this->copy()->startOfMonth()
            ->workhoursBetween($this->copy()->endOfMonth(), $workhoursInWeek);
    }

    public function workhoursInYear($workhoursInWeek = self::DEFAULT_WORKHOURS_IN_WEEK)
    {
        return $this->copy()->startOfYear()
            ->workhoursBetween($this->copy()->endOfYear(), $workhoursInWeek);
    }

    protected function resolveCalendar($date = null)
    {
        if ($date instanceof self) {
            return $date;
        }

        return static::parse($date);
    }
}
