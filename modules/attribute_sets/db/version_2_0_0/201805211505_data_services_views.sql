CREATE OR REPLACE VIEW list_attribute_sets AS
  SELECT id,
    title,
    description,
    website_id
  FROM attribute_sets
  WHERE deleted = false;

CREATE OR REPLACE VIEW list_attribute_sets_taxa_taxon_list_attributes AS
  SELECT asttla.id,
    aset.id as attribute_set_id,
    aset.title as attribute_set_title,
    aset.website_id,
    ttla.id as taxa_taxon_list_attribute_id,
    ttla.caption as taxa_taxon_list_attribute_caption,
    ttla.data_type as taxa_taxon_list_attribute_data_type,
    ttla.termlist_id as taxa_taxon_list_attribute_termlist_id
  FROM attribute_sets_taxa_taxon_list_attributes asttla
  JOIN attribute_sets aset on aset.id=asttla.attribute_set_id and aset.deleted=false
  JOIN taxa_taxon_list_attributes ttla on ttla.id=asttla.taxa_taxon_list_attribute_id AND ttla.deleted=false
  WHERE asttla.deleted = false;

CREATE OR REPLACE VIEW list_attribute_sets_surveys AS
  SELECT ass.id,
    ass.survey_id,
    aset.id as attribute_set_id,
    aset.title as attribute_set_title,
    aset.website_id
  FROM attribute_sets_surveys ass
  JOIN attribute_sets aset on aset.id=ass.attribute_set_id and aset.deleted=false
  WHERE ass.deleted = false;