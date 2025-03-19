-- #slow script#

-- Prevent tracking updates as this update does not affect information sent to
-- Elasticsearch.
SET application_name = 'skiptrigger';

-- Fill in taxon_path if it was unable to be populated from the master list.
UPDATE cache_occurrences_functional u
  SET taxon_path=ctp.path
  FROM cache_taxa_taxon_lists cttl
  JOIN cache_taxon_paths ctp ON ctp.external_key=cttl.external_key AND ctp.taxon_list_id=cttl.taxon_list_id
  WHERE u.taxon_path IS NULL
  AND u.taxa_taxon_list_id=cttl.id
  AND cttl.taxon_list_id<>#master_list_id#