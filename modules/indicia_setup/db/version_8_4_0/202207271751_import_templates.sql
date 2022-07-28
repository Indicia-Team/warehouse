
CREATE TABLE IF NOT EXISTS import_templates
(
  id serial NOT NULL,
  title character varying NOT NULL,
  entity character varying NOT NULL,
  mappings json NOT NULL,
  global_values json NOT NULL,
  group_id integer,
  created_on timestamp without time zone,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_import_tenplates PRIMARY KEY (id),
  CONSTRAINT fk_import_templates_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_import_templates_updator FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_import_templates_group FOREIGN KEY (group_id)
      REFERENCES groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE import_templates IS 'Predefined templates for imports.';
COMMENT ON COLUMN import_templates.entity IS 'Data entity the import template is for, e.g. occurrence.';
COMMENT ON COLUMN import_templates.mappings IS 'JSON defining the import column names and the fields they map to in the database.';
COMMENT ON COLUMN import_templates.global_values IS 'JSON defining database fields which have default values that apply to all rows in the import.';
COMMENT ON COLUMN import_templates.group_id IS 'Foreign key to the group this template is for, only if the template is for importing group records.';
COMMENT ON COLUMN import_templates.created_on IS 'Date this record was created.';
COMMENT ON COLUMN import_templates.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN import_templates.updated_on IS 'Date this record was updated.';
COMMENT ON COLUMN import_templates.updated_by_id IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN import_templates.deleted IS 'Has this record been deleted?';