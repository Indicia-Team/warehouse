CREATE TABLE licenses
(
  id serial NOT NULL, -- Primary key and unique identifier for the table
  title character varying NOT NULL, -- Title for the license
  code character varying NOT NULL, -- Code for the license, e.g. CC-BY
  description character varying, -- Description of the license
  url character varying, -- URL to the full license definition
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_licenses PRIMARY KEY (id),
  CONSTRAINT fk_license_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_license_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT chk_license_title_unique UNIQUE (title), -- Licence title should be unique
  CONSTRAINT chk_license_code_unique UNIQUE (code) -- Licence code should be unique
)
WITH (
  OIDS=FALSE
);
ALTER TABLE licenses
  OWNER TO indicia_user;
COMMENT ON TABLE licenses
  IS 'Data licenses available for linking to records';
COMMENT ON COLUMN licenses.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN licenses.title IS 'Title for the license';
COMMENT ON COLUMN licenses.code IS 'Abbreviation/code for the license, e.g. CC-BY';
COMMENT ON COLUMN licenses.description IS 'Description of the license';
COMMENT ON COLUMN licenses.url IS 'URL to the full license definition';
COMMENT ON COLUMN licenses.created_on IS 'Date this record was created.';
COMMENT ON COLUMN licenses.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN licenses.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN licenses.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN licenses.deleted IS 'Has this record been deleted?';

COMMENT ON CONSTRAINT chk_license_title_unique ON licenses IS 'Licence title should be unique.';
COMMENT ON CONSTRAINT chk_license_code_unique ON licenses IS 'Licence code should be unique.';


CREATE TABLE licenses_websites
(
  id serial NOT NULL,
  license_id integer NOT NULL, -- Foreign key to the licenses table. Identifies the filter that is available to the website.
  website_id integer NOT NULL, -- Foreign key to the websites table. Identifies the website that the license is available to.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_licenses_websites PRIMARY KEY (id),
  CONSTRAINT fk_licenses_website_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_licenses_websites_licenses FOREIGN KEY (license_id)
      REFERENCES licenses (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_licenses_websites_websites FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE licenses_websites
  OWNER TO indicia_user;
COMMENT ON TABLE licenses_websites
  IS 'Join table which identifies the licenses that are available to each website.';
COMMENT ON COLUMN licenses_websites.license_id IS 'Foreign key to the licenses table. Identifies the filter that is available to the website.';
COMMENT ON COLUMN licenses_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the filter is available to.';
COMMENT ON COLUMN licenses_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN licenses_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN licenses_websites.deleted IS 'Has this record been deleted?';
