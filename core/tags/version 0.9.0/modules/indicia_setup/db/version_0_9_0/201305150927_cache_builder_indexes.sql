-- Indexes which improve the cache builder performance as it needs to check for changed records
DROP INDEX IF EXISTS ix_termlists_terms_updated_on;
CREATE INDEX ix_termlists_terms_updated_on ON termlists_terms(updated_on);

DROP INDEX IF EXISTS ix_terms_updated_on;
CREATE INDEX ix_terms_updated_on ON terms(updated_on);

DROP INDEX IF EXISTS ix_termlists_updated_on;
CREATE INDEX ix_termlists_updated_on ON termlists(updated_on);

DROP INDEX IF EXISTS ix_taxa_taxon_lists_updated_on;
CREATE INDEX ix_taxa_taxon_lists_updated_on ON taxa_taxon_lists(updated_on);

DROP INDEX IF EXISTS ix_taxa_updated_on;
CREATE INDEX ix_taxa_updated_on ON taxa(updated_on);

DROP INDEX IF EXISTS ix_taxon_lists_updated_on;
CREATE INDEX ix_taxon_lists_updated_on ON taxon_lists(updated_on);

DROP INDEX IF EXISTS ix_taxon_groups_updated_on;
CREATE INDEX ix_taxon_groups_updated_on ON taxon_groups(updated_on);

DROP INDEX IF EXISTS ix_languages_updated_on;
CREATE INDEX ix_languages_updated_on ON languages(updated_on);

DROP INDEX IF EXISTS ix_occurrences_updated_on;
CREATE INDEX ix_occurrences_updated_on ON occurrences(updated_on);

DROP INDEX IF EXISTS ix_samples_updated_on;
CREATE INDEX ix_samples_updated_on ON samples(updated_on);

DROP INDEX IF EXISTS ix_surveys_updated_on;
CREATE INDEX ix_surveys_updated_on ON surveys(updated_on);

DROP INDEX IF EXISTS ix_occurrence_images_updated_on;
CREATE INDEX ix_occurrence_images_updated_on ON occurrence_images(updated_on);