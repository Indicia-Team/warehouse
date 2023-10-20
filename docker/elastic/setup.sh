#!/bin/sh

# Set 'kibana_system' password (to 'password').
# (But, you will log in to the Kibana UI as 'elastic')
curl localhost:9200/_security/user/kibana_system/_password \
  --silent \
  --request POST \
  --user elastic:password \
  --header "Content-Type: application/json" \
  --data "{\"password\":\"password\"}" \
  --output outputfile
  
