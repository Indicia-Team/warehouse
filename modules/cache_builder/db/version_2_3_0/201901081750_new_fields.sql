ALTER TABLE cache_occurrences_functional
  ADD COLUMN parent_sample_id integer,
  ADD COLUMN verification_checks_enabled boolean not null default false;

ALTER TABLE cache_samples_functional
  ADD COLUMN parent_sample_id integer;