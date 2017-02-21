-- #slow script#

UPDATE cache_taxon_searchterms
SET searchterm = regexp_replace(regexp_replace(
          lower( regexp_replace(original, E'\\(.+\\)', '', 'g') || coalesce(authority, '') ), 'ae', 'e', 'g'
        ), E'[^a-z0-9\\?\\+]', '', 'g'),
    searchterm_length = length(regexp_replace(regexp_replace(
          lower( regexp_replace(original, E'\\(.+\\)', '', 'g') || coalesce(authority, '') ), 'ae', 'e', 'g'
        ), E'[^a-z0-9\\?\\+]', '', 'g'))
WHERE simplified=true
AND searchterm <> regexp_replace(regexp_replace(
          lower( regexp_replace(original, E'\\(.+\\)', '', 'g') || coalesce(authority, '') ), 'ae', 'e', 'g'
        ), E'[^a-z0-9\\?\\+]', '', 'g')
