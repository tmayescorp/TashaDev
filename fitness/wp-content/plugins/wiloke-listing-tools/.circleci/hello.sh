#!/bin/bash
#while getopts u:a: flag
#do
#  case "${flag}" in
#    u) username=${OPTARG};;
#    a) age=${OPTARG};;
#  esac
#done
#
#echo "Username: $username"
#echo "Age: $age"

i=1;
for user in "$@"
do
  echo "Username is $user"
  i=$((i+1))
done
