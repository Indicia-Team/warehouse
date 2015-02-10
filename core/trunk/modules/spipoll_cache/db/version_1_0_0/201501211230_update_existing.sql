CREATE OR REPLACE FUNCTION update_existing_id_tool_data() RETURNS void AS $$
DECLARE
  collectionRow samples%ROWTYPE;
  sessionRow    samples%ROWTYPE;
  occRow        occurrences%ROWTYPE;
  surveyID      integer := 2;
  occurrenceID  integer;
BEGIN
  FOR occurrenceID IN SELECT distinct(occurrence_id) FROM determinations WHERE taxon_details IS NOT NULL AND deleted = false AND created_on >= CAST('2014-06-01' as date)
  LOOP
   SELECT * INTO occRow FROM occurrences WHERE id=occurrenceID;
   IF FOUND THEN
    SELECT * INTO sessionRow FROM samples WHERE id=occRow.sample_id AND survey_id = surveyID;
    IF FOUND THEN
     --- Eliminate flowers
     IF sessionRow.parent_id IS NOT NULL THEN
      SELECT * INTO collectionRow FROM samples WHERE id=sessionRow.parent_id AND survey_id = surveyID AND parent_id IS NULL;
      IF FOUND THEN
       perform update_spipoll_cache_entry(collectionRow, false);
      END IF;
     END IF;
    END IF;
   END IF;
  END LOOP;
END;
$$ LANGUAGE plpgsql;

select * from update_existing_id_tool_data();

DROP FUNCTION update_existing_id_tool_data() ;
