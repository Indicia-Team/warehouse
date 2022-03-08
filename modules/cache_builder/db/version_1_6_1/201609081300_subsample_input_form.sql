-- #slow script#

UPDATE cache_samples_functional s_update
SET input_form=COALESCE(sp.input_form, s.input_form)
FROM samples s
LEFT JOIN samples sp ON sp.id=s.parent_id AND sp.deleted=false
WHERE s.id=s_update.id
AND s.deleted=false
AND s_update.input_form IS null;

UPDATE cache_occurrences_functional o_update
SET input_form=COALESCE(sp.input_form, s.input_form)
FROM occurrences o
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND sp.deleted=false
WHERE o_update.id=o.id 
AND o_update.input_form IS null;
