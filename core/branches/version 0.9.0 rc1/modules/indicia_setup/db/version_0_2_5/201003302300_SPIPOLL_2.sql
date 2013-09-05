DROP VIEW IF EXISTS list_determinations;
DROP TABLE IF EXISTS determinations;
DROP SEQUENCE IF EXISTS determinations_id_seq;

CREATE SEQUENCE determinations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE determinations
(
  id integer DEFAULT nextval('determinations_id_seq'::regclass) NOT NULL,
  occurrence_id integer NOT NULL,
  email_address character varying(50),
  person_name character varying,
  cms_ref integer, 
  taxa_taxon_list_id integer, -- Foreign key to the taxa_taxon_lists table. Identifies the taxa on a taxon list that this occurrence is a record of.
  taxon_text_description text,
  taxon_extra_info text,
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  created_by_id integer, -- Foreign key to the users table (creator), if user was logged in when comment created.
  created_on timestamp without time zone NOT NULL, -- Date and time this comment was created.
  updated_by_id integer, -- Foreign key to the users table (updater), if user was logged in when comment updated.
  updated_on timestamp without time zone NOT NULL, -- Date and time this comment was updated.
  
  CONSTRAINT pk_determinations PRIMARY KEY (id),
  CONSTRAINT fk_determination_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_determination_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_determination_occurrence FOREIGN KEY (occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_determination_taxa_taxon_list FOREIGN KEY (taxa_taxon_list_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE determinations IS 'List of comments regarding the occurrence posted by users viewing the occurrence subsequent to initial data entry.';
COMMENT ON COLUMN determinations.occurrence_id IS 'Foreign key to the occurrences table. Identifies the determined occurrence.';
COMMENT ON COLUMN determinations.email_address IS 'Email of user who created the determination.';
COMMENT ON COLUMN determinations.person_name IS 'Identifier for determiner.';
COMMENT ON COLUMN determinations.cms_ref IS 'CMS Identifier for determiner.';
COMMENT ON COLUMN determinations.taxa_taxon_list_id IS 'Foreign key to the taxa_taxon_lists table. Identifies the taxa on a taxon list that this detemination is a record of.';
COMMENT ON COLUMN determinations.taxon_text_description IS 'Text description of Taxon if not in list, or if a list.';
COMMENT ON COLUMN determinations.taxon_extra_info IS 'Additional information that may provide more accurate determination.';
COMMENT ON COLUMN determinations.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN determinations.created_by_id IS 'Foreign key to the users table (creator), if user was logged in when comment created.';
COMMENT ON COLUMN determinations.created_on IS 'Date and time this comment was created.';
COMMENT ON COLUMN determinations.updated_by_id IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';
COMMENT ON COLUMN determinations.updated_on IS 'Date and time this comment was updated.';

CREATE OR REPLACE VIEW list_determinations AS 
 SELECT d.id, d.taxa_taxon_list_id, t.taxon, d.taxon_text_description, d.taxon_extra_info, d.occurrence_id,
 		d.email_address, d.person_name, d.cms_ref, d.deleted, d.updated_on, o.website_id
   FROM determinations d
   JOIN occurrences o ON d.occurrence_id = o.id
   LEFT JOIN taxa_taxon_lists ttl ON d.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   ;
