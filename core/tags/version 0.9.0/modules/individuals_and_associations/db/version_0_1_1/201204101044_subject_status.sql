-- Additional and revised values for 'life status' (subject status) termlist for Groups and Individuals module

-- SET search_path TO ind01,public;

-- life status/subject status

UPDATE terms SET term = 'Seen alive'
WHERE id IN (
SELECT term_id FROM termlists_terms tlt
JOIN termlists tl ON tl.id = tlt.termlist_id AND tl.external_key = 'indicia:assoc:life_status'
) AND term = 'Alive';
UPDATE terms SET term = 'Found dead'
WHERE id IN (
SELECT term_id FROM termlists_terms tlt
JOIN termlists tl ON tl.id = tlt.termlist_id AND tl.external_key = 'indicia:assoc:life_status'
) AND term = 'Dead';

UPDATE termlists_terms SET sort_order = 20
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:life_status'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Seen alive'
);
UPDATE termlists_terms SET sort_order = 500
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:life_status'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Found dead'
);

SELECT insert_term('First ringed', 'eng', 10, null, 'indicia:assoc:life_status');
SELECT insert_term('Retrapped', 'eng', 30, null, 'indicia:assoc:life_status');
SELECT insert_term('Re-ringed', 'eng', 40, null, 'indicia:assoc:life_status');
SELECT insert_term('New re-ring', 'eng', 50, null, 'indicia:assoc:life_status');
SELECT insert_term('Controlled', 'eng', 60, null, 'indicia:assoc:life_status');
SELECT insert_term('Injured, in captivity', 'eng', 200, null, 'indicia:assoc:life_status');
SELECT insert_term('Shot dead', 'eng', 510, null, 'indicia:assoc:life_status');

