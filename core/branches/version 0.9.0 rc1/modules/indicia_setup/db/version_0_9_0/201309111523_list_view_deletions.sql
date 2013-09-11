CREATE OR REPLACE VIEW list_determinations AS 
 SELECT d.id, d.taxa_taxon_list_id, t.taxon, d.comment, d.taxon_extra_info, d.occurrence_id, d.taxon_details, d.taxa_taxon_list_id_list, d.email_address, d.person_name, d.cms_ref, d.deleted, d.updated_on, d.determination_type, o.website_id
   FROM determinations d
   JOIN occurrences o ON d.occurrence_id = o.id AND o.deleted=false
   LEFT JOIN taxa_taxon_lists ttl ON d.taxa_taxon_list_id = ttl.id AND ttl.deleted=false
   LEFT JOIN taxa t ON ttl.taxon_id = t.id AND t.deleted=false
   WHERE d.deleted=false;

CREATE OR REPLACE VIEW list_location_images AS 
 SELECT li.id, li.location_id, li.path, li.caption, li.created_on, li.created_by_id, li.updated_on, li.updated_by_id, li.deleted, lw.website_id
   FROM location_images li
   JOIN locations l ON l.id = li.location_id AND l.deleted=false
   LEFT JOIN locations_websites lw ON l.id = lw.location_id AND lw.deleted=false
   WHERE li.deleted=false
  ORDER BY li.id;

CREATE OR REPLACE VIEW list_samples AS 
 SELECT s.id, su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, su.website_id
   FROM samples s
   LEFT JOIN locations l ON s.location_id = l.id AND l.deleted=false
   JOIN surveys su ON s.survey_id = su.id AND su.deleted=false
   WHERE s.deleted=false;

CREATE OR REPLACE VIEW list_surveys AS 
 SELECT s.id, s.title, s.website_id
   FROM surveys s
   WHERE s.deleted=false;

CREATE OR REPLACE VIEW list_terms AS 
 SELECT t.id, t.term, t.language_id, l.language, l.iso, NULL::integer AS website_id
   FROM terms t
   JOIN languages l ON l.id = t.language_id AND l.deleted=false
   WHERE t.deleted=false;

CREATE OR REPLACE VIEW list_user_trusts AS 
 SELECT ut.id, ut.user_id, ut.survey_id, ut.location_id, ut.taxon_group_id, ut.created_on, ut.created_by_id, ut.updated_on, ut.updated_by_id, ut.deleted
   FROM user_trusts ut
   WHERE ut.deleted=false;