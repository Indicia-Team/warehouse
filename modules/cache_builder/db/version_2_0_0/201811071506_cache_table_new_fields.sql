-- Add new cache columns. The location_ids[] column was added by a
-- previous script - left there rather than tidy into 1 script to avoid
-- problems with servers which have already run the script to set up
-- location_ids.
ALTER TABLE cache_occurrences_functional
  ADD COLUMN taxon_path integer[],
  ADD COLUMN blocked_sharing_tasks char[];
ALTER TABLE cache_samples_functional
  ADD COLUMN blocked_sharing_tasks char[];

-- Updates will be performed in the next script.