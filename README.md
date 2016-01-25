# europeana-qa-solr
Solr indexer for Metadata Quality Assurance Framework

## usage

### Compile:

    cd Indexer
    mvn clean install

### Start Solr

    cd /to/solr
    bin/solr start
    bin/solr create -c europeana

### Index one file

    cp run-sample.cfg run.cfg

edit `run.cfg` to add your local Maven repository (usually `~/.m2/repository`)

    ./run.sh [JSON file]

where JSON file is a file existing in the Hadoop Distributed File System (HDFS) `/europeana` directory.
