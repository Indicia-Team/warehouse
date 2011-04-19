-- Create a default admin user for the core module.
ALTER TABLE users DROP CONSTRAINT fk_user_person;

INSERT INTO users (person_id, username, created_on, created_by_id, updated_on, updated_by_id)
VALUES (1, 'admin', now(), 1, now(), 1);

INSERT INTO people (surname, first_name, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('admin', 'core', now(), 1, now(), 1);

ALTER TABLE users
  ADD CONSTRAINT fk_user_person FOREIGN KEY (person_id)
      REFERENCES people (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;


-- Create the core roles required
INSERT INTO core_roles (title, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('CoreAdmin', now(), 1, now(), 1);

UPDATE users
SET core_role_id=core_roles.id
FROM core_roles
WHERE users.id=(select min(id) from users) AND core_roles.title='CoreAdmin';


-- Insert an initial set of languages
INSERT INTO languages (iso, language, created_by_id, created_on, updated_by_id, updated_on)
VALUES ('eng', 'English', 1, now(), 1, now());

INSERT INTO languages (iso, language, created_by_id, created_on, updated_by_id, updated_on)
VALUES ('lat', 'Latin', 1, now(), 1, now());

INSERT INTO languages (iso, language, created_by_id, created_on, updated_by_id, updated_on)
VALUES ('cym', 'Welsh', 1, now(), 1, now());

INSERT INTO languages (iso, language, created_by_id, created_on, updated_by_id, updated_on)
VALUES ('gla', 'Gaelic', 1, now(), 1, now());

INSERT INTO languages (iso, language, created_by_id, created_on, updated_by_id, updated_on)
VALUES ('gle', 'Irish', 1, now(), 1, now());


-- Create the site roles required
INSERT INTO site_roles (title, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Admin', now(), 1, now(), 1);
INSERT INTO site_roles (title, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('User', now(), 1, now(), 1);
INSERT INTO site_roles (title, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Editor', now(), 1, now(), 1);


-- Create some termlists and attributes used by the demonstration site pages
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
VALUES ('Site Usages', 'How the site in question is used.', now(), 1, now(), 1);

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
    INSERT INTO termlists_terms (term_id, termlist_id, meaning_id, preferred, created_on, created_by_id, updated_on, updated_by_id)
    VALUES ((SELECT id FROM terms WHERE term = dafor_array[idx]),
      (SELECT id FROM termlists WHERE title = 'DAFOR'),
      m_id, 't', now(), 1, now(), 1);
  END LOOP;

  FOR idx IN array_lower(surroundings_array, 1)..array_upper(surroundings_array, 1) LOOP
    INSERT INTO terms (term, language_id, created_on, created_by_id, updated_on, updated_by_id)
    VALUES (surroundings_array[idx], (SELECT id from languages WHERE iso = 'eng'), now(), 1, now(), 1);
    m_id := nextval('meanings_id_seq'::regclass);
    INSERT INTO meanings VALUES (m_id);
    INSERT INTO termlists_terms (term_id, termlist_id, meaning_id, preferred, created_on, created_by_id, updated_on, updated_by_id)
    VALUES ((SELECT id FROM terms WHERE term = surroundings_array[idx]),
      (SELECT id FROM termlists WHERE title = 'Surroundings'),
      m_id, 't', now(), 1, now(), 1);
  END LOOP;

  FOR idx IN array_lower(usage_array, 1)..array_upper(usage_array, 1) LOOP
    INSERT INTO terms (term, language_id, created_on, created_by_id, updated_on, updated_by_id)
    VALUES (usage_array[idx], (SELECT id from languages WHERE iso = 'eng'), now(), 1, now(), 1);
    m_id := nextval('meanings_id_seq'::regclass);
    INSERT INTO meanings VALUES (m_id);
    INSERT INTO termlists_terms (term_id, termlist_id, meaning_id, preferred, created_on, created_by_id, updated_on, updated_by_id)
    VALUES ((SELECT id FROM terms WHERE term = usage_array[idx]),
      (SELECT id FROM termlists WHERE title = 'Site_Usages'),
      m_id, 't', now(), 1, now(), 1);
  END LOOP;
  RETURN 1;
END
$$ LANGUAGE plpgsql;

SELECT ttm();
DROP FUNCTION ttm();

INSERT INTO occurrence_attributes (caption, data_type, termlist_id, validation_rules, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Abundance Dafor', 'L', (SELECT id FROM termlists WHERE title='DAFOR'), '', true, now(), 1, now(), 1);

INSERT INTO sample_attributes (caption, data_type, termlist_id, validation_rules, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Surroundings', 'L', (SELECT id FROM termlists WHERE title='Surroundings'), '', true, now(), 1, now(), 1);

INSERT INTO sample_attributes (caption, data_type, termlist_id, validation_rules, multi_value, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Site Usage', 'L', (SELECT id FROM termlists WHERE title='Site_Usages'), '', true, true, now(), 1, now(), 1);


-- Create the default contents of the titles tables
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (1, 'Mr', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (2, 'Mrs', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (3, 'Miss', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (4, 'Ms', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (5, 'Master', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (6, 'Dr', now(), 1, now(), 1);


-- Create an unknown person
INSERT INTO people (first_name, surname, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('','Unknown', now(), 1, now(), 1);

-- Put data in for the demonstration pages
INSERT INTO websites (title, url, password, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Demonstration Website', 'http://localhost/', 'password', now(), 1, now(), 1);

INSERT INTO surveys (title, description, website_id, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Demonstration Survey', 'Survey created for the demonstration data capture pages', 1,
  now(), 1, now(), 1);