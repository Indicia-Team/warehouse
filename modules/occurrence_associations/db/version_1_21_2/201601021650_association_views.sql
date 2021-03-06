﻿CREATE OR REPLACE VIEW list_occurrence_associations AS
select
  oa.id, oa.from_occurrence_id, oa.to_occurrence_id, cttlfrom.taxon as from_taxon, cttlto.taxon as to_taxon, cofrom.sample_id,
  oa.association_type_id, atype.term as association_type, oa.part_id, part.term as part,
  oa.position_id, pos.term as position, oa.impact_id, impact.term as impact, cofrom.website_id
from occurrence_associations oa
join cache_occurrences_functional cofrom on cofrom.id=oa.from_occurrence_id
join cache_occurrences_functional coto on coto.id=oa.to_occurrence_id
join cache_taxa_taxon_lists cttlfrom on cttlfrom.id=cofrom.taxa_taxon_list_id
join cache_taxa_taxon_lists cttlto on cttlto.id=coto.taxa_taxon_list_id
join cache_termlists_terms atype on atype.id=oa.association_type_id
left join cache_termlists_terms part on part.id=oa.part_id
left join cache_termlists_terms pos on pos.id=oa.position_id
left join cache_termlists_terms impact on impact.id=oa.impact_id
where oa.deleted=false;

CREATE OR REPLACE VIEW detail_occurrence_associations AS
select
  oa.id, oa.from_occurrence_id, oa.to_occurrence_id, cttlfrom.taxon as from_taxon, cttlto.taxon as to_taxon, cofrom.sample_id,
  oa.association_type_id, atype.term as association_type, oa.part_id, part.term as part,
  oa.position_id, pos.term as position, oa.impact_id, impact.term as impact, oa.comment,
  oa.created_by_id, c.username AS created_by, oa.updated_by_id, u.username AS updated_by, cofrom.website_id
from occurrence_associations oa
join cache_occurrences_functional cofrom on cofrom.id=oa.from_occurrence_id
join cache_occurrences_functional coto on coto.id=oa.to_occurrence_id
join cache_taxa_taxon_lists cttlfrom on cttlfrom.id=cofrom.taxa_taxon_list_id
join cache_taxa_taxon_lists cttlto on cttlto.id=coto.taxa_taxon_list_id
join cache_termlists_terms atype on atype.id=oa.association_type_id
left join cache_termlists_terms part on part.id=oa.part_id
left join cache_termlists_terms pos on pos.id=oa.position_id
left join cache_termlists_terms impact on impact.id=oa.impact_id
JOIN users c ON c.id = oa.created_by_id
JOIN users u ON u.id = oa.updated_by_id
where oa.deleted=false;
