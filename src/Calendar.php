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
 * @method $this addWorkdayFull()
 * @method $this addWorkdayShort()
 * @method $this addWorkdays(int $value = 1)
 * @method $this addWorkdaysFull(int $value = 1)
 * @method $this addWorkdaysShort(int $value = 1)
 *
 * @method $this addHoliday()
 * @method $this addHolidayMinor()
 * @method $this addHolidayMajor()
 * @method $this addHolidays(int $value = 1)
 * @method $this addHolidaysMinor(int $value = 1)
 * @method $this addHolidaysMajor(int $value = 1)
 *
 * @method $this subWorkday()
 * @method $this subWorkdayFull()
 * @method $this subWorkdayShort()
 * @method $this subWorkdays(int $value = 1)
 * @method $this subWorkdaysFull(int $value = 1)
 * @method $this subWorkdaysShort(int $value = 1)
 *
 * @method $this subHoliday()
 * @method $this subHolidayMinor()
 * @method $this subHolidayMajor()
 * @method $this subHolidays(int $value = 1)
 * @method $this subHolidaysMinor(int $value = 1)
 * @method $this subHolidaysMajor(int $value = 1)
 *
 * @method $this nearestNextWorkday()
 * @method $this nearestNextWorkdayFull()
 * @method $this nearestNextWorkdayShort()
 * @method $this nearestNextHoliday()
 * @method $this nearestNextHolidayMinor()
 * @method $this nearestNextHolidayMajor()
 *
 * @method $this nearestPrevWorkday()
 * @method $this nearestPrevWorkdayFull()
 * @method $this nearestPrevWorkdayShort()
 * @method $this nearestPrevHoliday()
 * @method $this nearestPrevHolidayMinor()
 * @method $this nearestPrevHolidayMajor()
 *
 * @method int workdaysBetween($date)
 * @method int workdaysFullBetween($date)
 * @method int workdaysShortBetween($date)
 * @method int holidaysBetween($date)
 * @method int holidaysMinorBetween($date)
 * @method int holidaysMajorBetween($date)
 *
 * @method int workdaysInWeek()
 * @method int workdaysInMonth()
 * @method int workdaysInQuarter()
 * @method int workdaysInYear()
 * @method int workdaysInDecade()
 * @method int workdaysFullInWeek()
 * @method int workdaysFullInMonth()
 * @method int workdaysFullInQuarter()
 * @method int workdaysFullInYear()
 * @method int workdaysFullInDecade()
 * @method int workdaysShortInWeek()
 * @method int workdaysShortInMonth()
 * @method int workdaysShortInQuarter()
 * @method int workdaysShortInYear()
 * @method int workdaysShortInDecade()
 *
 * @method int holidaysInWeek()
 * @method int holidaysInMonth()
 * @method int holidaysInQuarter()
 * @method int holidaysInYear()
 * @method int holidaysInDecade()
 * @method int holidaysMinorInWeek()
 * @method int holidaysMinorInMonth()
 * @method int holidaysMinorInQuarter()
 * @method int holidaysMinorInYear()
 * @method int holidaysMinorInDecade()
 * @method int holidaysMajorInWeek()
 * @method int holidaysMajorInMonth()
 * @method int holidaysMajorInQuarter()
 * @method int holidaysMajorInYear()
 * @method int holidaysMajorInDecade()
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

            'workdays_full' => static::TYPE_WORKDAY_FULL,
            'workdays_short' => static::TYPE_WORKDAY_SHORT,
            'holidays_minor' => static::TYPE_HOLIDAY_MINOR,
            'holidays_major' => static::TYPE_HOLIDAY_MAJOR,
            'workdays' => static::TYPE_WORKDAY,
            'holidays' => static::TYPE_HOLIDAY,
        ];

        if (substr($method, 0, 2) === 'is') {
            $type = $this->snake(substr($method, 2));

            if (in_array($type, array_keys($types))) {
                return $this->isType($types[$type]);
            }
        }

        $action = substr($method, 0, 3);

        if ($action === 'add' || $action === 'sub') {
            $type = $this->snake(substr($method, 3));

            if (in_array($type, array_keys($types))) {
                return $this->{"${action}Type"}($types[$type], $parameters[0] ?? 1);
            }
        }

        $action = substr($method, 0, 7);

        if ($action === 'nearest') {
            $direction = substr($method, 7, 4);

            if (in_array($direction, ['Next', 'Prev'])) {
                $type = $this->snake(substr($method, 11));

                if (in_array($type, array_keys($types))) {
                    return $this->{"${action}${direction}Type"}($types[$type]);
                }
            }
        }

        if (substr($method, -7) === 'Between') {
            $type = $this->snake(substr($method, 0, -7));

            if (in_array($type, array_keys($types))) {
                return $this->typeBetween($parameters[0], $types[$type]);
            }
        }

        if (preg_match('/^(.+)In(Week|Month|Quarter|Year|Decade)$/', $method, $match)) {
            $type = $this->snake($match[1]);
            $unit = $match[2];

            if (in_array($type, array_keys($types))) {
                return $this->typeBetweenUnit($unit, $types[$type]);
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

    public function addType($type, $value)
    {
        $this->addDaysFilter($value, function (Calendar $date) use ($type) {
            return $date->isType($type);
        });

        return $this;
    }

    public function subType($type, $value)
    {
        return $this->addType($type, $value * -1);
    }

    public function nearestNextType($type)
    {
        if (!$this->isType($type)) {
            $this->addType($type, 1);
        }

        return $this;
    }

    public function nearestPrevType($type)
    {
        if (!$this->isType($type)) {
            $this->subType($type, 1);
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

    public function typeBetween($date, $type)
    {
        return $this->sumBetweenDays($date, function (Calendar $date) use ($type) {
            return $date->isType($type);
        });
    }

    public function typeBetweenUnit($unit, $type)
    {
        $range = $this->unitRange($unit);

        return $range[0]->typeBetween($range[1], $type);
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

    /** @return static[] */
    public function unitRange($unit)
    {
        return [
            $this->copy()->startOf($unit),
            $this->copy()->endOf($unit),
        ];
    }

    protected function resolveCalendar($date = null)
    {
        if ($date instanceof self) {
            return $date;
        }

        return static::parse($date);
    }

    private function snake($string)
    {
        return with(new Convert($string))->toSnake();
    }
}
