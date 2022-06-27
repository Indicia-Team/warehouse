--- initially these trigger definitions include no excluded rows, and audit at both row and statement level

SELECT audit.audit_table('occurrences'); 
SELECT audit.audit_table('occurrence_attribute_values', true, 'occurrence_id'); 
SELECT audit.audit_table('occurrence_comments', true, 'occurrence_id'); 
SELECT audit.audit_table('occurrence_media', true, 'occurrence_id'); 

SELECT audit.audit_table('samples'); 
SELECT audit.audit_table('sample_attribute_values', true, 'sample_id'); 
SELECT audit.audit_table('sample_comments', true, 'sample_id'); 
SELECT audit.audit_table('sample_media', true, 'sample_id'); 

SELECT audit.audit_table('locations'); 
SELECT audit.audit_table('location_attribute_values', true, 'location_id'); 
SELECT audit.audit_table('location_media', true, 'location_id');