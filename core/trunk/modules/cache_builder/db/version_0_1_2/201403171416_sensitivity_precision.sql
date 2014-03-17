ALTER TABLE cache_occurrences
   ADD COLUMN sensitivity_precision integer;

UPDATE cache_occurrences co
SET sensitivity_precision=o.sensitivity_precision
FROM occurrences o 
WHERE o.id=co.id AND o.sensitivity_precision IS NOT NULL;
