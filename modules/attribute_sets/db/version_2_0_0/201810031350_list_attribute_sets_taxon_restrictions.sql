CREATE OR REPLACE VIEW list_attribute_sets_taxon_restrictions AS
 SELECT
    astr.id,
    astr.attribute_sets_survey_id,
    astr.restrict_to_taxon_meaning_id,
    astr.restrict_to_stage_term_meaning_id,
    aset.title as attribute_set_title
   FROM attribute_sets_taxon_restrictions astr
   JOIN attribute_sets_surveys aset_surv on aset_surv.id = astr.attribute_sets_survey_id AND aset_surv.deleted=false
   JOIN attribute_sets aset on aset.id = aset_surv.attribute_set_id AND aset.deleted=false
  WHERE astr.deleted = false;