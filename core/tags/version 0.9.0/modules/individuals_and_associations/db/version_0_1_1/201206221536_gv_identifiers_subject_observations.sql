
CREATE OR REPLACE VIEW gv_identifiers_subject_observations AS 
  SELECT iso.id, iso.subject_observation_id, iso.identifier_id, i.coded_value, 
    CASE iso.verified_status 
      WHEN 'M' THEN 'Unknown'
      WHEN 'V' THEN 'Verified'
      ELSE 'Unknown' 
    END as verified_status   
 FROM identifiers_subject_observations iso
 JOIN identifiers i on i.id=iso.identifier_id and i.deleted=false
 WHERE iso.deleted=false