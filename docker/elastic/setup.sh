#!/bin/sh

echo
echo "Starting ES setup"

# Obtain CA certificate
docker cp indicia-elastic-1:/usr/share/elasticsearch/config/certs/ca.crt .

# Test to see if index already set up.
response=$(curl \
  --silent \
  --cacert ca.crt \
  --user elastic:password \
  --output outputfile \
  --write-out %{response_code} \
  https://localhost:9200/occurrence_brc1_v1/_settings)

if [ $response = 404 ]; then
  echo "Setting up ElasticSearch"

  # ELASTIC SECURITY
  echo "Setting kibana_system password."
  # (But, you will log in to the Kibana UI as 'elastic')
  curl https://localhost:9200/_security/user/kibana_system/_password \
    --silent \
    --request POST \
    --cacert ca.crt \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data '{"password":"password"}' \
    --output outputfile

  echo "Creating logstash_writer role."
  curl https://localhost:9200/_security/role/logstash_writer \
    --silent \
    --request POST \
    --cacert ca.crt \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/role_logstash_writer.json \
    --output outputfile

  echo "Creating ${LOGSTASH_USER} user."
  curl https://localhost:9200/_security/user/${LOGSTASH_USER} \
    --silent \
    --request POST \
    --cacert ca.crt \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/user_indicia_pipeline.json \
    --output outputfile

  echo "Creating elasticsearch_reader role."
  curl https://localhost:9200/_security/role/elasticsearch_reader \
    --silent \
    --request POST \
    --cacert ca.crt \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/role_elasticsearch_reader.json \
    --output outputfile

  echo "Creating ${PROXY_USER} user."
  curl https://localhost:9200/_security/user/${PROXY_USER} \
    --silent \
    --request POST \
    --cacert ca.crt \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/user_indicia_proxy.json \
    --output outputfile

  echo "Creating occurrence index."
  curl https://localhost:9200/occurrence_brc1_v1 \
    --silent \
    --request PUT \
    --cacert ca.crt \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/occurrence_brc1_v1.json \
    --output outputfile

  echo "Creating sample index."
  curl https://localhost:9200/sample_brc1_v1 \
    --silent \
    --request PUT \
    --cacert ca.crt \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/sample_brc1_v1.json \
    --output outputfile

  #Clean up
  rm ca.crt

  echo "ElasticSearch set up complete."
else
  echo "ElasticSearch already set up (response code $response)."
fi 
