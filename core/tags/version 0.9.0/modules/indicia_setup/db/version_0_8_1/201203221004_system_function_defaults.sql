-- Apply some standard system functions to existing attributes.

UPDATE sample_attributes SET system_function='email' WHERE lower(caption) IN ('email', 'email address');
UPDATE sample_attributes SET system_function='cms_user_id' WHERE lower(caption) IN ('cms user id');
UPDATE sample_attributes SET system_function='cms_username' WHERE lower(caption) IN ('cms username');
UPDATE sample_attributes SET system_function='first_name' WHERE lower(caption) IN ('first name','forename');
UPDATE sample_attributes SET system_function='lastname' WHERE lower(caption) IN ('last name', 'surname');
UPDATE sample_attributes SET system_function='biotope' WHERE lower(caption) IN ('biotope', 'habitat');

UPDATE occurrence_attributes SET system_function='certainty' WHERE lower(caption) IN ('certain','certainty');
UPDATE occurrence_attributes SET system_function='det_first_name' WHERE lower(caption) IN ('determiner first name','identifier first name');
UPDATE occurrence_attributes SET system_function='det_last_name' WHERE lower(caption) IN ('determiner last name', 'determiner surname', 'identifier last name', 'identifier surname');