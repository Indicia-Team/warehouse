DROP VIEW IF EXISTS list_taxon_associations;

CREATE OR REPLACE VIEW list_taxon_associations AS 
select 
  ta.id, ta.from_taxon_meaning_id, ta.to_taxon_meaning_id, cttlfrom.taxon as from_taxon, cttlto.taxon as to_taxon,
  cttlfrom.preferred_taxon as from_preferred_taxon, cttlto.preferred_taxon as to_preferred_taxon,
  ta.association_type_id, atype.term as association_type, ta.part_id, part.term as part,
  ta.position_id, pos.term as position, ta.impact_id, impact.term as impact,
  cttlfrom.taxon_list_id as from_taxon_list_id, cttlfrom.preferred as from_preferred,
  cttlto.taxon_list_id as to_taxon_list_id, cttlto.preferred as to_preferred,
  cttlfrom.taxon_list_id=cttlto.taxon_list_id as same_list, cttlfrom.website_id
from taxon_associations ta
join cache_taxa_taxon_lists cttlfrom on cttlfrom.taxon_meaning_id=ta.from_taxon_meaning_id
join cache_taxa_taxon_lists cttlto on cttlto.taxon_meaning_id=ta.to_taxon_meaning_id
join cache_termlists_terms atype on atype.id=ta.association_type_id
left join cache_termlists_terms part on part.id=ta.part_id
left join cache_termlists_terms pos on pos.id=ta.position_id
left join cache_termlists_terms impact on impact.id=ta.impact_id
where ta.deleted=false;

DROP VIEW IF EXISTS detail_taxon_associations;

CREATE OR REPLACE VIEW detail_taxon_associations AS
select
  ta.id, ta.from_taxon_meaning_id, ta.to_taxon_meaning_id, cttlfrom.taxon as from_taxon, cttlto.taxon as to_taxon,
  cttlfrom.preferred_taxon as from_preferred_taxon, cttlto.preferred_taxon as to_preferred_taxon,
  ta.association_type_id, atype.term as association_type, ta.part_id, part.term as part,
  ta.position_id, pos.term as position, ta.impact_id, impact.term as impact,
  cttlfrom.taxon_list_id as from_taxon_list_id, cttlfrom.preferred as from_preferred,
  cttlto.taxon_list_id as to_taxon_list_id, cttlto.preferred as to_preferred,
  cttlfrom.taxon_list_id=cttlto.taxon_list_id as same_list, ta.comment,
  ta.created_by_id, c.username AS created_by, ta.updated_by_id, u.username AS updated_by, cttlfrom.website_id
from taxon_associations ta
join cache_taxa_taxon_lists cttlfrom on cttlfrom.taxon_meaning_id=ta.from_taxon_meaning_id
join cache_taxa_taxon_lists cttlto on cttlto.taxon_meaning_id=ta.to_taxon_meaning_id
join cache_termlists_terms atype on atype.id=ta.association_type_id
left join cache_termlists_terms part on part.id=ta.part_id
left join cache_termlists_terms pos on pos.id=ta.position_id
left join cache_termlists_terms impact on impact.id=ta.impact_id
JOIN users c ON c.id = ta.created_by_id
JOIN users u ON u.id = ta.updated_by_id
where ta.deleted=false;