-- Bug in taxon designation importer created blank designations for empty rows.
DELETE FROM taxa_taxon_designations WHERE taxon_designation_id IN (
  SELECT id FROM taxon_designations WHERE title = ''
);
DELETE FROM taxon_designations WHERE title='';