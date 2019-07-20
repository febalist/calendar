<?php

namespace Febalist\Calendar;

use Carbon\Carbon;
use Jawira\CaseConverter\Convert;
use OutOfRangeException;

/**
 * @method bool isWorkday()
 * @method bool isWorkdayFull()
 * @method bool isWorkdayShort()
 *
 * @method bool isHoliday()
 * @method bool isHolidayMinor()
 * @method bool isHolidayMajor()
 *
 * @method $this addWorkday()
 * @method $this addWorkdays(int $value = 1)
 * @method $this addWorkdayFull(int $value = 1)
 * @method $this addWorkdayShort(int $value = 1)
 *
 * @method $this addHoliday()
 * @method $this addHolidays(int $value = 1)
 * @method $this addHolidayMinor(int $value = 1)
 * @method $this addHolidayMajor(int $value = 1)
 *
 * @method $this subWorkday()
 * @method $this subWorkdays(int $value = 1)
 * @method $this subWorkdayFull(int $value = 1)
 * @method $this subWorkdayShort(int $value = 1)
 *
 * @method $this subHoliday()
 * @method $this subHolidays(int $value = 1)
 * @method $this subHolidayMinor(int $value = 1)
 * @method $this subHolidayMajor(int $value = 1)
 */
class Calendar extends Carbon
{
    const TYPE_WORKDAY_FULL = 0;
    const TYPE_WORKDAY_SHORT = 1;
    const TYPE_HOLIDAY_MINOR = 2;
    const TYPE_HOLIDAY_MAJOR = 3;

    const TYPE_WORKDAY = 4;
    const TYPE_HOLIDAY = 5;

    const DEFAULT_WORKHOURS_IN_WEEK = 40;

    protected static $calendar;
    protected static $yearsRange;

    protected static function data()
    {
        if (!static::$calendar) {
            static::$calendar = json_decode(file_get_contents(__DIR__.'/data.json'), true);

            $years = array_keys(static::$calendar);
            static::$yearsRange = [min($years), max($years)];
        }

        return static::$calendar;
    }

    public function __call($method, $parameters)
    {
        $types = [
            'workday_full' => static::TYPE_WORKDAY_FULL,
            'workday_short' => static::TYPE_WORKDAY_SHORT,
            'holiday_minor' => static::TYPE_HOLIDAY_MINOR,
            'holiday_major' => static::TYPE_HOLIDAY_MAJOR,
            'workday' => static::TYPE_WORKDAY,
            'holiday' => static::TYPE_HOLIDAY,
        ];

        $unit = rtrim($method, 's');

        if (substr($unit, 0, 2) === 'is') {
            $unit = substr($unit, 2);
            $unit = with(new Convert($unit))->toSnake();

            if (in_array($unit, array_keys($types))) {
                return $this->isType($types[$unit]);
            }
        }

        $action = substr($unit, 0, 3);

        if ($action === 'add' || $action === 'sub') {
            $unit = substr($unit, 3);
            $unit = with(new Convert($unit))->toSnake();

            if (in_array($unit, array_keys($types))) {
                return $this->{"${action}Type"}($parameters[0] ?? 1, $types[$unit]);
            }
        }

        return parent::__call($method, $parameters);
    }

    public function getType()
    {
        return static::data()[$this->year][$this->month][$this->day] ?? static::TYPE_WORKDAY_FULL;
    }

    public function isType($type)
    {
        if ($type == static::TYPE_WORKDAY) {
            return in_array($this->getType(), [static::TYPE_WORKDAY_FULL, static::TYPE_WORKDAY_SHORT]);
        }

        if ($type == static::TYPE_HOLIDAY) {
            return in_array($this->getType(), [static::TYPE_HOLIDAY_MINOR, static::TYPE_HOLIDAY_MAJOR]);
        }

        return $this->getType() == $type;
    }

    public function addDaysFilter($value, callable $filter)
    {
        $result = 0;
        $step = $value >= 0 ? 1 : -1;
        $value = abs($value);

        while ($result < $value) {
            $this->addDays($step);
            $result += $filter($this);

            if ($this->year < static::$yearsRange[0] || $this->year > static::$yearsRange[1]) {
                throw new OutOfRangeException();
            }
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

    public function nextOrCurrentType($type)
    {
        if (!$this->isType($type)) {
            $this->addDaysType($type);
        }

        return $this;
    }

    public function previousOrCurrentType($type)
    {
        if (!$this->isType($type)) {
            $this->subDaysType($type);
        }

        return $this;
    }

    public function nextOrCurrentWorkday()
    {
        return $this->nextOrCurrentType(static::TYPE_WORKDAY);
    }

    public function previousOrCurrentWorkday()
    {
        return $this->previousOrCurrentType(static::TYPE_WORKDAY);
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
        return $this->daysTypeBetween($date, static::TYPE_WORKDAY);
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
        return $this->daysTypeBetween($date, static::TYPE_WORKDAY);
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
