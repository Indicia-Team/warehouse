CREATE TABLE rest_api_clients
(
    id serial NOT NULL,
    title character varying NOT NULL,
    description text,
    website_id integer NOT NULL,
    username character varying NOT NULL,
    secret character varying,
    public_key text,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL,
    CONSTRAINT pk_rest_api_clients PRIMARY KEY (id ),
    CONSTRAINT fk_rest_api_clients_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_rest_api_clients_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_rest_api_clients_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE rest_api_clients
    IS 'List of additional REST API clients, e.g. applications which connect to the REST API for access to PostgreSQL or Elasticsearch data.
      These are in addition to direct connections using authentication in the websites table.';
COMMENT ON COLUMN rest_api_clients.id IS 'Primary key for the table.';
COMMENT ON COLUMN rest_api_clients.title IS 'Name given to the client, for admin purposes only.';
COMMENT ON COLUMN rest_api_clients.description IS 'Description of the client, for admin purposes only.';
COMMENT ON COLUMN rest_api_clients.website_id IS 'ID of the website the client is authenticated for access to.';
COMMENT ON COLUMN rest_api_clients.username IS 'Username provided when authenticating as this client.';
COMMENT ON COLUMN rest_api_clients.secret IS 'Encrypted secret provided when using directClient or hmacClient authentication modes.';
COMMENT ON COLUMN rest_api_clients.public_key IS 'Secret provided when using jwtClient authentication mode.';
COMMENT ON COLUMN rest_api_clients.created_on IS 'Date this record was created.';
COMMENT ON COLUMN rest_api_clients.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN rest_api_clients.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN rest_api_clients.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN rest_api_clients.deleted IS 'Has this record been deleted?';

CREATE TABLE rest_api_client_connections
(
    id serial NOT NULL,
    title character varying NOT NULL,
    description text,
    rest_api_client_id integer NOT NULL,
    sharing character,
    es_endpoint character varying,
    es_bool_query json,
    es_sensitivity_blur character NOT NULL DEFAULT 'B',
    allow_reports boolean,
    limit_to_reports character varying[],
    allow_data_resources boolean,
    limit_to_data_resources character varying[],
    read_only boolean NOT NULL,
    allow_confidential boolean DEFAULT false,
    allow_sensitive boolean DEFAULT true,
    allow_unreleased boolean DEFAULT false,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL,
    CONSTRAINT pk_rest_api_client_connections PRIMARY KEY (id ),
    CONSTRAINT fk_rest_api_client_connections_clients FOREIGN KEY (rest_api_client_id)
      REFERENCES rest_api_clients (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
   CONSTRAINT chk_rest_api_client_connection_sharing_options CHECK ((sharing = ANY (ARRAY['R'::bpchar, 'P'::bpchar, 'V'::bpchar, 'D'::bpchar, 'M'::bpchar])))
);

COMMENT ON TABLE rest_api_client_connections
    IS 'Configugration for a single connection method to the REST API by a client. A client may be allowed to use multiple connections for different purposes.
      Includes privileges and filtering to define the capabilities when using this connection.';
COMMENT ON COLUMN rest_api_client_connections.id IS 'Primary key for the table.';
COMMENT ON COLUMN rest_api_client_connections.title IS 'Title of the REST API connection, for admin purposes only.';
COMMENT ON COLUMN rest_api_client_connections.description IS 'Description of the REST API connection, for admin purposes only.';
COMMENT ON COLUMN rest_api_client_connections.rest_api_client_id IS 'Foreign key to the rest_api_clients table, defines the client that can use this connection.';
COMMENT ON COLUMN rest_api_client_connections.sharing IS 'Identifies the record sharing task that this connection uses when reading records. Defines the list of websites which records can be read from.
  Options are R(eporting), P(eer review), V(erification), D(ata flow), M(oderation).';
COMMENT ON COLUMN rest_api_client_connections.es_endpoint IS 'If Elasticsearch enabled for this connect, then specifies the REST API Elasticsearch endpoint, which must be defined in the REST API''s config file.
  Can point to an alias which enforces filtering, or the entire index which relies on filtering defined for the connection in this table.';
COMMENT ON COLUMN rest_api_client_connections.es_bool_query IS 'JSON object containing filtering which will be added to all requests for data from Elasticsearch.
  The top level property names must match those available for an Elasticsearch boolean query, e.g. must, must_not or should.';
COMMENT ON COLUMN rest_api_client_connections.es_sensitivity_blur IS 'When Elasticsearch data are requested, return the full-precision (F) or blurred (B) copy of the record.';
COMMENT ON COLUMN rest_api_client_connections.allow_reports IS 'Set to true if access to reports via the REST API is allowed.';
COMMENT ON COLUMN rest_api_client_connections.limit_to_reports IS 'If allow_reports=true and limit_to_reports is empty, then unlimited access to reports is allowed.
  If this contains a list of report paths then only listed reports are allowed.';
COMMENT ON COLUMN rest_api_client_connections.allow_data_resources IS 'Set to true if access to data resource endpoints via the REST API is allowed.';
COMMENT ON COLUMN rest_api_client_connections.limit_to_data_resources IS 'If allow_data=true and limit_to_data_resources is empty, then unlimited access to data end-points is allowed.
  If this contains a list of data resources (e.g. occurrences, locations, samples) then only listed resources are allowed.';
COMMENT ON COLUMN rest_api_client_connections.read_only IS 'Set to false to allow data updates via the data resource endpoints.';
COMMENT ON COLUMN rest_api_client_connections.allow_confidential IS 'Set to true to allow confidential records to be included in responses.';
COMMENT ON COLUMN rest_api_client_connections.allow_sensitive IS 'Set to true to allow sensitive records to be included in responses.';
COMMENT ON COLUMN rest_api_client_connections.allow_unreleased IS 'Set to true to allow unreleased records to be included in responses.';
COMMENT ON COLUMN rest_api_client_connections.created_on IS 'Date this record was created.';
COMMENT ON COLUMN rest_api_client_connections.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN rest_api_client_connections.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN rest_api_client_connections.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN rest_api_client_connections.deleted IS 'Has this record been deleted?';