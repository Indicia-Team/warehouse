
CREATE SEQUENCE sample_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
CREATE TABLE sample_images (
    id integer DEFAULT nextval('sample_images_id_seq'::regclass) NOT NULL,
    sample_id integer NOT NULL,
    "path" character varying(200) NOT NULL,
    caption character varying(100),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
);
ALTER TABLE ONLY sample_images
    ADD CONSTRAINT pk_sample_images PRIMARY KEY (id);
ALTER TABLE ONLY sample_images
    ADD CONSTRAINT fk_sample_image_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
ALTER TABLE ONLY sample_images
    ADD CONSTRAINT fk_sample_image_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
COMMENT ON TABLE sample_images IS 'Lists images that are attached to sample records.';
COMMENT ON COLUMN sample_images.sample_id IS 'Foreign key to the samples table. Identifies the sample that the image is attached to.';
COMMENT ON COLUMN sample_images."path" IS 'Path to the image file, relative to the server''s image storage folder.';
COMMENT ON COLUMN sample_images.caption IS 'Caption for the image.';
COMMENT ON COLUMN sample_images.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_images.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_images.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN sample_images.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN sample_images.deleted IS 'Has this record been deleted?';