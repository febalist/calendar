<?php

namespace Febalist\Calendar;

require __DIR__.'/../../vendor/autoload.php';

$celebrations = json_decode(file_get_contents(__DIR__.'/data/celebrations.json'), true);
$content = json_decode(file_get_contents(__DIR__.'/data/content.json'), true);

$data = [];
$year_celebrations = [];

foreach ($content as $year_content) {
    $year_content = array_values($year_content);
    $year = (int) $year_content[0];

    if (isset($celebrations[$year])) {
        $year_celebrations = $celebrations[$year];
    }

    foreach (range(1, 12) as $month) {
        $days = explode(',', $year_content[$month]);
        foreach ($days as $day_data) {
            $day = (int) $day_data;

            if (in_array($day, $year_celebrations[$month] ?? [])) {
                $type = Calendar::TYPE_HOLIDAY_CELEBRATION;
            } elseif (strpos($day_data, '*') !== false) {
                $type = Calendar::TYPE_WORKDAY_SHORT;
            } else {
                $type = Calendar::TYPE_HOLIDAY_USUAL;
            }

            $data[$year][$month][$day] = $type;
        }
    }
}

file_put_contents(__DIR__.'/src/data.json', json_encode($data));
