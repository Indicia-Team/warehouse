ALTER TABLE cache_taxon_searchterms ADD COLUMN taxon_group_id integer;
COMMENT ON COLUMN cache_taxon_searchterms.taxon_group_id IS 'ID of the taxon group';

-- Safe to delete and restart, because this table has not been officially released at this point so for live servers will be empty.
DELETE FROM cache_taxon_searchterms;

DELETE FROM variables WHERE name='populated-taxon_searchterms';