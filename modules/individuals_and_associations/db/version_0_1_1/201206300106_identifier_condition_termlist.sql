-- Termlists for Groups and Individuals module

-- SET search_path TO ind01,public;

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:identifier_condition');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:identifier_condition';

-- identifier condition

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Identifier Condition', 'Lookup list of identifier conditions (problems and remedies) noted during subject_observations in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:identifier_condition');

SELECT insert_term('Chipped', 'eng', 10, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Cracked', 'eng', 20, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Gun hole', 'eng', 30, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Leg ring lost', 'eng', 40, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Ring above tarsus', 'eng', 50, null, 'indicia:iassoc:dentifier_condition');
SELECT insert_term('Ring over web', 'eng', 60, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Sprung', 'eng', 70, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Stained/faded', 'eng', 80, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Transmitter retrieved', 'eng', 90, null, 'indicia:identifier_condition');
SELECT insert_term('Twisted', 'eng', 100, null, 'indicia:assoc:identifier_condition');
SELECT insert_term('Unknown', 'eng', 110, null, 'indicia:assoc:identifier_condition');

