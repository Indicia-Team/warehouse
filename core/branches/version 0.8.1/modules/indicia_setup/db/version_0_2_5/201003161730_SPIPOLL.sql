
DROP SEQUENCE IF EXISTS location_images_id_seq;
DROP TABLE IF EXISTS location_images;
CREATE SEQUENCE location_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
CREATE TABLE location_images (
    id integer DEFAULT nextval('location_images_id_seq'::regclass) NOT NULL,
    location_id integer NOT NULL,
    "path" character varying(200) NOT NULL,
    caption character varying(100),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
);
ALTER TABLE ONLY location_images
    ADD CONSTRAINT pk_location_images PRIMARY KEY (id);
ALTER TABLE ONLY location_images
    ADD CONSTRAINT fk_location_image_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
ALTER TABLE ONLY location_images
    ADD CONSTRAINT fk_location_image_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
COMMENT ON TABLE location_images IS 'Lists images that are attached to location records.';
COMMENT ON COLUMN location_images.location_id IS 'Foreign key to the locations table. Identifies the location that the image is attached to.';
COMMENT ON COLUMN location_images."path" IS 'Path to the image file, relative to the server''s image storage folder.';
COMMENT ON COLUMN location_images.caption IS 'Caption for the image.';
COMMENT ON COLUMN location_images.created_on IS 'Date this record was created.';
COMMENT ON COLUMN location_images.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN location_images.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN location_images.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN location_images.deleted IS 'Has this record been deleted?';


DROP VIEW IF EXISTS list_location_attributes;
CREATE OR REPLACE VIEW list_location_attributes AS 
 SELECT la.id,
 		la.caption,
		la.data_type,
		la.termlist_id,
		la.multi_value,
		law.website_id,
		law.restrict_to_survey_id,
		(((la.id || '|'::text) || la.data_type::text) || '|'::text) || COALESCE(la.termlist_id::text, ''::text) AS signature,
		la.deleted,
		law.deleted AS website_deleted
   FROM location_attributes la
   LEFT JOIN location_attributes_websites law ON la.id = law.location_attribute_id
  ORDER BY law.id;

DROP VIEW IF EXISTS list_location_attribute_values; 
CREATE OR REPLACE VIEW list_location_attribute_values AS 
 SELECT lav.id, l.id AS location_id, la.id AS location_attribute_id, 
        CASE la.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
	    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE la.data_type
        END AS data_type, la.caption, 
        CASE la.data_type
            WHEN 'T'::bpchar THEN lav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN lav.int_value::character varying::text
	    WHEN 'B'::bpchar THEN lav.int_value::character varying::text
            WHEN 'F'::bpchar THEN lav.float_value::character varying::text
            WHEN 'D'::bpchar THEN lav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (lav.date_start_value::character varying::text || ' - '::text) || lav.date_end_value::character varying::text
            ELSE NULL::text
        END AS value,
        CASE la.data_type
            WHEN 'T'::bpchar THEN lav.text_value
            WHEN 'L'::bpchar THEN lav.int_value::character varying::text
            WHEN 'I'::bpchar THEN lav.int_value::character varying::text
	    WHEN 'B'::bpchar THEN lav.int_value::character varying::text
            WHEN 'F'::bpchar THEN lav.float_value::character varying::text
            WHEN 'D'::bpchar THEN lav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (lav.date_start_value::character varying::text || ' - '::text) || lav.date_end_value::character varying::text
            ELSE NULL::text
        END AS raw_value, la.termlist_id, law.website_id
   FROM locations l
   JOIN surveys su ON su.deleted = false
   JOIN location_attributes_websites law ON law.website_id = su.website_id AND (law.restrict_to_survey_id = su.id OR law.restrict_to_survey_id IS NULL) AND law.deleted = false
   JOIN location_attributes la ON la.id = law.location_attribute_id AND la.deleted = false
   LEFT JOIN location_attribute_values lav ON lav.location_attribute_id = la.id AND lav.location_id = l.id AND lav.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id) ON tt.id = lav.int_value AND la.data_type = 'L'::bpchar
  WHERE l.deleted = false
  ORDER BY la.id;

DROP VIEW IF EXISTS list_occurrence_images;
CREATE OR REPLACE VIEW list_occurrence_images AS 
 SELECT oi.*, o.website_id
   FROM occurrence_images oi
   INNER JOIN occurrences o ON o.id = oi.occurrence_id 
  ORDER BY oi.id;


DROP VIEW IF EXISTS list_location_images;
CREATE OR REPLACE VIEW list_location_images AS 
 SELECT li.*, lw.website_id
   FROM location_images li
   INNER JOIN locations l ON l.id = li.location_id
   INNER JOIN locations_websites lw ON l.id = lw.location_id
  ORDER BY li.id;



