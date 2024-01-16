#!/bin/sh

# Test to see if index already set up.
response=$(curl \
  --silent \
  --user elastic:password \
  --output outputfile \
  --write-out %{response_code} \
  localhost:9200/occurrence_brc1_v1/_settings)

if [ $response = 404 ]; then
  echo "Setting up ElasticSearch"

  # ELASTIC SECURITY
  echo "Setting kibana_system password."
  # (But, you will log in to the Kibana UI as 'elastic')
  curl localhost:9200/_security/user/kibana_system/_password \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data '{"password":"password"}' \
    --output outputfile

  # echo "Setting logstash_system password."
  # curl localhost:9200/_security/user/logstash_system/_password \
  #   --silent \
  #   --request POST \
  #   --user elastic:password \
  #   --header 'Content-Type: application/json' \
  #   --data '{"password":"password"}' \
  #   --output outputfile

  echo "Creating logstash_writer role."
  curl localhost:9200/_security/role/logstash_writer \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/role_logstash_writer.json \
    --output outputfile

  echo "Creating ${LOGSTASH_USER} user."
  curl localhost:9200/_security/user/${LOGSTASH_USER} \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/user_indicia_pipeline.json \
    --output outputfile

  echo "Creating elasticsearch_reader role."
  curl localhost:9200/_security/role/elasticsearch_reader \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/role_elasticsearch_reader.json \
    --output outputfile

  echo "Creating ${PROXY_USER} user."
  curl localhost:9200/_security/user/${PROXY_USER} \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/user_indicia_proxy.json \
    --output outputfile

  echo "Creating occurrence index."
  curl localhost:9200/occurrence_brc1_v1 \
    --silent \
    --request PUT \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/occurrence_brc1_v1.json \
    --output outputfile

  echo "Creating sample index."
  curl localhost:9200/sample_brc1_v1 \
    --silent \
    --request PUT \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/sample_brc1_v1.json \
    --output outputfile

  echo "Creating Certificate Authority."
  # Saving to the config folder as that is persisted in a docker volume
  docker exec -w /usr/share/elasticsearch indicia-elastic-1 \
    ./bin/elasticsearch-certutil ca \
    --out ./config/elastic-stack-ca.p12 \
    --pass password \
    --silent

  echo "Creating certificates."
  docker exec -w /usr/share/elasticsearch indicia-elastic-1 \
    ./bin/elasticsearch-certutil cert \
      --ca ./config/elastic-stack-ca.p12 \
      --ca-pass password \
      --out ./config/elastic-certificates.p12 \
      --pass password \
      --silent

  echo "Storing passwords."
  docker exec -w /usr/share/elasticsearch indicia-elastic-1 sh -c \
    "echo password | ./bin/elasticsearch-keystore add -f \
      --stdin xpack.security.transport.ssl.keystore.secure_password"
  docker exec -w /usr/share/elasticsearch indicia-elastic-1 sh -c \
    "echo password | ./bin/elasticsearch-keystore add -f \
      --stdin xpack.security.transport.ssl.truststore.secure_password"

  echo "Restarting Elasticsearch to apply changes to keystore."
  docker restart indicia-elastic-1

  echo "ElasticSearch set up complete."
else
  echo "ElasticSearch already set up (response code $response)."
fi 

  
