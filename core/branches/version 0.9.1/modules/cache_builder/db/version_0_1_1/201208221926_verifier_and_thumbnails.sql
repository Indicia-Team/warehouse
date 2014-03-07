-- Column: verifier

ALTER TABLE cache_occurrences ADD COLUMN verifier character varying;
COMMENT ON COLUMN cache_occurrences.verifier IS 'Name of the record''s verifier, if any.';

-- Column: images

ALTER TABLE cache_occurrences ADD COLUMN images character varying;
COMMENT ON COLUMN cache_occurrences.images IS 'Comma separated list of image paths for this occurrence''s images.';

UPDATE cache_occurrences co
SET verifier=p.surname || ', ' || p.first_name
FROM occurrences o
JOIN users u ON u.id=o.verified_by_id AND u.deleted=false
JOIN people p ON p.id=u.person_id AND p.deleted=false
WHERE o.id=co.id;

UPDATE cache_occurrences co
SET images=images.list
FROM (select occurrence_id, 
array_to_string(array_agg(path), ',') as list
from occurrence_images
where deleted=false
group by occurrence_id) as images
where images.occurrence_id=co.id;