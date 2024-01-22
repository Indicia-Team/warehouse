ARG ELASTIC_VERSION
FROM docker.elastic.co/elasticsearch/elasticsearch:${ELASTIC_VERSION}

# Creating Certificate Authority.
RUN mkdir -p config/certs && \
  ./bin/elasticsearch-certutil ca \
    --out config/certs/ca.p12 \
    --pass password \
    --silent

# Convert to pem format for clients to copy.
RUN openssl pkcs12 \
  -in config/certs/ca.p12 \
  -out config/certs/ca.crt \
  -passin pass:password \
  -nokeys

# Creating certificates.
# Transport
RUN ./bin/elasticsearch-certutil cert \
  --ca config/certs/ca.p12 \
  --ca-pass password \
  --out config/certs/transport.p12 \
  --pass password \
  --silent
# Http
RUN ./bin/elasticsearch-certutil cert \
  --ca config/certs/ca.p12 \
  --ca-pass password \
  --name indicia-elastic-1 \
  --dns indicia-elastic-1,localhost \
  --out config/certs/http.p12 \
  --pass password \
  --silent
# Kibana
RUN ./bin/elasticsearch-certutil cert \
  --ca config/certs/ca.p12 \
  --ca-pass password \
  --name indicia-kibana-1 \
  --dns indicia-kibana-1,localhost \
  --pem \
  --out config/certs/kibana.zip \
  --silent && \
  unzip config/certs/kibana.zip -d config/certs && \
  rm config/certs/kibana.zip

# Storing passwords.
# CA
RUN echo password | ./bin/elasticsearch-keystore add -f \
  --stdin xpack.security.transport.ssl.truststore.secure_password
# Transport
RUN echo password | ./bin/elasticsearch-keystore add -f \
  --stdin xpack.security.transport.ssl.keystore.secure_password
# Http
RUN echo password | ./bin/elasticsearch-keystore add -f \
  --stdin xpack.security.http.ssl.keystore.secure_password

# The Elasticsearch entrypoint will run a wrapper script for us.
COPY wrapper.sh .
CMD ./wrapper.sh