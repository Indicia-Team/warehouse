ALTER TABLE milestones ALTER COLUMN success_message TYPE character varying;
ALTER TABLE milestones ADD COLUMN awarded_by character varying(100) NOT NULL;
COMMENT ON COLUMN milestones.awarded_by IS 'Person or organisation which is awarding this milestone, e.g. the recording scheme associated with the survey.';

CREATE OR REPLACE VIEW gv_milestones AS 
SELECT m.id, m.title, m.count,m.entity,m.website_id,m.filter_id,m.awarded_by
FROM milestones m
WHERE m.deleted = false;
