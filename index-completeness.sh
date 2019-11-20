#!/bin/bash

index_file(){
  FILE=$1
  random_time=$(shuf -i1-6 -n1)
  sleep $random_time
  time=$(date +"%F %T")
  echo "$time> indexing $FILE (version: $VERSION)"
  php incremental-index-completeness.php --port 8984 --collection $VERSION --with-check --file $FILE
}

get_running_tasks_count(){
  task_count=$(ps aux | grep "[ ]incremental-index-completeness.php" | wc -l)
}

# BEGIN do not change section ------
open_sem(){
  mkfifo pipe-$$
  exec 3<>pipe-$$
  rm pipe-$$
  local i=$1
  for((;i>0;i--)); do
    printf %s 000 >&3
  done
}

run_with_lock(){
  local x
  read -u 3 -n 3 x && ((0==x)) || exit $x
  (
    ( "$@"; )
    printf '%.3d' $? >&3
  )&
}
# END do not change section ------

task_count=0
SECONDS=0

VERSION=$1
dir=/home/pkiraly/data-export/$1
files=$(ls $dir/parts-completeness/*.csv)

N=6
open_sem $N
for file in $files; do
  run_with_lock index_file $file
done

get_running_tasks_count
while [[ $task_count != 0 ]]; do
  echo "wait a bit"
  sleep 10
  get_running_tasks_count
done

curl -s http://localhost:8984/solr/$VERSION/update -H 'Content-type: text/xml' --data-binary '<commit />'
curl -s http://localhost:8984/solr/$VERSION/update -H 'Content-type: text/xml' --data-binary '<optimize />'

duration=$SECONDS
hours=$(($duration / (60*60)))
mins=$(($duration % (60*60) / 60))
secs=$(($duration % 60))

time=$(date +"%F %T")
echo "$time> index-completeness DONE"
printf "%02d:%02d:%02d elapsed.\n" $hours $mins $secs

