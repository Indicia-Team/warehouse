DROP VIEW IF EXISTS list_people;
DROP VIEW IF EXISTS detail_people;
DROP VIEW IF EXISTS detail_occurrences;
DROP VIEW IF EXISTS detail_surveys;
DROP VIEW IF EXISTS gv_people;
DROP VIEW IF EXISTS gv_taxon_lists_taxa;
DROP VIEW IF EXISTS gv_termlists;
DROP VIEW IF EXISTS gv_triggers;
DROP VIEW IF EXISTS gv_users;
DROP VIEW IF EXISTS report_people;

ALTER TABLE people ALTER first_name TYPE character varying(50);
ALTER TABLE people ALTER surname TYPE character varying(50);
ALTER TABLE people ALTER email_address TYPE character varying(100);

CREATE OR REPLACE VIEW gv_taxon_lists AS 
 SELECT t.id, t.title, t.website_id, t.parent_id
   FROM taxon_lists t
  WHERE t.deleted = false;

CREATE OR REPLACE VIEW list_languages AS 
 SELECT l.id, l.language, l.iso, NULL::integer AS website_id
   FROM languages l
   WHERE l.deleted=false;
   
CREATE OR REPLACE VIEW gv_languages AS 
 SELECT l.id, l.language, l.iso, NULL::integer AS website_id
   FROM languages l
   WHERE l.deleted=false;

CREATE OR REPLACE VIEW gv_websites AS 
 SELECT w.id, w.title, w.description, w.url, w.id AS website_id
   FROM websites w
   WHERE w.deleted=false;

CREATE OR REPLACE VIEW gv_taxon_groups AS 
 SELECT t.id, t.title, NULL::integer AS website_id
   FROM taxon_groups t
  WHERE t.deleted = false;

CREATE OR REPLACE VIEW gv_locations AS 
 SELECT l.id, l.name, l.code, l.centroid_sref, lw.website_id
   FROM locations l
   LEFT JOIN locations_websites lw ON l.id = lw.location_id
   WHERE l.deleted=false;

CREATE OR REPLACE VIEW list_locations AS 
 SELECT l.id, l.name, l.code, l.centroid_sref, lw.website_id
   FROM locations l
   LEFT JOIN locations_websites lw ON l.id = lw.location_id
   WHERE l.deleted=false;

CREATE OR REPLACE VIEW detail_occurrences AS 
 SELECT o.id, o.confidential, o.comment, o.taxa_taxon_list_id, ttl.taxon_meaning_id, o.record_status, o.determiner_id, t.taxon, s.entered_sref, s.entered_sref_system, s.geom, st_astext(s.geom) AS wkt, s.location_name, s.survey_id, s.date_start, s.date_end, s.date_type, s.location_id, l.name AS location, l.code AS location_code, s.recorder_names, (d.first_name::text || ' '::text) || d.surname::text AS determiner, o.website_id, o.created_by_id, c.username AS created_by, o.created_on, o.updated_by_id, u.username AS updated_by, o.updated_on, o.downloaded_flag, o.sample_id, o.deleted
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id AND s.deleted = false
   LEFT JOIN people d ON d.id = o.determiner_id AND d.deleted=false
   LEFT JOIN locations l ON l.id = s.location_id AND l.deleted=false
   LEFT JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id AND ttl.deleted=false
   LEFT JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted=false
   LEFT JOIN surveys su ON s.survey_id = su.id AND su.deleted = false
   JOIN users c ON c.id = o.created_by_id
   JOIN users u ON u.id = o.updated_by_id
  WHERE o.deleted = false;

CREATE OR REPLACE VIEW gv_occurrence_comments AS 
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, oc.person_name, u.username, o.website_id
   FROM occurrence_comments oc
   JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted=false
   LEFT JOIN users u ON oc.created_by_id = u.id
   WHERE oc.deleted=false;

CREATE OR REPLACE VIEW list_occurrence_comments AS 
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, oc.person_name, u.username, o.website_id
   FROM occurrence_comments oc
   JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted=false
   LEFT JOIN users u ON oc.created_by_id = u.id
   WHERE oc.deleted=false;

CREATE OR REPLACE VIEW gv_occurrence_images AS 
 SELECT occurrence_images.id, occurrence_images.path, occurrence_images.caption, occurrence_images.deleted, occurrence_images.occurrence_id
   FROM occurrence_images
   WHERE deleted=false;

CREATE OR REPLACE VIEW list_occurrence_images AS 
 SELECT oi.id, oi.occurrence_id, oi.path, oi.caption, oi.created_on, oi.created_by_id, oi.updated_on, oi.updated_by_id, oi.deleted, oi.external_details, o.website_id
   FROM occurrence_images oi
   JOIN occurrences o ON o.id = oi.occurrence_id AND o.deleted=false
  WHERE oi.deleted=false;

CREATE OR REPLACE VIEW list_people AS 
         SELECT p.id, p.first_name, p.surname, p.initials, (p.first_name::text || ' '::text) || p.surname::text AS caption, uw.website_id
           FROM people p
   LEFT JOIN users us ON us.person_id = p.id AND us.deleted=false
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
   WHERE p.deleted=false;

CREATE OR REPLACE VIEW gv_people AS 
         SELECT p.id, p.first_name, p.surname, p.initials, (p.first_name::text || ' '::text) || p.surname::text AS caption, uw.website_id
           FROM people p
   LEFT JOIN users us ON us.person_id = p.id AND us.deleted=false
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
   WHERE p.deleted=false;

CREATE OR REPLACE VIEW detail_people AS 
         SELECT p.id, p.first_name, p.surname, p.initials, p.email_address, p.website_url, p.created_by_id, c.username AS created_by, p.updated_by_id, u.username AS updated_by, uw.website_id
           FROM people p
   JOIN users c ON c.id = p.created_by_id
   JOIN users u ON u.id = p.updated_by_id
   LEFT JOIN users us ON us.person_id = p.id
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
   WHERE p.deleted=false;

CREATE OR REPLACE VIEW gv_taxa_taxon_lists AS 
 SELECT tt.id, tt.taxon_list_id, tt.taxon_id, tt.created_on, tt.created_by_id, tt.parent_id, tt.taxon_meaning_id, tt.taxonomic_sort_order, tt.preferred, tt.deleted, t.taxon, t.taxon_group_id, t.language_id, t.authority, t.search_code, t.scientific, l.language, tg.title AS taxon_group
   FROM taxa_taxon_lists tt
   JOIN taxa t ON tt.taxon_id = t.id AND t.deleted=false
   JOIN languages l ON t.language_id = l.id AND l.deleted=false
   JOIN taxon_groups tg ON t.taxon_group_id = tg.id AND tg.deleted=false
   WHERE tt.deleted=false;

CREATE OR REPLACE VIEW list_titles AS 
 SELECT id, title
 FROM titles
 WHERE deleted=false;

CREATE OR REPLACE VIEW gv_titles AS 
 SELECT id, title
 FROM titles
 WHERE deleted=false;

CREATE OR REPLACE VIEW detail_titles AS 
 SELECT t.id, t.title, t.created_by_id, c.username AS created_by, t.updated_by_id, u.username AS updated_by
 FROM titles t
 JOIN users c ON c.id = t.created_by_id
 JOIN users u ON u.id = t.updated_by_id
 WHERE t.deleted=false;

CREATE OR REPLACE VIEW list_websites AS 
 SELECT w.id, w.title, w.id AS website_id
   FROM websites w
   WHERE w.deleted=false;

CREATE OR REPLACE VIEW gv_triggers AS 
 SELECT 
   t.id, t.name, t.description, t.public, COALESCE(p.first_name::text || ' '::text, ''::text) || p.surname::text AS created_by_name, 
   CASE t.public
       WHEN true THEN NULL::integer
       ELSE u.id
   END AS private_for_user_id, t.deleted, logged_in.id as user_id, 
   CASE WHEN logged_in.core_role_id=1 OR u.id=logged_in.id THEN true ELSE false END AS "edit_trigger",
   CASE WHEN ta.id IS NULL THEN true ELSE false END AS "subscribe",
   CASE WHEN ta.id IS NULL THEN false ELSE true END AS "edit_subscription"
   FROM triggers t
   JOIN users u ON u.id = t.created_by_id AND u.deleted = false
   JOIN people p ON p.id = u.person_id AND p.deleted = false
   CROSS JOIN users logged_in
   LEFT JOIN trigger_actions ta ON ta.trigger_id=t.id 
	AND ta.param1 = CAST(logged_in.id AS character varying)
	AND ta.deleted=false
   WHERE t.deleted=false;

CREATE OR REPLACE VIEW report_people AS 
 SELECT p.id, p.first_name, p.surname, p.initials, p.title_id
   FROM people p
  WHERE p.deleted = false;

CREATE OR REPLACE VIEW gv_users AS 
 SELECT p.id AS person_id, COALESCE(p.first_name::text || ' '::text, ''::text) || p.surname::text AS name, u.id, u.username, cr.title AS core_role, p.deleted
   FROM people p
   LEFT JOIN users u ON p.id = u.person_id AND u.deleted = false
   LEFT JOIN core_roles cr ON u.core_role_id = cr.id
  WHERE p.email_address IS NOT NULL
  AND p.deleted=false;

CREATE OR REPLACE VIEW detail_surveys AS 
 SELECT s.id, s.title, s.owner_id, p.surname AS owner, s.description, s.website_id, w.title AS website, s.created_by_id, c.username AS created_by, s.updated_by_id, u.username AS updated_by
   FROM surveys s
   JOIN users c ON c.id = s.created_by_id
   JOIN users u ON u.id = s.updated_by_id
   LEFT JOIN people p ON p.id = s.owner_id AND p.deleted=false
   JOIN websites w ON w.id = s.website_id AND w.deleted=false
   WHERE s.deleted=false;

CREATE OR REPLACE VIEW gv_termlists AS 
 SELECT t.id, t.title, t.description, w.id as website_id, t.parent_id, t.deleted, t.created_on, t.created_by_id, t.updated_on, t.updated_by_id, w.title AS website, p.surname AS creator
   FROM termlists t
   LEFT JOIN websites w ON t.website_id = w.id AND w.deleted=false
   JOIN users u ON t.created_by_id = u.id
   JOIN people p ON u.person_id = p.id
   WHERE t.deleted=false;

CREATE OR REPLACE VIEW gv_taxon_images AS 
 SELECT ti.id, ti.path, ti.caption, ti.deleted, ti.taxon_meaning_id
   FROM taxon_images ti
   WHERE ti.deleted=false;

CREATE OR REPLACE VIEW list_taxon_images AS 
 SELECT ti.id, ti.path, ti.caption, ti.deleted, ti.taxon_meaning_id
   FROM taxon_images ti
   WHERE ti.deleted=false;

CREATE OR REPLACE VIEW detail_taxon_images AS 
 SELECT ti.id, ti.path, ti.caption, ti.deleted, ti.taxon_meaning_id, ti.created_by_id, c.username AS created_by, ti.updated_by_id, u.username AS updated_by
   FROM taxon_images ti
   JOIN users c ON c.id = ti.created_by_id
   JOIN users u ON u.id = ti.updated_by_id
   WHERE ti.deleted=false;

CREATE OR REPLACE VIEW gv_taxon_relation_types AS
 SELECT trt.id, trt.caption, trt.forward_term, trt.reverse_term, trt.relation_code
 FROM taxon_relation_types trt
 WHERE trt.deleted=false;

CREATE OR REPLACE VIEW list_taxon_relation_types AS
 SELECT trt.id, trt.caption, trt.forward_term, trt.reverse_term, trt.relation_code
 FROM taxon_relation_types trt
 WHERE trt.deleted=false;

CREATE OR REPLACE VIEW detail_taxon_relation_types AS
 SELECT trt.id, trt.caption, trt.forward_term, trt.reverse_term, trt.relation_code, trt.special,
     trt.created_by_id, c.username AS created_by, trt.updated_by_id, u.username AS updated_by
 FROM taxon_relation_types trt
 JOIN users c ON c.id = trt.created_by_id
 JOIN users u ON u.id = trt.updated_by_id
 WHERE trt.deleted=false;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, l.iso AS language, tc.taxon AS common, tpref.taxon AS preferred_name, tg.title AS taxon_group, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, tl.website_id, t.external_key, ttl.allow_data_entry
   FROM taxa_taxon_lists ttl
   JOIN taxa_taxon_lists ttlpref 
	ON ttlpref.taxon_meaning_id = ttl.taxon_meaning_id AND ttlpref.preferred = true AND ttlpref.deleted = false AND ttlpref.taxon_list_id=ttl.taxon_list_id
   JOIN taxa tpref ON tpref.id = ttlpref.taxon_id AND tpref.deleted = false
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id AND tl.deleted = false
   JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id
   JOIN taxon_groups tg ON tg.id = t.taxon_group_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id AND tc.deleted = false
  WHERE ttl.deleted = false
  ORDER BY ttl.taxonomic_sort_order, t.taxon;
