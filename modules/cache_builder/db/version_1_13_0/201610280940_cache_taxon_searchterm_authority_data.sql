-- #slow script#

UPDATE cache_taxon_searchterms cts
SET authority = t.authority,
searchterm = case
  when name_type IN ('L', 'S', 'V') and simplified=true then
    regexp_replace(regexp_replace(regexp_replace(lower(t.taxon || t.authority), E'\\(.+\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\?\\+]', '', 'g')
  when name_type IN ('L', 'S', 'V') and simplified=false then
    original || ' ' || t.authority
  else searchterm
end
FROM taxa_taxon_lists ttl
JOIN taxa t on t.id=ttl.taxon_id
WHERE ttl.id=cts.taxa_taxon_list_id
AND t.authority IS NOT NULL;