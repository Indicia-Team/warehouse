INSERT INTO sample_attributes (caption, data_type, validation_rules, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Weather', 'T', '', true, now(), 1, now(), 1);

INSERT INTO sample_attributes (caption, data_type, validation_rules, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Temperature (Celsius)', 'F', 'number,min[-20],max[45]', true, now(), 1, now(), 1);

INSERT INTO occurrence_attributes (caption, data_type, validation_rules, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Determination Date', 'V', '', true, now(), 1, now(), 1);

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('DAFOR', 'Abundance data', now(), 1, now(), 1);

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Surroundings', 'Surroundings', now(), 1, now(), 1);

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Site_Usages', 'How the site in question is used.', now(), 1, now(), 1);

CREATE FUNCTION ttm() RETURNS integer AS $$
DECLARE
	dafor_array Text[];
	surroundings_array Text[];
	usage_array Text[];
	m_id integer;
BEGIN
	dafor_array[1] = 'Dominant';
	dafor_array[2] = 'Abundant';
	dafor_array[3] = 'Frequent';
	dafor_array[4] = 'Occasional';
	dafor_array[5] = 'Rare';

	surroundings_array[1] = 'Urban';
	surroundings_array[2] = 'Suburban';
	surroundings_array[3] = 'Countryside';

	usage_array[1] = 'Cycling';
	usage_array[2] = 'Motorcycles';
	usage_array[3] = 'Sheep Grazing';
	usage_array[4] = 'Cattle Grazing';
	

	FOR idx IN array_lower(dafor_array, 1)..array_upper(dafor_array, 1) LOOP
		INSERT INTO terms (term, language_id, created_on, created_by_id, updated_on, updated_by_id)
		VALUES (dafor_array[idx], (SELECT id from languages WHERE iso = 'eng'), now(), 1, now(), 1);
		m_id := nextval('meanings_id_seq'::regclass);
		INSERT INTO meanings VALUES (m_id);
		INSERT INTO termlists_terms (term_id, termlist_id, meaning_id, created_on, created_by_id, updated_on, updated_by_id)
		VALUES ((SELECT id FROM terms WHERE term = dafor_array[idx]),
			(SELECT id FROM termlists WHERE title = 'DAFOR'),
			m_id, now(), 1, now(), 1);
	END LOOP;

	FOR idx IN array_lower(surroundings_array, 1)..array_upper(surroundings_array, 1) LOOP
		INSERT INTO terms (term, language_id, created_on, created_by_id, updated_on, updated_by_id)
		VALUES (surroundings_array[idx], (SELECT id from languages WHERE iso = 'eng'), now(), 1, now(), 1);
		m_id := nextval('meanings_id_seq'::regclass);
		INSERT INTO meanings VALUES (m_id);
		INSERT INTO termlists_terms (term_id, termlist_id, meaning_id, created_on, created_by_id, updated_on, updated_by_id)
		VALUES ((SELECT id FROM terms WHERE term = surroundings_array[idx]),
			(SELECT id FROM termlists WHERE title = 'Surroundings'),
			m_id, now(), 1, now(), 1);
	END LOOP;

	FOR idx IN array_lower(usage_array, 1)..array_upper(usage_array, 1) LOOP
		INSERT INTO terms (term, language_id, created_on, created_by_id, updated_on, updated_by_id)
		VALUES (usage_array[idx], (SELECT id from languages WHERE iso = 'eng'), now(), 1, now(), 1);
		m_id := nextval('meanings_id_seq'::regclass);
		INSERT INTO meanings VALUES (m_id);
		INSERT INTO termlists_terms (term_id, termlist_id, meaning_id, created_on, created_by_id, updated_on, updated_by_id)
		VALUES ((SELECT id FROM terms WHERE term = usage_array[idx]),
			(SELECT id FROM termlists WHERE title = 'Site_Usages'),
			m_id, now(), 1, now(), 1);
	END LOOP;
	RETURN 1;
END
$$ LANGUAGE plpgsql;

SELECT ttm();
DROP FUNCTION ttm();
	
