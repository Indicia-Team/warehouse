ALTER TABLE sample_attributes ADD applies_to_recorder boolean NOT NULL DEFAULT 'f';

COMMENT ON COLUMN sample_attributes.applies_to_recorder IS 'For attributes that are gathered which pertain to the person recording the sample rather than the specific sample, this flag is set to true.';

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Titles', 'Lookup list of titles to attach to samples.', now(), 1, now(), 1, 'indicia:titles');

SELECT insert_term('Mr', 'eng', null, 'indicia:titles');
SELECT insert_term('Mrs', 'eng', null, 'indicia:titles');
SELECT insert_term('Miss', 'eng', null, 'indicia:titles');
SELECT insert_term('Ms', 'eng', null, 'indicia:titles');
SELECT insert_term('Master', 'eng', null, 'indicia:titles');
SELECT insert_term('Dr', 'eng', null, 'indicia:titles');

INSERT INTO sample_attributes (
	caption,
	data_type,
	created_on,
	created_by_id,
	updated_on,
	updated_by_id,
	applies_to_location,
	termlist_id,
	multi_value,
	public,
	applies_to_recorder
) VALUES (
	'Title',
	'L',
	now(),
	1,
	now(),
	1,
	'f',
	(select id from termlists where external_key='indicia:titles'),
	'f',
	't',
	't'
);

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
	'First name',
	'S',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'	
);

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
	'Last name',
	'S',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'
);

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
	'Email',
	'S',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'
);

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
	'Address',
	'S',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'
);

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
	'Postcode',
	'S',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'
);

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
	'Sample Reference',
	'S',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'
);

INSERT INTO sample_attributes (
	caption,
	data_type,
	created_on,
	created_by_id,
	updated_on,
	updated_by_id,
	validation_rules,
	applies_to_location,
	multi_value,
	public,
	applies_to_recorder
) VALUES (
	'Altitude (m)',
	'F',
	now(),
	1,
	now(),
	1,
	'number',
	'f',	
	'f',
	't',
	't'
);






