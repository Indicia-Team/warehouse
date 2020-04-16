-- #slow script#

UPDATE cache_occurrences_functional co
  SET confidential=true
FROM occurrences o
WHERE o.id=co.id
AND o.confidential=true;