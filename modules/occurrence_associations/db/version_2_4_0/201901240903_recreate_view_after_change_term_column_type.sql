--Recreate view as it was dropped by ddmmyyyyhhmm_change_term_column_type.sql so the data type of terms.term could be changed to varchar
CREATE OR REPLACE VIEW gv_occurrence_associations AS
  SELECT oa.id, o1.id as occurrence_id, o2.id as associated_occurrence_id,
    'Record of ' || t1.taxon as from_occurrence, type_t.term as association_type, 'Record of ' || t2.taxon as to_occurrence
  FROM occurrence_associations oa
  JOIN occurrences o1 ON o1.id=oa.from_occurrence_id AND o1.deleted=false
  JOIN taxa_taxon_lists ttl1 ON ttl1.id=o1.taxa_taxon_list_id AND ttl1.deleted=false
  JOIN taxa t1 ON t1.id=ttl1.taxon_id AND t1.deleted=false
  JOIN termlists_terms type_tlt ON type_tlt.id=oa.association_type_id AND type_tlt.deleted=false
  JOIN terms type_t ON type_t.id=type_tlt.term_id AND type_t.deleted=false
  JOIN occurrences o2 ON o2.id=oa.to_occurrence_id AND o2.deleted=false
  JOIN taxa_taxon_lists ttl2 ON ttl2.id=o2.taxa_taxon_list_id AND ttl2.deleted=false
  JOIN taxa t2 ON t2.id=ttl2.taxon_id AND t2.deleted=false
  UNION
  SELECT oa.id, o2.id as occurrence_id, o1.id as associated_occurrence_id,
    'Record of ' || t1.taxon as from_occurrence, type_t.term as association, 'Record of ' || t2.taxon as to_occurrence
  FROM occurrence_associations oa
  JOIN occurrences o1 ON o1.id=oa.from_occurrence_id AND o1.deleted=false
  JOIN taxa_taxon_lists ttl1 ON ttl1.id=o1.taxa_taxon_list_id AND ttl1.deleted=false
  JOIN taxa t1 ON t1.id=ttl1.taxon_id AND t1.deleted=false
  JOIN termlists_terms type_tlt ON type_tlt.id=oa.association_type_id AND type_tlt.deleted=false
  JOIN terms type_t ON type_t.id=type_tlt.term_id AND type_t.deleted=false
  JOIN occurrences o2 ON o2.id=oa.to_occurrence_id AND o2.deleted=false
  JOIN taxa_taxon_lists ttl2 ON ttl2.id=o2.taxa_taxon_list_id AND ttl2.deleted=false
  JOIN taxa t2 ON t2.id=ttl2.taxon_id AND t2.deleted=false;