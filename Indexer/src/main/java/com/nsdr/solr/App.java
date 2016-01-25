package com.nsdr.solr;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.net.URL;
import java.util.HashMap;
import java.util.Map;
import java.util.Map.Entry;
import java.util.concurrent.TimeUnit;

import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.common.SolrInputDocument;

import com.jayway.jsonpath.Configuration;
import com.jayway.jsonpath.JsonPath;
import com.jayway.jsonpath.PathNotFoundException;
import com.jayway.jsonpath.spi.json.JsonProvider;

/**
 * Indexer
 */
public class App {

	private static final String PATH_TPL = "http://localhost:50075/webhdfs/v1/europeana/%s?op=OPEN&namenoderpcaddress=localhost:54310&offset=0";
	private static final String SOLR_URL = "http://localhost:8983/solr/europeana";
	private static final JsonProvider jsonProvider = Configuration.defaultConfiguration().jsonProvider();

	private static final Map<String, String> fieldMap = new HashMap<>();
	static {
		fieldMap.put("id", "$.identifier");
		fieldMap.put("dataProvider_s",
				"$.['ore:Aggregation'][0]['edm:dataProvider'][0]");
		fieldMap.put("provider_s",
				"$.['ore:Aggregation'][0]['edm:provider'][0]");
		fieldMap.put("type_s", "$.['ore:Proxy'][0]['edm:type'][0]");
		fieldMap.put("collection_s",
				"$.['edm:EuropeanaAggregation'][0]['edm:collectionName'][0]");
		fieldMap.put("language_s",
				"$.['edm:EuropeanaAggregation'][0]['edm:language'][0]");
		fieldMap.put("country_s",
				"$.['edm:EuropeanaAggregation'][0]['edm:country'][0]");
	}

	public static void main(String[] args) throws IOException, SolrServerException {

		if (args.length < 1) {
			System.err.println("Please provide a full path to the output file");
			System.exit(0);
		}

		HttpSolrClient server = new HttpSolrClient(SOLR_URL);
		URL path = new URL(String.format(PATH_TPL, args[0]));
		BufferedReader br = new BufferedReader(new InputStreamReader(path.openStream()));

		String strLine;
		int i = 0;
		long start = System.currentTimeMillis();
		while ((strLine = br.readLine()) != null) {
			i++;
			Object jsonDoc = jsonProvider.parse(strLine);

			SolrInputDocument solrDoc = new SolrInputDocument();
			for (Entry<String, String> entry : fieldMap.entrySet()) {
				try {
					Object value = JsonPath.read(jsonDoc, entry.getValue());
					if (value != null) {
						solrDoc.addField(entry.getKey(), value);
					}
				} catch (PathNotFoundException e) {
					System.err.println("PathNotFoundException: " + e.getLocalizedMessage());
				}
			}
			solrDoc.addField("json_st", strLine);
			server.add(solrDoc);

			if (i % 100 == 0) {
				System.out.println(String.format("So far indexed %d records. Took %s", i, formatDuration(start)));
				server.commit();
			}
		}
		System.out.println(String.format("Indexed %d records. Took %s", i, formatDuration(start)));
		br.close();

		server.commit();
		server.close();
	}

	private static String formatDuration(long start) {
		long millis = System.currentTimeMillis() - start;
		String duration = String.format("%d min, %d sec",
				TimeUnit.MILLISECONDS.toMinutes(millis),
				TimeUnit.MILLISECONDS.toSeconds(millis)
						- TimeUnit.MINUTES.toSeconds(TimeUnit.MILLISECONDS.toMinutes(millis)));
		return duration;
	}

}
