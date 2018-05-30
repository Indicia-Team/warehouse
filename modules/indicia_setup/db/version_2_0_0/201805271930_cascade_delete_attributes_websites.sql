CREATE OR REPLACE FUNCTION cascade_occurrence_attributes_websites_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrence_attribute_taxon_restrictions SET deleted = true  WHERE occurrence_attributes_website_id  = OLD.id;
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER cascade_occurrence_attributes_websites_delete_trigger
AFTER UPDATE ON occurrence_attributes_websites
FOR EACH ROW EXECUTE PROCEDURE cascade_occurrence_attributes_websites_delete();

CREATE OR REPLACE FUNCTION cascade_sample_attributes_websites_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE sample_attribute_taxon_restrictions SET deleted = true  WHERE sample_attributes_website_id  = OLD.id;
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER cascade_sample_attributes_websites_delete_trigger
AFTER UPDATE ON sample_attributes_websites
FOR EACH ROW EXECUTE PROCEDURE cascade_sample_attributes_websites_delete();

CREATE OR REPLACE FUNCTION cascade_taxon_lists_taxa_taxon_list_attributes_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE taxa_taxon_list_attribute_taxon_restrictions SET deleted = true  WHERE taxon_lists_taxa_taxon_list_attribute_id  = OLD.id;
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER cascade_taxon_lists_taxa_taxon_list_attributes_delete_trigger
AFTER UPDATE ON taxon_lists_taxa_taxon_list_attributes
FOR EACH ROW EXECUTE PROCEDURE cascade_taxon_lists_taxa_taxon_list_attributes_delete();