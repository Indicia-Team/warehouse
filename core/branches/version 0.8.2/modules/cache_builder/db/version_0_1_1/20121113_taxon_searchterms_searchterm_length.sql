ALTER TABLE cache_taxon_searchterms ADD COLUMN searchterm_length integer;
COMMENT ON COLUMN cache_taxon_searchterms.searchterm_length IS 'Contains the length of the searchterm field, useful for taxon name searches. Putting shorter searchterms at the top of a list brings the "nearest" matches to the top.';

UPDATE cache_taxon_searchterms SET searchterm_length=LENGTH(searchterm);

