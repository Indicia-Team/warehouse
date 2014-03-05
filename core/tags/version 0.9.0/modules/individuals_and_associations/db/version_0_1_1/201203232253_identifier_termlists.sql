-- Termlists for Groups and Individuals module

-- SET search_path TO ind01,public;

-- DELETE FROM termlists_terms WHERE termlist_id  IN (SELECT id FROM termlists WHERE external_key = 'indicia:assoc:ring_colour');
-- DELETE FROM meanings WHERE id NOT IN (SELECT meaning_id FROM termlists_terms);
-- DELETE FROM terms WHERE id NOT IN (SELECT term_id FROM termlists_terms);
-- DELETE FROM termlists WHERE external_key = 'indicia:assoc:ring_colour';

-- ring colour additional values (from BTO standards)

SELECT insert_term('Red', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Pale Blue', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Orange', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Dark Pink (Carmine)', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Light Green (Lime)', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Light Pink', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Blue (Dark)', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Violet/Mauve/Purple', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Silver/Grey', 'eng', 1, null, 'indicia:assoc:ring_colour');
SELECT insert_term('Brown (Umber)', 'eng', 1, null, 'indicia:assoc:ring_colour');

