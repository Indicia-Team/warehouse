-- recreate view in case core upgrader has had to drop it
CREATE OR REPLACE VIEW list_taxa_taxon_designations AS 
 SELECT ttd.id, td.title, td.code, td.abbreviation, t.taxon, cttl.default_common_name as common, cttl.preferred_taxon as preferred_name, cttl.language, cttl.taxon_group
   FROM taxon_designations td
   JOIN taxa_taxon_designations ttd ON ttd.taxon_designation_id = td.id AND ttd.deleted = false
   JOIN taxa t on t.id=ttd.taxon_id and t.deleted=false
   JOIN taxa_taxon_lists ttl on ttl.taxon_id=t.id and ttl.deleted=false
   JOIN cache_taxa_taxon_lists cttl on cttl.id=ttl.id
  WHERE td.deleted = false;