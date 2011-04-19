-- A standard attribute that can be detected by the task scheduler to send an email copy of the record back to the user.
INSERT INTO sample_attributes (
	caption,
	data_type,
	created_on,
	created_by_id,
	updated_on,
	updated_by_id,
	applies_to_location,
	multi_value,
	public,
	applies_to_recorder
) VALUES (
	'Email me a copy of the record',
	'B',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	'f'
);