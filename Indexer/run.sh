. ./run.cfg

CP=$REPO/org/apache/solr/solr-solrj/5.4.1/solr-solrj-5.4.1.jar
CP=$CP:$REPO/commons-io/commons-io/2.4/commons-io-2.4.jar
CP=$CP:$REPO/org/apache/httpcomponents/httpclient/4.4.1/httpclient-4.4.1.jar
CP=$CP:$REPO/org/apache/httpcomponents/httpcore/4.4.1/httpcore-4.4.1.jar
CP=$CP:$REPO/org/apache/httpcomponents/httpmime/4.4.1/httpmime-4.4.1.jar
CP=$CP:$REPO/org/noggit/noggit/0.6/noggit-0.6.jar
CP=$CP:$REPO/org/slf4j/jcl-over-slf4j/1.7.7/jcl-over-slf4j-1.7.7.jar
CP=$CP:$REPO/org/slf4j/slf4j-api/1.7.7/slf4j-api-1.7.7.jar
CP=$CP:$REPO/com/jayway/jsonpath/json-path/2.1.0/json-path-2.1.0.jar
CP=$CP:$REPO/net/minidev/json-smart/2.2/json-smart-2.2.jar

java -classpath $CP:target/Indexer-1.0-SNAPSHOT.jar com.nsdr.solr.App full.json