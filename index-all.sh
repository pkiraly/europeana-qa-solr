#!/usr/bin/env bash

VERSION=$1
if [[ "$VERSION" = "" ]]; then
  echo "Please specify a version"
  exit 1;
fi

SECONDS=0

# index all
printf "%s %s> Create Solr collection\n" $(date +"%F %T")
./create-solr-collection.sh $VERSION

LOG=logs/index-completeness.log
printf "%s %s> Indexing completeness (%s)\n" $(date +"%F %T") $LOG
./index-completeness.sh $VERSION > ${LOG}

LOG=logs/index-multilingual-saturation.log
printf "%s %s> Indexing multilingual saturation (%s)\n" $(date +"%F %T") $LOG
./index-multilingual-saturation.sh $VERSION > ${LOG}

LOG=logs/index-languages.log
printf "%s %s> Indexing languages (%s)\n" $(date +"%F %T") $LOG
./index-languages.sh $VERSION > ${LOG}

# check the result
printf "%s %s> Check Solr collection\n" $(date +"%F %T")
./check-solr-counts.sh $VERSION

duration=$SECONDS
hours=$(($duration / (60*60)))
mins=$(($duration % (60*60) / 60))
secs=$(($duration % 60))

time=$(date +"%F %T")
echo "$time> All indexing DONE"
printf "%02d:%02d:%02d elapsed.\n" $hours $mins $secs
