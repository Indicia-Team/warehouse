ARG ELASTIC_VERSION
FROM docker.elastic.co/logstash/logstash:${ELASTIC_VERSION}

# Install git
USER root
RUN apt-get update \
  && apt-get -y install git \
  && apt-get clean
USER logstash

# WITH SECURITY
# Getting version.json should ensure git clone will always run if the version
# changes. See https://stackoverflow.com/a/39278224
#ADD https://api.github.com/repos/Indicia-Team/support_files/git/refs/heads/elastic-secure version.json
# Clone the Elasticsearch folder of the support files repo to
# /usr/share/logstash/support_files
# Checkout the elastic-secure branch which includes extra config for
# Elasticsearch with security enabled.
#RUN git clone -n --branch elastic-secure --depth=1 --filter=tree:0 \
#    https://github.com/Indicia-Team/support_files.git \
#  && cd support_files \
#  && git sparse-checkout set Elasticsearch \
#  && git checkout \
#  && cd ..

# WITHOUT SECURITY
# Getting version.json should ensure git clone will always run if the version
# changes. See https://stackoverflow.com/a/39278224
ADD https://api.github.com/repos/Indicia-Team/support_files/git/refs/heads/master version.json
# Clone the Elasticsearch folder of the support files repo to
# /usr/share/logstash/support_files
# Checkout the master branch which does not support elasticsearch security.
RUN git clone -n --branch master --depth=1 --filter=tree:0 \
    https://github.com/Indicia-Team/support_files.git \
  && cd support_files \
  && git sparse-checkout set Elasticsearch \
  && git checkout \
  && cd ..

# Create configuration files from templates.
ARG WAREHOUSE_URL
ARG WAREHOUSE_API_USER
ARG WAREHOUSE_API_SECRET
ARG WAREHOUSE_API_PROJECT_OCC
ARG WAREHOUSE_API_PROJECT_OCC_DEL
ARG WAREHOUSE_API_PROJECT_SMP
ARG WAREHOUSE_API_PROJECT_SMP_DEL
ARG ELASTIC_URL
ARG WAREHOUSE_UNIQUE_NAME
ARG LOGSTASH_USER
ARG LOGSTASH_PASSWORD

ENV SUPPORT_FILES_PATH='/usr/share/logstash/support_files/Elasticsearch'

# Create occurrences-http-indicia.conf from its template.
RUN file=${SUPPORT_FILES_PATH}/logstash-config/occurrences-http-indicia \
  && cp $file.template $file.conf \
  && sed -i -e "s|{{ Warehouse URL }}|${WAREHOUSE_URL}|" \
    -e "s|{{ User }}|${WAREHOUSE_API_USER}|" \
    -e "s|{{ Secret }}|${WAREHOUSE_API_SECRET}|" \
    -e "s|{{ Project ID }}|${WAREHOUSE_API_PROJECT_OCC}|" \
    -e "s|{{ Working folder path }}|${SUPPORT_FILES_PATH}|" \
    -e "s|{{ Elasticsearch address }}|${ELASTIC_URL}|" \
    -e "s|{{ Indicia warehouse unique name }}|${WAREHOUSE_UNIQUE_NAME}|" \
    -e "s|{{ Logstash user }}|${LOGSTASH_USER}|" \
    -e "s|{{ Logstash password }}|${LOGSTASH_PASSWORD}|" \
    $file.conf

# Create occurrences-http-indicia-deletions.conf from its template.
RUN file=${SUPPORT_FILES_PATH}/logstash-config/occurrences-http-indicia-deletions \
  && cp $file.template $file.conf \
  && sed -i -e "s|{{ Warehouse URL }}|${WAREHOUSE_URL}|" \
    -e "s|{{ User }}|${WAREHOUSE_API_USER}|" \
    -e "s|{{ Secret }}|${WAREHOUSE_API_SECRET}|" \
    -e "s|{{ Project ID }}|${WAREHOUSE_API_PROJECT_OCC_DEL}|" \
    -e "s|{{ Working folder path }}|${SUPPORT_FILES_PATH}|" \
    -e "s|{{ Elasticsearch address }}|${ELASTIC_URL}|" \
    -e "s|{{ Indicia warehouse unique name }}|${WAREHOUSE_UNIQUE_NAME}|" \
    -e "s|{{ Logstash user }}|${LOGSTASH_USER}|" \
    -e "s|{{ Logstash password }}|${LOGSTASH_PASSWORD}|" \
    $file.conf

# Create samples-http-indicia.conf from its template.
RUN file=${SUPPORT_FILES_PATH}/logstash-config/samples-http-indicia \
  && cp $file.template $file.conf \
  && sed -i -e "s|{{ Warehouse URL }}|${WAREHOUSE_URL}|" \
    -e "s|{{ User }}|${WAREHOUSE_API_USER}|" \
    -e "s|{{ Secret }}|${WAREHOUSE_API_SECRET}|" \
    -e "s|{{ Project ID }}|${WAREHOUSE_API_PROJECT_SMP}|" \
    -e "s|{{ Working folder path }}|${SUPPORT_FILES_PATH}|" \
    -e "s|{{ Elasticsearch address }}|${ELASTIC_URL}|" \
    -e "s|{{ Indicia warehouse unique name }}|${WAREHOUSE_UNIQUE_NAME}|" \
    -e "s|{{ Logstash user }}|${LOGSTASH_USER}|" \
    -e "s|{{ Logstash password }}|${LOGSTASH_PASSWORD}|" \
    $file.conf

# Create samples-http-indicia-deletions.conf from its template.
RUN file=${SUPPORT_FILES_PATH}/logstash-config/samples-http-indicia-deletions \
  && cp $file.template $file.conf \
  && sed -i -e "s|{{ Warehouse URL }}|${WAREHOUSE_URL}|" \
    -e "s|{{ User }}|${WAREHOUSE_API_USER}|" \
    -e "s|{{ Secret }}|${WAREHOUSE_API_SECRET}|" \
    -e "s|{{ Project ID }}|${WAREHOUSE_API_PROJECT_SMP_DEL}|" \
    -e "s|{{ Working folder path }}|${SUPPORT_FILES_PATH}|" \
    -e "s|{{ Elasticsearch address }}|${ELASTIC_URL}|" \
    -e "s|{{ Indicia warehouse unique name }}|${WAREHOUSE_UNIQUE_NAME}|" \
    -e "s|{{ Logstash user }}|${LOGSTASH_USER}|" \
    -e "s|{{ Logstash password }}|${LOGSTASH_PASSWORD}|" \
    $file.conf

# Create pipelines.yml from 
COPY setup/pipelines.yml /usr/share/logstash/config

RUN sed -i -e "s|{{ Working folder path }}|${SUPPORT_FILES_PATH}|" \
    /usr/share/logstash/config/pipelines.yml
