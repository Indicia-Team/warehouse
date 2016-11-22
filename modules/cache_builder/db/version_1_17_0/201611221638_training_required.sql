-- #slow script#
UPDATE occurrences SET training=false WHERE training IS NULL;
UPDATE cache_occurrences_functional SET training=false WHERE training IS NULL;

ALTER TABLE occurrences
   ALTER COLUMN training SET DEFAULT false;
ALTER TABLE occurrences
   ALTER COLUMN training SET NOT NULL;

ALTER TABLE cache_occurrences_functional
   ALTER COLUMN training SET DEFAULT false;
ALTER TABLE cache_occurrences_functional
   ALTER COLUMN training SET NOT NULL;
