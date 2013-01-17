-- Termlists for Groups and Individuals module

-- SET search_path TO ind01,public;

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:identifier_type');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:identifier_type';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:issue_authority');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:issue_authority';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:issue_scheme');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:issue_scheme';

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:ring_colour');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:ring_colour';

-- identifier type

UPDATE termlists SET title = 'Identifier Type', external_key = 'indicia:assoc:identifier_type' 
  WHERE external_key = 'indicia:assoc:mark_type';

-- issue authority

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Issue Authority', 'Lookup list of identifier issue authorities which manage identifier schemes available for known_subjects and subject_observations in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:issue_authority');

SELECT insert_term('WWT', 'eng', 1, null, 'indicia:assoc:issue_authority');
SELECT insert_term('BTO', 'eng', 1, null, 'indicia:assoc:issue_authority');

-- issue scheme

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Issue Scheme', 'Lookup list of identifier issue schemes available for known_subjects and subject_observations in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:issue_scheme');

SELECT insert_term('Bewick''s Swan colour-marking', 'eng', 1, null, 'indicia:assoc:issue_scheme');
UPDATE termlists_terms SET parent_id = (
  SELECT tlt.id 
  FROM termlists_terms tlt 
  JOIN termlists tl ON tlt.termlist_id = tl.id AND tl.external_key = 'indicia:assoc:issue_authority' 
  JOIN terms t ON tlt.term_id = t.id AND t.term = 'WWT'
)
WHERE id = (
  SELECT tlt.id 
  FROM termlists_terms tlt 
  JOIN termlists tl ON tlt.termlist_id = tl.id AND tl.external_key = 'indicia:assoc:issue_scheme' 
  JOIN terms t ON tlt.term_id = t.id AND t.term = 'Bewick''s Swan colour-marking'
);
SELECT insert_term('Whooper Swan colour-marking', 'eng', 1, null, 'indicia:assoc:issue_scheme');
UPDATE termlists_terms SET parent_id = (
  SELECT tlt.id 
  FROM termlists_terms tlt 
  JOIN termlists tl ON tlt.termlist_id = tl.id AND tl.external_key = 'indicia:assoc:issue_authority' 
  JOIN terms t ON tlt.term_id = t.id AND t.term = 'WWT'
)
WHERE id = (
  SELECT tlt.id 
  FROM termlists_terms tlt 
  JOIN termlists tl ON tlt.termlist_id = tl.id AND tl.external_key = 'indicia:assoc:issue_scheme' 
  JOIN terms t ON tlt.term_id = t.id AND t.term = 'Whooper Swan colour-marking'
);

-- ring colour

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Ring Colour', 'Lookup list of ring colours for identifiers in the Groups and Individuals module.', now(), 1, now(), 1, 'indicia:assoc:ring_colour');

SELECT insert_term('Black', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Green', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('White', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Yellow', 'eng', 1, null, 'indicia:assoc:ring_colour');

