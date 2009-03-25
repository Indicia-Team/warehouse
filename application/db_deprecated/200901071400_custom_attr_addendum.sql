INSERT INTO occurrence_attributes (caption, data_type, termlist_id, validation_rules, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Abundance Dafor', 'L', (SELECT id FROM termlists WHERE title='DAFOR'), '', true, now(), 1, now(), 1);

INSERT INTO sample_attributes (caption, data_type, termlist_id, validation_rules, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Surroundings', 'L', (SELECT id FROM termlists WHERE title='Surroundings'), '', true, now(), 1, now(), 1);

INSERT INTO sample_attributes (caption, data_type, termlist_id, validation_rules, multi_value, public, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('Site Usage', 'L', (SELECT id FROM termlists WHERE title='Site_Usages'), '', true, true, now(), 1, now(), 1);
