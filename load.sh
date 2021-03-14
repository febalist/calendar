#!/usr/bin/env bash
set -e

file=data/data.txt
year=2004

rm -f $file

while ((year <= 2025)); do
  curl "https://isdayoff.ru/api/getdata?year=$year&pre=1" >> $file
  echo >> $file
  year=$((year + 1))
done
