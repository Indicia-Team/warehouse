update cache_taxa_taxon_lists cttl
set allow_data_entry=ttl.allow_data_entry
from taxa_taxon_lists ttl
where ttl.id=cttl.id and cttl.allow_data_entry<>ttl.allow_data_entry;

delete from cache_taxon_searchterms where taxa_taxon_list_id in (select id from taxa_taxon_lists where allow_data_entry=false);

update taxa_taxon_lists
set updated_on=now()
where id in (
select ttl2.id from taxa_taxon_lists ttl2
left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=ttl2.id
where cts.id is null and ttl2.allow_data_entry=true
);

