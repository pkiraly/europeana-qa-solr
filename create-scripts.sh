#!/usr/bin/env bash

VERSION=v2019-03
SOLR_CORE=$(echo $VERSION | sed 's/v/qa-/')

for TYPE in completeness multilingual-saturation languages
do
  echo $TYPE
  INPUT_FILES=/projects/pkiraly/data-export/$VERSION/parts-$TYPE/part*
  BASH_SCRIPT=$VERSION-$TYPE.sh
  if [[ -e $BASH_SCRIPT ]]; then
    rm $BASH_SCRIPT
  fi

  for FILE in $INPUT_FILES
  do
    echo "php incremental-index-$TYPE.php --port 8984 --collection $SOLR_CORE --with-check --file $FILE" >> $BASH_SCRIPT
  done

  chmod +x $BASH_SCRIPT
  echo $BASH_SCRIPT >> $VERSION-index-all.sh
done

chmod +x $VERSION-index-all.sh

echo DONE
