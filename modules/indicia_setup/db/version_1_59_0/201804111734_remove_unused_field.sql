DROP TRIGGER IF EXISTS taxon_verification_check_versioning ON taxa_taxon_list_attribute_values;
DROP FUNCTION IF EXISTS taxon_verification_check_versioning();

ALTER TABLE taxa_taxon_list_attributes
  DROP COLUMN for_verification_check;