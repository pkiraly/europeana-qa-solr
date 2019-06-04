#!/usr/bin/env bash

VERSION=v2019-03
INPUT_DIR=/home/pkiraly/git/europeana-qa-spark

for TYPE in completeness multilingual-saturation languages
do
  echo $TYPE
  TARGET_DIR=/projects/pkiraly/data-export/$VERSION/parts-$TYPE
  if [[ ! -d $TARGET_DIR ]]; then
    mkdir $TARGET_DIR
  fi

  split -l 1000000 -d --additional-suffix .csv $INPUT_DIR/$VERSION-$TYPE.csv $TARGET_DIR/part
done

echo DONE
