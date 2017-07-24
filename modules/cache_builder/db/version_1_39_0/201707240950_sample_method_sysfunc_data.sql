-- #slow script#

UPDATE cache_samples_nonfunctional
SET attr_sample_method = t.term
FROM samples s
JOIN cache_termlists_terms t on t.id=s.sample_method_id;