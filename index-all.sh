#!/usr/bin/env bash

VERSION=$1
if [[ "$VERSION" = "" ]]; then
  echo "Please specify a version"
  exit 1;
fi

SECONDS=0

# index all
./nano index-languages.sh $VERSION
./index-multilingual-saturation.sh $VERSION
./index-completeness.sh $VERSION

# check the result
./check-solr-counts.sh $VERSION

duration=$SECONDS
hours=$(($duration / (60*60)))
mins=$(($duration % (60*60) / 60))
secs=$(($duration % 60))

time=$(date +"%F %T")
echo "$time> All indexing DONE"
printf "%02d:%02d:%02d elapsed.\n" $hours $mins $secs
