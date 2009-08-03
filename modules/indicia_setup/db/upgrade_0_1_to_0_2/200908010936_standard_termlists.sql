-- Add a static identifier for a termlist that is globally unique.
ALTER TABLE termlists ADD external_key character varying(50);

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Sample methods', 'Top level list of sampling methods.', now(), 1, now(), 1, 'indicia:sample_methods');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Location types', 'Top level list of location types.', now(), 1, now(), 1, 'indicia:location_types');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('DAFOR scale', 'Top level list DAFOR abundance measurement values.', now(), 1, now(), 1, 'indicia:dafor');

