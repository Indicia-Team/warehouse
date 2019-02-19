--Recreate view as it was dropped by ddmmyyyyhhmm_change_term_column_type.sql so the data type of terms.term could be changed to varchar
CREATE OR REPLACE VIEW gv_taxon_associations AS
    SELECT ta.id, cttlf.id as taxa_taxon_list_id, cttlf.taxon as from_taxon, type_t.term as association_type, cttlt.taxon as to_taxon
  FROM taxon_associations ta
  JOIN cache_taxa_taxon_lists cttlf ON cttlf.taxon_meaning_id=ta.from_taxon_meaning_id AND cttlf.preferred=true
  JOIN cache_taxa_taxon_lists cttlt ON cttlt.taxon_meaning_id=ta.to_taxon_meaning_id
    AND cttlt.id=(SELECT id FROM cache_taxa_taxon_lists WHERE taxon_meaning_id=ta.to_taxon_meaning_id ORDER BY preferred DESC LIMIT 1)
  JOIN termlists_terms type_tlt ON type_tlt.id=ta.association_type_id AND type_tlt.deleted=false
  JOIN terms type_t ON type_t.id=type_tlt.term_id AND type_t.deleted=false
  UNION
  SELECT ta.id, cttlt.id as taxa_taxon_list_id, cttlf.taxon as from_taxon, cttlt.taxon as to_taxon, type_t.term as association_type
  FROM taxon_associations ta
  JOIN cache_taxa_taxon_lists cttlt ON cttlt.taxon_meaning_id=ta.to_taxon_meaning_id AND cttlt.preferred=true
  JOIN cache_taxa_taxon_lists cttlf ON cttlf.taxon_meaning_id=ta.from_taxon_meaning_id
    AND cttlf.id=(SELECT id FROM cache_taxa_taxon_lists WHERE taxon_meaning_id=ta.from_taxon_meaning_id ORDER BY preferred DESC LIMIT 1)
  JOIN termlists_terms type_tlt ON type_tlt.id=ta.association_type_id AND type_tlt.deleted=false
  JOIN terms type_t ON type_t.id=type_tlt.term_id AND type_t.deleted=false;