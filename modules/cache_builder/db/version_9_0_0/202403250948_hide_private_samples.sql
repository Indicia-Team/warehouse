ALTER TABLE cache_samples_functional
ADD COLUMN IF NOT EXISTS hide_sample_as_private boolean;

ALTER TABLE cache_occurrences_functional
ADD COLUMN IF NOT EXISTS hide_sample_as_private boolean;