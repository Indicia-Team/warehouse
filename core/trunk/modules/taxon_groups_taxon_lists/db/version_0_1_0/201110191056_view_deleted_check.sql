CREATE OR REPLACE VIEW gv_taxon_groups_taxon_lists AS 
 SELECT tgtl.id, tg.id AS taxon_group_id, tg.title, tgtl.deleted, tgtl.taxon_list_id
   FROM taxon_groups_taxon_lists tgtl
   JOIN taxon_groups tg ON tg.id = tgtl.taxon_group_id AND tg.deleted = false
   WHERE tgtl.deleted=false;