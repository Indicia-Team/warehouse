-- Trigger on taxon attribute values so that if we change one relating to the auto-verification checks, the version
-- number on the taxon is incremented. This tags the taxon's occurrences for re-verification.

CREATE OR REPLACE FUNCTION taxon_verification_check_versioning() RETURNS trigger AS $taxon_verification_check_versioning$
DECLARE 
	check_attr boolean := false;
BEGIN
	-- test associated attribute.for_verification_check
	SELECT INTO check_attr for_verification_check FROM taxa_taxon_list_attributes
	WHERE id=NEW.taxa_taxon_list_attribute_id;

	-- if true then increment associated taxon verification check version
	IF check_attr=true THEN
		UPDATE taxa_taxon_lists 
		SET verification_check_version=verification_check_version+1
		WHERE id=NEW.taxa_taxon_list_id;
	END IF;
	RETURN NEW;
END;
$taxon_verification_check_versioning$ LANGUAGE plpgsql;

CREATE TRIGGER taxon_verification_check_versioning AFTER INSERT OR UPDATE ON taxa_taxon_list_attribute_values
	FOR EACH ROW EXECUTE PROCEDURE taxon_verification_check_versioning();