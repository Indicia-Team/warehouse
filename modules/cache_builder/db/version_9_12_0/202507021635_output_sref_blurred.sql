-- #slow script

-- Ensure sensitive records refreshed in Elasticsearch due to additional output_sref_blurred field.
-- NOTE it might be wise to run this script in batches to not interrupt the normal flow of records to Elasticsearch.
UPDATE cache_occurrences_functional
SET website_id=website_id
WHERE sensitive=true;

-- Same for samples.
-- NOTE it might be wise to run this script in batches to not interrupt the normal flow of records to Elasticsearch.
UPDATE cache_samples_functional
SET website_id=website_id
WHERE sensitive = true
OR private=true;
