-- Trigger on taxon designations  so that if we change one, the version
-- number on the taxon is incremented. This tags the taxon's occurrences for re-verification.

CREATE FUNCTION taxon_designation_verification_check_versioning() RETURNS trigger AS $taxon_designation_verification_check_versioning$
BEGIN
	UPDATE taxa_taxon_lists 
		SET verification_check_version=verification_check_version+1
		WHERE taxon_id=NEW.taxon_id;
  RETURN NEW;
END;
$taxon_designation_verification_check_versioning$ LANGUAGE plpgsql;

CREATE TRIGGER taxon_designation_verification_check_versioning AFTER INSERT OR UPDATE ON taxa_taxon_designations
	FOR EACH ROW EXECUTE PROCEDURE taxon_designation_verification_check_versioning();