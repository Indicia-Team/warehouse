-- More Termlists for Groups and Individuals module

-- SET search_path TO ind01,public;

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:attachment_type');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:attachment_type';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:gender');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:gender';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:stage');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:stage';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:life_status');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:life_status';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:identifier_position');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:identifier_position';

-- identifier type

UPDATE termlists SET title = 'Identifier Type', external_key = 'indicia:assoc:identifier_type' 
  WHERE external_key = 'indicia:assoc:mark_type';

-- attachment type

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Attachment Type', 'Lookup list of types of attachable devices available for known_subjects and subject_observations in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:attachment_type');

UPDATE termlists_terms SET termlist_id = (
  SELECT tl.id 
  FROM termlists tl 
  WHERE tl.external_key = 'indicia:assoc:attachment_type' 
)
WHERE id IN (
  SELECT tlt.id 
  FROM termlists_terms tlt 
  JOIN termlists tl ON tlt.termlist_id = tl.id AND tl.external_key = 'indicia:assoc:identifier_type' 
  JOIN terms t ON tlt.term_id = t.id AND t.term IN ('Data logger', 'Radio telemetry', 'Satellite tracking')
);

-- gender

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Gender', 'Lookup list of genders available for known_subjects and subject_observations in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:gender');

SELECT insert_term('Male', 'eng', 1, null, 'indicia:assoc:gender');
SELECT insert_term('Female', 'eng', 1, null, 'indicia:assoc:gender');
SELECT insert_term('Hermaphrodite', 'eng', 1, null, 'indicia:assoc:gender');
SELECT insert_term('Unknown', 'eng', 1, null, 'indicia:assoc:gender');

-- age/stage class

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Stage/Age Group', 'Lookup list of stage / age classifications in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:stage');

SELECT insert_term('Pullus', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('Juvenile', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('1st winter', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('1st summer', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('2nd winter', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('2nd summer', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('3rd winter', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('3rd summer', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('4th winter', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('1st calendar year', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('2nd calendar year', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('3rd calendar year', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('4th calendar year', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('Adult', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('Adult summer', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('Adult winter', 'eng', 1, null, 'indicia:assoc:stage');
SELECT insert_term('Unknown', 'eng', 1, null, 'indicia:assoc:stage');

-- life status

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Life Status', 'Lookup list of life status classifications in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:life_status');

SELECT insert_term('Alive', 'eng', 1, null, 'indicia:assoc:life_status');
SELECT insert_term('Dead', 'eng', 1, null, 'indicia:assoc:life_status');
SELECT insert_term('Unknown', 'eng', 1, null, 'indicia:assoc:life_status');

-- identifier position

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Identifier Position', 'Lookup list of identifier positions, such as ''left leg'' in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:identifier_position');

SELECT insert_term('Bill', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Neck', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Left wing', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Right wing', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Left', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Right', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Left leg', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Right leg', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Left below ''knee''', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Left above ''knee''', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Right below ''knee''', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Right above ''knee''', 'eng', 1, null, 'indicia:assoc:identifier_position');
SELECT insert_term('Unknown', 'eng', 1, null, 'indicia:assoc:identifier_position');

