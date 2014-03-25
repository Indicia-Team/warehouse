-- Clean up existing data
UPDATE occurrences SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;
DELETE FROM cache_occurrences WHERE sample_id in (select id from samples where deleted=true);
UPDATE sample_attribute_values SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;
UPDATE sample_comments SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;
UPDATE sample_media SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;

UPDATE occurrence_attribute_values SET deleted = true WHERE occurrence_id in (select id from occurrences where deleted=true) and deleted=false;
UPDATE occurrence_comments SET deleted = true WHERE occurrence_id in (select id from occurrences where deleted=true) and deleted=false;
UPDATE occurrence_media SET deleted = true WHERE occurrence_id in (select id from occurrences where deleted=true) and deleted=false;

UPDATE location_attribute_values SET deleted = true WHERE location_id in (select id from locations where deleted=true) and deleted=false;
UPDATE location_media SET deleted = true WHERE location_id in (select id from locations where deleted=true) and deleted=false;