
ALTER TABLE verification_templates
  ADD COLUMN title character varying;

UPDATE verification_templates
  SET title = 'Template ' || CAST(id AS character varying);

ALTER TABLE verification_templates
  ALTER COLUMN title SET NOT NULL;


ALTER TABLE verification_templates
  ADD COLUMN template_statuses varchar[];

UPDATE verification_templates
  SET template_statuses = ARRAY['V'::varchar];

--- Values that will be stored are:
--- accepted 'V'
--- accepted as correct 'V1'
--- accepted as considered correct 'V2'
--- Plausible 'C3'
--- not accepted 'R'
--- not accepted as unable to verify 'R4'
--- not accepted as incorrect 'R5'
--- redetermined 'DT'

ALTER TABLE verification_templates
  ALTER COLUMN template_statuses SET NOT NULL;


COMMENT ON COLUMN verification_templates.title IS 'Title of verification template used to identify record.';
COMMENT ON COLUMN verification_templates.template_statuses IS 'Verification statuses for which this template is applicable';

DROP VIEW IF EXISTS gv_verification_templates;
CREATE VIEW gv_verification_templates AS
  SELECT vt.id, substr(vt.template_statuses::text , 2 , length(vt.template_statuses::text)-2) as template_statuses, vt.title
    FROM verification_templates vt
    WHERE vt.deleted = false;

DROP VIEW IF EXISTS list_verification_templates;
CREATE VIEW list_verification_templates AS
  SELECT vt.id, vt.website_id, vt.template_statuses, vt.title,
      vt.restrict_to_external_keys, vt.restrict_to_family_external_keys,
      vt.restrict_to_website_id, vt.template
    FROM verification_templates vt
    WHERE vt.deleted = false;
