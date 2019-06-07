SELECT insert_term('Image:imgix', 'eng', null, 'indicia:media_types');

UPDATE termlists_terms
SET parent_id=(SELECT id FROM list_termlists_terms WHERE term IN ('Image') AND termlist_external_key='indicia:media_types')
WHERE id IN (SELECT id FROM list_termlists_terms WHERE term IN ('Image:iNaturalist', 'Image:imgex') AND termlist_external_key='indicia:media_types');