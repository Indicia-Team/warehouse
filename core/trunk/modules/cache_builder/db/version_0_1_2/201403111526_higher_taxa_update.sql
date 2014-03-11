-- #slow script#

with recursive q as ( 
    select ttl.id, ttl.id as top_parent_id, t.taxon as top_taxon, t.external_key as child_key
    from taxa_taxon_lists ttl
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false and tr.rank='Kingdom'    
    where ttl.preferred=true
    union all 
    select tc.id, q.top_parent_id, q.top_taxon, tc.external_key
    from q 
    join cache_taxa_taxon_lists tc on tc.parent_id = q.id and tc.preferred=true
  ) select distinct * into temporary rankupdate from q;

update cache_taxa_taxon_lists cttl
set kingdom_taxon=top_taxon, kingdom_taxa_taxon_list_id=cttltop.id
from rankupdate ru 
join cache_taxa_taxon_lists cttltop on cttltop.id=ru.top_parent_id
where ru.child_key=cttl.external_key;

truncate rankupdate;

insert into rankupdate
with recursive q as ( 
    select ttl.id, ttl.id as top_parent_id, t.taxon as top_taxon, t.external_key as child_key
    from taxa_taxon_lists ttl
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false and tr.rank='Order'    
    where ttl.preferred=true
    union all 
    select tc.id, q.top_parent_id, q.top_taxon, tc.external_key
    from q 
    join cache_taxa_taxon_lists tc on tc.parent_id = q.id and tc.preferred=true
  ) select distinct * from q;

update cache_taxa_taxon_lists cttl
set order_taxon=top_taxon, order_taxa_taxon_list_id=cttltop.id
from rankupdate ru 
join cache_taxa_taxon_lists cttltop on cttltop.id=ru.top_parent_id
where ru.child_key=cttl.external_key;

truncate rankupdate;

insert into rankupdate
with recursive q as ( 
    select ttl.id, ttl.id as top_parent_id, t.taxon as top_taxon, t.external_key as child_key
    from taxa_taxon_lists ttl
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false and tr.rank='Family'    
    where ttl.preferred=true
    union all 
    select tc.id, q.top_parent_id, q.top_taxon, tc.external_key
    from q 
    join cache_taxa_taxon_lists tc on tc.parent_id = q.id and tc.preferred=true
  ) select distinct * from q;

update cache_taxa_taxon_lists cttl
set family_taxon=top_taxon, family_taxa_taxon_list_id=cttltop.id
from rankupdate ru 
join cache_taxa_taxon_lists cttltop on cttltop.id=ru.top_parent_id
where ru.child_key=cttl.external_key;

drop table rankupdate;