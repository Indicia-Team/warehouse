-- #slow script#

-- Disable tracking increments, so doesn't force a complete ES refresh.
SET application_name = 'skiptrigger';

ALTER TABLE cache_samples_functional
ALTER COLUMN hide_sample_as_private SET DEFAULT false;

ALTER TABLE cache_occurrences_functional
ALTER COLUMN hide_sample_as_private SET DEFAULT false;

UPDATE cache_samples_functional SET hide_sample_as_private=false;

UPDATE cache_occurrences_functional SET hide_sample_as_private=false;

ALTER TABLE cache_samples_functional
ALTER COLUMN hide_sample_as_private SET NOT NULL;

ALTER TABLE cache_occurrences_functional
ALTER COLUMN hide_sample_as_private SET NOT NULL;