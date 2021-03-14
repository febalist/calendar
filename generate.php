<?php

namespace Febalist\Calendar;

use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$data = fopen(__DIR__.'/data/data.txt', 'rb');
$major = yaml_parse_file(__DIR__.'/data/major.yaml');
$csv = fopen(__DIR__.'/src/data.csv', 'wb');
$json = [];

foreach (range(2004, 2025) as $year) {
    $values = str_split(fgets($data));
    $date = new Carbon("$year-01-01");
    while ($date->year === $year) {
        $value = array_shift($values);
        $month = $date->month;
        $day = $date->day;

        if (in_array($day, $major[$year][$month] ?? [])) {
            $type = 3;
        } elseif ($value === '2') {
            $type = 1;
        } elseif ($value === '1') {
            $type = 2;
        } else {
            $type = 0;
        }

        if ($type) {
            fputcsv($csv, [date_create("$year-$month-$day")->format("Y-m-d"), $type]);
            $json[$year][$month][$day] = $type;
        }

        $date->addDay();
    }
}

fclose($csv);
file_put_contents(__DIR__.'/src/data.json', json_encode($json));
