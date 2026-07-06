
-- Add website_id to classification_events table for better user access control.
ALTER TABLE classification_events
ADD COLUMN website_id integer,
ADD CONSTRAINT fk_classification_events_websites FOREIGN KEY (website_id) REFERENCES websites(id);

-- Existing classification events should be linked to an occurrence or determination which has the
-- website ID.
UPDATE classification_events ce
SET website_id = o.website_id
FROM occurrences o
WHERE ce.id = o.classification_event_id;

UPDATE classification_events ce
SET website_id = o.website_id
FROM determinations d
JOIN occurrences o ON o.id = d.occurrence_id
WHERE ce.id = d.classification_event_id;

-- Catch-all using occurrence media in case any weren't correctly saved with the occurrence ID.
UPDATE classification_events ce
SET website_id = o.website_id
FROM classification_results cr
JOIN classification_results_occurrence_media cr_om ON cr_om.classification_result_id = cr.id
JOIN occurrence_media om ON om.id = cr_om.occurrence_media_id
JOIN occurrences o ON o.id = om.occurrence_id
WHERE ce.id = cr.classification_event_id
AND ce.website_id IS NULL;

-- We will rely on model to enforce requiredness of website_id for new classification events, so
-- no need to set NOT NULL constraint here. This allows for existing data which we can't work it
-- out for.