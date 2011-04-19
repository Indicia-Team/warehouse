
DROP VIEW IF EXISTS list_taxon_relations;
CREATE OR REPLACE VIEW list_taxon_relations AS
 SELECT tr.id, tr.from_taxon_meaning_id, tr.to_taxon_meaning_id, tr.taxon_relation_type_id, tr.deleted
   FROM taxon_relations tr
   WHERE tr.deleted = FALSE
;