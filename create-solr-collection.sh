#!/usr/bin/env bash

VERSION=$1
SOLR_CORE=$(echo $VERSION)

URL=$(printf "http://localhost:8984/solr/admin/cores?action=STATUS&core=%s" $SOLR_CORE)
echo $URL
CORE_NOT_EXISTS=$(curl -s "$URL" | jq .status | grep "$SOLR_CORE" | grep -c '{}')
echo "core not exists: $CORE_NOT_EXISTS"

if [[ "$CORE_NOT_EXISTS" != "0" ]]; then
  # create core
  echo "Create Solr core '$SOLR_CORE'"
  curl -s "http://localhost:8984/solr/admin/cores?action=CREATE&name=$SOLR_CORE&configSet=_default"
  # ~/solr-8.2.0/bin/solr create -v $VERSION
fi

echo DONE
