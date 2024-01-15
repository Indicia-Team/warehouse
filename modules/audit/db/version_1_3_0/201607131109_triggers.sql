-- add this trigger can only be done now, as 1.3 adds updated_by_id to the table which auditing requires.
SELECT audit.audit_table('locations_websites', true, 'location_id');