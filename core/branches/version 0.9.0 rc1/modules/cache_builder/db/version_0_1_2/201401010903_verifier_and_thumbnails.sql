UPDATE cache_occurrences co
SET verifier=p.surname || ', ' || p.first_name
FROM occurrences o
JOIN users u ON u.id=o.verified_by_id AND u.deleted=false
JOIN people p ON p.id=u.person_id AND p.deleted=false
WHERE o.id=co.id AND co.verifier IS NULL;

UPDATE cache_occurrences co
SET images=images.list
FROM (
  select occurrence_id, 
  array_to_string(array_agg(path), ',') AS list
  FROM occurrence_images
  WHERE deleted=false
  GROUP BY occurrence_id
) AS images
WHERE images.occurrence_id=co.id AND co.images IS NULL;