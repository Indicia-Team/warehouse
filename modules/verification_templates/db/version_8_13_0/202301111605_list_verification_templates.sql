CREATE OR REPLACE VIEW list_verification_templates AS
  SELECT vt.id, vt.website_id, vt.template_statuses, vt.title,
      vt.restrict_to_external_keys, vt.restrict_to_family_external_keys,
      vt.restrict_to_website_id, vt.template, vt.created_by_id
    FROM verification_templates vt
    WHERE vt.deleted = false;