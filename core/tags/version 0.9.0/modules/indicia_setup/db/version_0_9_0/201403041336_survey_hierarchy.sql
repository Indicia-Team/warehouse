ALTER TABLE surveys ADD COLUMN parent_id integer;

ALTER TABLE surveys ADD CONSTRAINT fk_survey_parent FOREIGN KEY (parent_id) REFERENCES surveys (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN surveys.parent_id IS 'Identifies the survey''s parent survey, if there is one.';

CREATE OR REPLACE VIEW detail_surveys AS 
 SELECT s.id, s.title, s.owner_id, p.surname AS owner, s.description, s.website_id, w.title AS website, s.created_by_id, c.username AS created_by, s.updated_by_id, u.username AS updated_by, s.parent_id
   FROM surveys s
   JOIN users c ON c.id = s.created_by_id
   JOIN users u ON u.id = s.updated_by_id
   LEFT JOIN people p ON p.id = s.owner_id AND p.deleted = false
   JOIN websites w ON w.id = s.website_id AND w.deleted = false
  WHERE s.deleted = false;