#!/usr/bin/env bash
token=$1
[[ -n $token ]] || exit 1

url="https://data.gov.ru/api/json/dataset/7708660670-proizvcalendar/version/20151123T183036/content"
wget -O data/content.json "$url?access_token=$token"
