COMMENT ON COLUMN occurrence_images.path IS 'Path to the image file, either relative to the server''s image storage folder or an absolute path if the image is held externally.';

ALTER TABLE occurrence_images ADD COLUMN external_details character varying(200);

COMMENT ON COLUMN occurrence_images.external_details IS 'If the image is held externally, this field is used to hold a JSON format object defining the link to the image on the external image server. This provides all the details required to lookup the full image information.';
