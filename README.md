# europeana-qa-solr
Solr indexer for Metadata Quality Assurance Framework

## usage

### Compile:

    cd Indexer
    mvn clean install

### Install, config and start Apache Solr

Download the latest solr-5.x.x.tgz from http://lucene.apache.org/solr/ (current latest version is solr-5.4.1.tgz) and uncompress it with

    tar xvf solr-5.x.x.tgz

Start Solr and create a new index:

    cd /to/solr
    bin/solr start
    bin/solr create -c europeana
    
Modify server/solr/europeana/conf/managed-schema to add this line:

    <field name="json_st" type="strings" multiValued="false" indexed="false" stored="true" />

Restart Solr:

    bin/solr stop
    bin/solr start

### Index one file

    cp run-sample.cfg run.cfg

edit `run.cfg` to add your local Maven repository (usually `~/.m2/repository`)

    ./run.sh [JSON file]

where JSON file is a file existing in the Hadoop Distributed File System (HDFS) `/europeana` directory.
