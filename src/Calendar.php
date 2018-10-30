<?php

namespace Febalist\Calendar;

use Carbon\Carbon;

class Calendar extends Carbon
{
    protected static $calendar;

    protected static function calendar()
    {
        if (!static::$calendar) {
            static::$calendar = json_decode(file_get_contents(__DIR__.'/calendar.json'));
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

    protected function inCalendar($type)
    {
        return in_array($this->toDateString(), static::calendar()[$type]);
    }
}
