CREATE TABLE taxon_images
(
  id serial NOT NULL,
  taxon_meaning_id integer NOT NULL, -- Foreign key to the taxon_meanings table. Identifies the taxon meaning that the image is attached to.
  path character varying(200) NOT NULL, -- Path to the image file, either relative to the server's image storage folder or an absolute path if the image is held externally.
  caption character varying(100), -- Caption for the image.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  external_details character varying(200), -- If the image is held externally, this field is used to hold a JSON format object defining the link to the image on the external image server. This provides all the details required to lookup the full image information.
  CONSTRAINT pk_taxon_images PRIMARY KEY (id),
  CONSTRAINT fk_taxon_image_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_image_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE taxon_images IS 'Lists images that are attached to taxon_meaning records.';
COMMENT ON COLUMN taxon_images.taxon_meaning_id IS 'Foreign key to the taxon_meanings table. Identifies the taxon_meaning that the image is attached to.';
COMMENT ON COLUMN taxon_images.path IS 'Path to the image file, either relative to the server''s image storage folder or an absolute path if the image is held externally.';
COMMENT ON COLUMN taxon_images.caption IS 'Caption for the image.';
COMMENT ON COLUMN taxon_images.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_images.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_images.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_images.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_images.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN taxon_images.external_details IS 'If the image is held externally, this field is used to hold a JSON format object defining the link to the image on the external image server. This provides all the details required to lookup the full image information.';

-- Migrate existing data into new table.
INSERT INTO taxon_images (taxon_meaning_id, path, created_on, created_by_id, updated_on, updated_by_id) 
  SELECT taxon_meaning_id, image_path, created_on, created_by_id, updated_on, updated_by_id 
  FROM taxa_taxon_lists
  WHERE image_path IS NOT NULL;

INSERT INTO taxon_images (taxon_meaning_id, path, created_on, created_by_id, updated_on, updated_by_id) 
  SELECT ttl.taxon_meaning_id, t.image_path, t.created_on, t.created_by_id, t.updated_on, t.updated_by_id 
  FROM taxa_taxon_lists ttl
  JOIN taxa t ON t.id=ttl.taxon_id
  WHERE t.image_path IS NOT NULL;

-- Remove old columns
ALTER TABLE taxa_taxon_lists DROP COLUMN image_path CASCADE;
ALTER TABLE taxa DROP COLUMN image_path CASCADE;