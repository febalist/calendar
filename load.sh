#!/usr/bin/env bash
source .env

[[ -n $TOKEN ]] || exit 1

url="https://data.gov.ru/api/json/dataset/7708660670-proizvcalendar/version/20151123T183036/content"
wget --timeout 10 -O data/content.json "$url?access_token=$TOKEN"
