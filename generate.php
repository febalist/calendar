<?php

namespace Febalist\Calendar;

$major = json_decode(file_get_contents(__DIR__.'/data/major.json'), true);
$content = json_decode(file_get_contents(__DIR__.'/data/content.json'), true);
$csv = fopen(__DIR__.'/src/data.csv', 'wb');
$data = [];
$year_major = [];

foreach ($content as $year_content) {
    $year_content = array_values($year_content);
    $year = (int) $year_content[0];

    if (isset($major[$year])) {
        $year_major = $major[$year];
    }

    foreach (range(1, 12) as $month) {
        $days = explode(',', $year_content[$month]);
        foreach ($days as $day_data) {
            $day = (int) $day_data;

            if (in_array($day, $year_major[$month] ?? [])) {
                $type = 3;
            } elseif (strpos($day_data, '*') !== false) {
                $type = 1;
            } else {
                $type = 2;
            }

            fputcsv($csv, [date_create("$year-$month-$day")->format("Y-m-d"), $type]);
            $data[$year][$month][$day] = $type;
        }
    }
}

fclose($csv);
file_put_contents(__DIR__.'/src/data.json', json_encode($data));
