-- Add detail views for join tables 
-- identifiers_subject_observations
-- occurrences_subject_observations

-- View: detail_identifiers_subject_observations

-- DROP VIEW detail_identifiers_subject_observations;

CREATE OR REPLACE VIEW detail_identifiers_subject_observations AS 
 SELECT iso.id, iso.identifier_id, iso.subject_observation_id, iso.matched, iso.verified_status, iso.verified_by_id, v.username AS verified_by, iso.verified_on, iso.created_by_id, c.username AS created_by, iso.created_on, iso.updated_by_id, u.username AS updated_by, iso.updated_on, i.website_id, w.title AS website
   FROM identifiers_subject_observations iso
   JOIN identifiers i ON i.id = iso.identifier_id
   LEFT JOIN users v ON v.id = iso.verified_by_id
   JOIN users c ON c.id = iso.created_by_id
   JOIN users u ON u.id = iso.updated_by_id
   JOIN websites w ON w.id = i.website_id
  WHERE iso.deleted = false;

-- View: detail_occurrences_subject_observations

-- DROP VIEW detail_occurrences_subject_observations;

CREATE OR REPLACE VIEW detail_occurrences_subject_observations AS 
 SELECT oso.id, oso.occurrence_id, oso.subject_observation_id, oso.created_by_id, c.username AS created_by, oso.created_on, o.website_id, w.title AS website
   FROM occurrences_subject_observations oso
   JOIN occurrences o ON o.id = oso.occurrence_id
   JOIN users c ON c.id = oso.created_by_id
   JOIN websites w ON w.id = o.website_id
  WHERE oso.deleted = false;


