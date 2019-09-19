#!/usr/bin/env bash

VERSION=$1
DIR=$2
SOLR_CORE=$(echo $VERSION)
# SOLR_CORE=$(echo $VERSION | sed 's/v/qa-/')

URL=$(printf "http://localhost:8984/solr/admin/cores?action=STATUS&core=%s" $SOLR_CORE)
echo $URL
CORE_NOT_EXISTS=$(curl -s "$URL" | jq .status | grep "$SOLR_CORE" | grep -c '{}')
echo "core not exists: $CORE_NOT_EXISTS"

if [[ "$CORE_NOT_EXISTS" != "0" ]]; then
  # create core
  echo "Create Solr core '$SOLR_CORE'"
  curl -s "http://localhost:8984/solr/admin/cores?action=CREATE&name=$SOLR_CORE"
fi

BASE_DIR=$(dirname $(readlink -nf $BASH_SOURCE))
echo "base: $base"

TOP_SCRIPT=$BASE_DIR/$VERSION-index-all.sh
if [[ -e $TOP_SCRIPT ]]; then
  rm $TOP_SCRIPT
fi

for TYPE in completeness multilingual-saturation languages
do
  echo $TYPE
  INPUT_FILES=$DIR/$VERSION/parts-$TYPE/part*
  BASH_SCRIPT=${BASE_DIR}/$VERSION-$TYPE.sh
  if [[ -e $BASH_SCRIPT ]]; then
    rm $BASH_SCRIPT
  fi

  for FILE in $INPUT_FILES
  do
    echo "echo indexing $TYPE $FILE" >> $BASH_SCRIPT
    echo "php incremental-index-$TYPE.php --port 8984 --collection $SOLR_CORE --with-check --file $FILE" >> $BASH_SCRIPT
    echo "sleep 5" >> $BASH_SCRIPT
  done

  chmod +x $BASH_SCRIPT
  echo $BASH_SCRIPT >> $TOP_SCRIPT
done

echo "echo commit and optimize" >> $TOP_SCRIPT
echo "curl http://localhost:8984/solr/$SOLR_CORE/update -H 'Content-type: text/xml' --data-binary '<commit />'" >> $TOP_SCRIPT
echo "curl http://localhost:8984/solr/$SOLR_CORE/update -H 'Content-type: text/xml' --data-binary '<optimize />'" >> $TOP_SCRIPT

chmod +x $TOP_SCRIPT

echo DONE
