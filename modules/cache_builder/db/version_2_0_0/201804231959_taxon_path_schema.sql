-- Table: cache_taxon_paths

-- DROP TABLE cache_taxon_paths;

CREATE TABLE cache_taxon_paths
(
  taxon_meaning_id integer NOT NULL, -- Taxon meaning ID of this taxon as found on the master list.
  taxon_list_id integer NOT NULL, -- Taxon list this taxon belongs to.
  external_key character varying, -- External key of the preferred name with this path. Allows easy cross reference with other lists that share the same preferred name.
  path integer[], -- Array of taxon_meaning_ids for the taxon's materialised path. This is a list of pointers to the ancestors of this taxa from the top of the taxonomic tree downwards.
  CONSTRAINT pk_cache_taxon_paths PRIMARY KEY (taxon_meaning_id, taxon_list_id),
  CONSTRAINT fk_taxon_meaning_id FOREIGN KEY (taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_list_id FOREIGN KEY (taxon_list_id)
      REFERENCES taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE cache_taxon_paths
  IS 'Materialised paths for all taxon meanings on the master taxon list.';
COMMENT ON COLUMN cache_taxon_paths.taxon_meaning_id IS 'Taxon meaning ID of this taxon as found on the master list.';
COMMENT ON COLUMN cache_taxon_paths.taxon_list_id IS 'Taxon list this taxon belongs to.';
COMMENT ON COLUMN cache_taxon_paths.external_key IS 'External key of the preferred name with this path. Allows easy cross reference with other lists that share the same preferred name.';
COMMENT ON COLUMN cache_taxon_paths.path IS 'Array of taxon_meaning_ids for the taxon''s materialised path. This is a list of pointers to the ancestors of this taxa from the top of the taxonomic tree downwards.';


-- Index: ix_taxon_path_external_key

-- DROP INDEX ix_taxon_path_external_key;

CREATE INDEX ix_taxon_path_external_key
  ON cache_taxon_paths
  USING btree
  (external_key COLLATE pg_catalog."default");

-- Index: ix_taxon_path_taxon_list_id

-- DROP INDEX ix_taxon_path_taxon_list_id;

CREATE INDEX ix_taxon_path_taxon_list_id
  ON cache_taxon_paths
  USING btree
  (taxon_list_id);

-- Index: ix_taxon_path_path

-- DROP INDEX ix_taxon_path_path;

CREATE INDEX ix_taxon_path_path
  ON cache_taxon_paths
  USING gin
  (path);