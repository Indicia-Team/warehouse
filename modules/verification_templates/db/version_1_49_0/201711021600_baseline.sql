

-- Sequences
CREATE SEQUENCE verification_templates_id_seq
  START WITH 1
  INCREMENT BY 1
  NO MAXVALUE
  NO MINVALUE
  CACHE 1;


-- Tables
CREATE TABLE verification_templates
(
  id                               integer                     NOT NULL DEFAULT nextval('verification_templates_id_seq'::regclass),
  website_id                       integer                     NOT NULL,
  restrict_to_external_keys        varchar[],
  restrict_to_family_external_keys varchar[],
  restrict_to_website_id           boolean                     DEFAULT false NOT NULL,
  template                         character varying           NOT NULL,
  created_on                       timestamp without time zone NOT NULL,
  created_by_id                    integer                     NOT NULL,
  updated_on                       timestamp without time zone NOT NULL,
  updated_by_id                    integer                     NOT NULL,
  deleted                          boolean                     DEFAULT false NOT NULL,
  
  CONSTRAINT pk_verification_templates PRIMARY KEY (id),
  CONSTRAINT fk_verification_templates_website FOREIGN KEY (website_id) REFERENCES websites(id),
  CONSTRAINT fk_verification_templates_creator FOREIGN KEY (created_by_id) REFERENCES users(id),
  CONSTRAINT fk_verification_templates_updater FOREIGN KEY (updated_by_id) REFERENCES users(id)
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE verification_templates IS 'Definition of templates used in the verification process';
COMMENT ON COLUMN verification_templates.id IS 'Unique identifier for each verification template.';
COMMENT ON COLUMN verification_templates.website_id IS 'The Indicia ID of a website that the event is associated with.';
COMMENT ON COLUMN verification_templates.restrict_to_external_keys IS 'An optional array of character keys that match against the taxon external key for records this template applies to.';
COMMENT ON COLUMN verification_templates.restrict_to_family_external_keys IS 'An optional array of character keys that match against the external key for the family taxon for records this template applies to.';
COMMENT ON COLUMN verification_templates.restrict_to_website_id IS 'Boolean flag indicating whether to restrict the applicability of this template to the creating website.';
COMMENT ON COLUMN verification_templates.template IS 'Text field holding the verification template.';
COMMENT ON COLUMN verification_templates.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN verification_templates.created_on IS 'Date this record was created.';
COMMENT ON COLUMN verification_templates.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN verification_templates.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN verification_templates.updated_by_id IS 'Foreign key to the users table (last updater).';


-- Views
DROP VIEW IF EXISTS gv_verification_templates;
CREATE VIEW gv_verification_templates AS
  SELECT vt.id
    FROM verification_templates vt
    WHERE vt.deleted = false;
  
DROP VIEW IF EXISTS list_verification_templates;
CREATE VIEW list_verification_templates AS
  SELECT vt.id, vt.website_id, vt.restrict_to_external_keys, vt.restrict_to_family_external_keys,
      vt.restrict_to_website_id, vt.template
    FROM verification_templates vt
    WHERE vt.deleted = false;

