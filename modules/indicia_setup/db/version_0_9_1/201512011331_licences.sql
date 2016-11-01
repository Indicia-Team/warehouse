CREATE TABLE licences
(
  id serial NOT NULL, -- Primary key and unique identifier for the table
  title character varying NOT NULL, -- Title for the licence
  code character varying NOT NULL, -- Code for the licence, e.g. CC-BY
  description character varying, -- Description of the licence
  url_readable character varying, -- URL to the human readable licence definition
  url_legal character varying, -- URL to the full legal licence definition
  version character varying(10), -- Version number of the licence
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_licences PRIMARY KEY (id),
  CONSTRAINT fk_licence_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_licence_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

CREATE UNIQUE INDEX idx_licences_title_unique
  ON licences
  USING btree
  (title, version)
  WHERE deleted = false;

CREATE UNIQUE INDEX idx_licences_code_unique
  ON licences
  USING btree
  (code, version)
  WHERE deleted = false;

COMMENT ON TABLE licences
  IS 'Data licences available for linking to records';
COMMENT ON COLUMN licences.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN licences.title IS 'Title for the licence';
COMMENT ON COLUMN licences.code IS 'Abbreviation/code for the licence, e.g. CC-BY';
COMMENT ON COLUMN licences.description IS 'Description of the licence';
COMMENT ON COLUMN licences.url_readable IS 'URL to the human readable licence definition';
COMMENT ON COLUMN licences.url_legal IS 'URL to the full legal licence definition';
COMMENT ON COLUMN licences.version IS 'Version number of the licence';
COMMENT ON COLUMN licences.created_on IS 'Date this record was created.';
COMMENT ON COLUMN licences.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN licences.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN licences.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN licences.deleted IS 'Has this record been deleted?';

CREATE TABLE licences_websites
(
  id serial NOT NULL,
  licence_id integer NOT NULL, -- Foreign key to the licences table. Identifies the filter that is available to the website.
  website_id integer NOT NULL, -- Foreign key to the websites table. Identifies the website that the licence is available to.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_licences_websites PRIMARY KEY (id),
  CONSTRAINT fk_licences_website_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_licences_websites_licences FOREIGN KEY (licence_id)
      REFERENCES licences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_licences_websites_websites FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE licences_websites
  IS 'Join table which identifies the licences that are available to each website.';
COMMENT ON COLUMN licences_websites.licence_id IS 'Foreign key to the licences table. Identifies the licence that is available to the website.';
COMMENT ON COLUMN licences_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the filter is available to.';
COMMENT ON COLUMN licences_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN licences_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN licences_websites.deleted IS 'Has this record been deleted?';