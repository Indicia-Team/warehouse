-- Convert address into Address 1 and Address 2
UPDATE sample_attributes SET caption='Address 1' WHERE caption='Address';

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
	'Address 2',
	'T',
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
	'Town',
	'T',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'
);

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Counties', 'Lookup list of postal counties in the UK.', now(), 1, now(), 1, 'indicia:counties');

SELECT insert_term('Avon', 'eng', null, 'indicia:counties');
SELECT insert_term('Bedfordshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Berkshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Borders', 'eng', null, 'indicia:counties');
SELECT insert_term('Buckinghamshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Cambridgeshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Central', 'eng', null, 'indicia:counties');
SELECT insert_term('Cheshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Cleveland', 'eng', null, 'indicia:counties');
SELECT insert_term('Clwyd', 'eng', null, 'indicia:counties');
SELECT insert_term('Cornwall', 'eng', null, 'indicia:counties');
SELECT insert_term('County Antrim', 'eng', null, 'indicia:counties');
SELECT insert_term('County Armagh', 'eng', null, 'indicia:counties');
SELECT insert_term('County Down', 'eng', null, 'indicia:counties');
SELECT insert_term('County Fermanagh', 'eng', null, 'indicia:counties');
SELECT insert_term('County Londonderry', 'eng', null, 'indicia:counties');
SELECT insert_term('County Tyrone', 'eng', null, 'indicia:counties');
SELECT insert_term('Cumbria', 'eng', null, 'indicia:counties');
SELECT insert_term('Derbyshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Devon', 'eng', null, 'indicia:counties');
SELECT insert_term('Dorset', 'eng', null, 'indicia:counties');
SELECT insert_term('Dumfries and Galloway', 'eng', null, 'indicia:counties');
SELECT insert_term('Durham', 'eng', null, 'indicia:counties');
SELECT insert_term('Dyfed', 'eng', null, 'indicia:counties');
SELECT insert_term('East Sussex', 'eng', null, 'indicia:counties');
SELECT insert_term('Essex', 'eng', null, 'indicia:counties');
SELECT insert_term('Fife', 'eng', null, 'indicia:counties');
SELECT insert_term('Gloucestershire', 'eng', null, 'indicia:counties');
SELECT insert_term('Grampian', 'eng', null, 'indicia:counties');
SELECT insert_term('Greater Manchester', 'eng', null, 'indicia:counties');
SELECT insert_term('Gwent', 'eng', null, 'indicia:counties');
SELECT insert_term('Gwynedd County', 'eng', null, 'indicia:counties');
SELECT insert_term('Hampshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Herefordshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Hertfordshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Highlands and Islands', 'eng', null, 'indicia:counties');
SELECT insert_term('Humberside', 'eng', null, 'indicia:counties');
SELECT insert_term('Isle of Wight', 'eng', null, 'indicia:counties');
SELECT insert_term('Kent', 'eng', null, 'indicia:counties');
SELECT insert_term('Lancashire', 'eng', null, 'indicia:counties');
SELECT insert_term('Leicestershire', 'eng', null, 'indicia:counties');
SELECT insert_term('Lincolnshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Lothian', 'eng', null, 'indicia:counties');
SELECT insert_term('Merseyside', 'eng', null, 'indicia:counties');
SELECT insert_term('Mid Glamorgan', 'eng', null, 'indicia:counties');
SELECT insert_term('Norfolk', 'eng', null, 'indicia:counties');
SELECT insert_term('North Yorkshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Northamptonshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Northumberland', 'eng', null, 'indicia:counties');
SELECT insert_term('Nottinghamshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Oxfordshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Powys', 'eng', null, 'indicia:counties');
SELECT insert_term('Rutland', 'eng', null, 'indicia:counties');
SELECT insert_term('Shropshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Somerset', 'eng', null, 'indicia:counties');
SELECT insert_term('South Yorkshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Staffordshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Strathclyde', 'eng', null, 'indicia:counties');
SELECT insert_term('Suffolk', 'eng', null, 'indicia:counties');
SELECT insert_term('Surrey', 'eng', null, 'indicia:counties');
SELECT insert_term('Tayside', 'eng', null, 'indicia:counties');
SELECT insert_term('Tyne and Wear', 'eng', null, 'indicia:counties');
SELECT insert_term('Warwickshire', 'eng', null, 'indicia:counties');
SELECT insert_term('West Glamorgan', 'eng', null, 'indicia:counties');
SELECT insert_term('West Midlands', 'eng', null, 'indicia:counties');
SELECT insert_term('West Sussex', 'eng', null, 'indicia:counties');
SELECT insert_term('West Yorkshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Wiltshire', 'eng', null, 'indicia:counties');
SELECT insert_term('Worcestershire', 'eng', null, 'indicia:counties');

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
	'County',
	'L',
	now(),
	1,
	now(),
	1,
	'f',
	(select id from termlists where external_key='indicia:counties'),
	'f',
	't',
	't'
);

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Countries', 'Lookup list of countries.', now(), 1, now(), 1, 'indicia:countries');

SELECT insert_term('United Kingdom', 'eng', null, 'indicia:countries');
SELECT insert_term('England', 'eng', null, 'indicia:countries');
SELECT insert_term('Scotland', 'eng', null, 'indicia:countries');
SELECT insert_term('Wales', 'eng', null, 'indicia:countries');
SELECT insert_term('Northern Ireland', 'eng', null, 'indicia:countries');

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
	'Country',
	'L',
	now(),
	1,
	now(),
	1,
	'f',
	(select id from termlists where external_key='indicia:countries'),
	'f',
	't',
	't'
);

--Parent the UK countries under UK
UPDATE termlists_terms
SET parent_id=(SELECT id FROM list_termlists_terms WHERE term='United Kingdom' AND termlist_external_key='indicia:countries')
WHERE id IN (SELECT id FROM list_termlists_terms WHERE termlist_external_key='indicia:countries' AND term!='United Kingdom');

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
	'Telephone Number',
	'T',
	now(),
	1,
	now(),
	1,
	'f',	
	'f',
	't',
	't'
);