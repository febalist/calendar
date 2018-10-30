<?php

if (!function_exists('calendar')) {
    /**
     * @return Febalist\Calendar\Calendar
     */
    function calendar($date = null)
    {
        return Febalist\Calendar\Calendar::parse($date);
    }
}
