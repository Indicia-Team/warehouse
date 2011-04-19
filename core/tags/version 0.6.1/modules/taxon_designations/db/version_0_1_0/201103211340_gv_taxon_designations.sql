CREATE OR REPLACE VIEW gv_taxa_taxon_designations AS
SELECT ttd.id, td.id AS taxon_designation_id, ttl.id AS taxa_taxon_list_id,
  tx.id as taxon_id, tx.taxon, td.title, t.term AS category, td.deleted
FROM taxon_designations td
JOIN taxa_taxon_designations ttd ON ttd.taxon_designation_id=td.id AND ttd.deleted=false
JOIN taxa tx ON tx.id=ttd.taxon_id AND tx.deleted=false
JOIN taxa_taxon_lists ttl ON ttl.taxon_id=tx.id AND ttl.deleted=false
LEFT JOIN (
  termlists_terms tlt 
  JOIN terms t on t.id=tlt.term_id AND t.deleted=false
) ON tlt.id=td.category_id AND tlt.deleted=false
WHERE td.deleted=false