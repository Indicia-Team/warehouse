-- Termlists for Groups and Individuals module

-- SET search_path TO ind01,public;

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:subject_type');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:subject_type';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:count_qualifier');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:count_qualifier';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:mark_type');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:mark_type';

-- Subject type

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Subject type', 'Lookup list of subject types available for known_subjects and subject_observations in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:subject_type');

SELECT insert_term('Group', 'eng', 1, null, 'indicia:assoc:subject_type');
SELECT insert_term('Family', 'eng', 1, null, 'indicia:assoc:subject_type');
SELECT insert_term('Pair', 'eng', 1, null, 'indicia:assoc:subject_type');
SELECT insert_term('Individual', 'eng', 1, null, 'indicia:assoc:subject_type');

-- Count qualifier

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Count qualifier', 'Lookup list of count qualifiers which may be associated with group counts to aid their interpretation.', now(), 1, now(), 1, 'indicia:assoc:count_qualifier');

SELECT insert_term('Approximate', 'eng', 1, null, 'indicia:assoc:count_qualifier');
SELECT insert_term('Exact', 'eng', 1, null, 'indicia:assoc:count_qualifier');
SELECT insert_term('Minimum', 'eng', 1, null, 'indicia:assoc:count_qualifier');
SELECT insert_term('Maximum', 'eng', 1, null, 'indicia:assoc:count_qualifier');
SELECT insert_term('Multiplied count', 'eng', 1, null, 'indicia:assoc:count_qualifier');


-- mark type

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Mark Type', 'Lookup list of mark types available for known_subjects and subject_observations in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:mark_type');

SELECT insert_term('Metal ring', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Colour ring', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Darvic ring', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Leg flag', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Neck collar', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Nasal saddle', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Wing tag', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Radio telemetry', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Satellite tracking', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Data logger', 'eng', 1, null, 'indicia:assoc:mark_type');
SELECT insert_term('Distinctive marks', 'eng', 1, null, 'indicia:assoc:mark_type');


