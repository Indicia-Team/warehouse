DROP TRIGGER trigger_cache_occurrences_functional_changed ON cache_occurrences_functional;
CREATE TRIGGER trigger_cache_occurrences_functional_changed
    BEFORE INSERT OR UPDATE
    ON indicia.cache_occurrences_functional
    FOR EACH ROW WHEN (current_setting('application_name') <> 'skiptrigger')
    EXECUTE PROCEDURE indicia.cache_functional_changed();