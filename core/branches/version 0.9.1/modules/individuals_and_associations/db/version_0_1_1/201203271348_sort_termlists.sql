-- More Termlists for Groups and Individuals module

-- SET search_path TO ind01,public;

-- gender

UPDATE termlists_terms SET sort_order = 10 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:gender'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Male'
);
UPDATE termlists_terms SET sort_order = 20 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:gender'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Female'
);
UPDATE termlists_terms SET sort_order = 30 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:gender'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Hermaphrodite'
);
UPDATE termlists_terms SET sort_order = 1000 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:gender'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Unknown'
);

-- age/stage class

UPDATE termlists_terms SET sort_order = 10 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Pullus'
);
UPDATE termlists_terms SET sort_order = 20 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Juvenile'
);
UPDATE termlists_terms SET sort_order = 30 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '1st calendar year'
);
UPDATE termlists_terms SET sort_order = 40 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '1st winter'
);
UPDATE termlists_terms SET sort_order = 50 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '2nd calendar year'
);
UPDATE termlists_terms SET sort_order = 60 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '1st summer'
);
UPDATE termlists_terms SET sort_order = 70 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '2nd winter'
);
UPDATE termlists_terms SET sort_order = 80 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '3rd calendar year'
);
UPDATE termlists_terms SET sort_order = 90 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '2nd summer'
);
UPDATE termlists_terms SET sort_order = 100 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '3rd winter'
);
UPDATE termlists_terms SET sort_order = 110 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '4th calendar year'
);
UPDATE termlists_terms SET sort_order = 120 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '3rd summer'
);
UPDATE termlists_terms SET sort_order = 130 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = '4th winter'
);
UPDATE termlists_terms SET sort_order = 500 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Adult'
);
UPDATE termlists_terms SET sort_order = 510 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Adult summer'
);
UPDATE termlists_terms SET sort_order = 520 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Adult winter'
);
UPDATE termlists_terms SET sort_order = 1000 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:stage'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Unknown'
);

-- life status

UPDATE termlists_terms SET sort_order = 10
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:life_status'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Alive'
);
UPDATE termlists_terms SET sort_order = 20
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:life_status'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Dead'
);
UPDATE termlists_terms SET sort_order = 1000 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:life_status'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Unknown'
);

-- identifier position

UPDATE termlists_terms SET sort_order = 10
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Bill'
);
UPDATE termlists_terms SET sort_order = 20
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Neck'
);
UPDATE termlists_terms SET sort_order = 30
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Left'
);
UPDATE termlists_terms SET sort_order = 40
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Right'
);
UPDATE termlists_terms SET sort_order = 50
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Left wing'
);
UPDATE termlists_terms SET sort_order = 60
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Right wing'
);
UPDATE termlists_terms SET sort_order = 70
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Left leg'
);
UPDATE termlists_terms SET sort_order = 80
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Right leg'
);
UPDATE termlists_terms SET sort_order = 90
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Left above ''knee'''
);
UPDATE termlists_terms SET sort_order = 100
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Left below ''knee'''
);
UPDATE termlists_terms SET sort_order = 110
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Right above ''knee'''
);
UPDATE termlists_terms SET sort_order = 120
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Right below ''knee'''
);
UPDATE termlists_terms SET sort_order = 1000 
WHERE termlist_id = (
SELECT id FROM termlists
WHERE external_key = 'indicia:assoc:identifier_position'
) AND term_id IN (
SELECT id FROM terms
WHERE term = 'Unknown'
);


