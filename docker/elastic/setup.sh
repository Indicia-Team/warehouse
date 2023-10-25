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

  echo "Setting kibana_system password."
  # (But, you will log in to the Kibana UI as 'elastic')
  curl localhost:9200/_security/user/kibana_system/_password \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data '{"password":"password"}' \
    --output outputfile

  echo "Setting logstash_system password."
  curl localhost:9200/_security/user/logstash_system/_password \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data '{"password":"password"}' \
    --output outputfile

  echo "Creating logstash_writer role."
  curl localhost:9200/_security/role/logstash_writer \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/role_logstash_writer.json \
    --output outputfile

  echo "Creating logstash_pipeline user."
  curl localhost:9200/_security/user/logstash_pipeline \
    --silent \
    --request POST \
    --user elastic:password \
    --header 'Content-Type: application/json' \
    --data @elastic/setup/user_logstash_pipeline.json \
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

  echo "ElasticSearch set up complete."
else
  echo "ElasticSearch already set up (response code $response)."
fi 

  
