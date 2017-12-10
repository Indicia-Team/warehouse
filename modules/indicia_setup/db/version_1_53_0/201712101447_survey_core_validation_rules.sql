ALTER TABLE surveys
   ADD COLUMN core_validation_rules text;
COMMENT ON COLUMN surveys.core_validation_rules
  IS 'JSON listing core fields (entity.fieldname) with altered validation rules for this survey dataset, for example {"sample.location_name":"required"}.';
