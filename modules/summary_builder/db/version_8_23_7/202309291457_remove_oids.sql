-- Remove OIDS for compatibility with recent PG versions.
ALTER TABLE summary_occurrences SET WITHOUT OIDS;