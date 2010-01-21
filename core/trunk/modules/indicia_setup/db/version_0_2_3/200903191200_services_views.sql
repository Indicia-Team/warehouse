-- note name change on foreign key constraint.
ALTER TABLE ONLY surveys drop CONSTRAINT pk_surveys_owner;
ALTER TABLE ONLY surveys
    ADD CONSTRAINT fk_surveys_owner FOREIGN KEY (owner_id) REFERENCES people(id);

--
-- Language views, NB no gv_
--
DROP VIEW IF EXISTS detail_languages;
DROP VIEW IF EXISTS list_languages;

CREATE VIEW list_languages AS
SELECT l.id, l.language, l.iso, cast (null as integer) as website_id
FROM languages l;

CREATE VIEW detail_languages AS
SELECT l.id, l.language, l.iso, l.created_by_id, c.username AS created_by,
    l.updated_by_id, u.username AS updated_by, cast (null as integer) as website_id
FROM ((languages l JOIN users c ON ((c.id = l.created_by_id))) JOIN users u
    ON ((u.id = l.updated_by_id)));

--
-- locations views, NB no gv_
--
DROP VIEW IF EXISTS list_locations;
DROP VIEW IF EXISTS detail_locations;

CREATE VIEW list_locations AS
SELECT l.id, l.name, l.code, l.centroid_sref, lw.website_id
FROM locations l
	JOIN locations_websites lw ON (l.id = lw.location_id);

CREATE VIEW detail_locations AS
SELECT l.id, l.name, l.code, l.parent_id, p.name AS parent,
    l.centroid_sref, l.centroid_sref_system, l.created_by_id, c.username AS
    created_by, l.updated_by_id, u.username AS updated_by, lw.website_id
FROM ((((locations l
			JOIN users c ON (c.id = l.created_by_id))
			JOIN users u ON (u.id = l.updated_by_id))
			JOIN locations_websites lw ON (l.id = lw.location_id))
			LEFT JOIN locations p ON ((p.id = l.parent_id)));

--
-- list_ and detail_ occurrence need no alterations. The gv_ does not include the website, easy addition
--
DROP VIEW IF EXISTS gv_occurrences;

CREATE OR REPLACE VIEW gv_occurrences AS
 SELECT o.id, o.sample_id, t.taxon, sa.date_start, sa.date_end, sa.date_type, sa.entered_sref,
 		sa.entered_sref_system, sa.location_name, l.name, o.deleted, o.website_id
   FROM occurrences o
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN samples sa ON o.sample_id = sa.id
   LEFT JOIN locations l ON sa.location_id = l.id;

--
-- list_ and gv_ occurrence_attributes need no alterations. No detail_ view.
-- NB these two views have different primary tables (occurence_attributes and
-- occurrence_attribute_websites respectively) so may give different results.
--

--
-- people views, NB no gv_
--
-- 2 sets of people:
-- 1) those people who are linked as users to the website with a valid site_role
-- 2) those people have no user details.
DROP VIEW IF EXISTS list_people;
DROP VIEW IF EXISTS detail_people;

CREATE VIEW list_people AS
SELECT p.id, p.first_name, p.surname, p.initials, (((p.first_name)::text ||
    ' '::text) || (p.surname)::text) AS caption, uw.website_id
FROM ((people p
		JOIN users us ON (us.person_id = p.id))
		JOIN users_websites uw ON (uw.user_id = us.id and uw.site_role_id is not null))
UNION ALL
SELECT p.id, p.first_name, p.surname, p.initials, (((p.first_name)::text ||
    ' '::text) || (p.surname)::text) AS caption, cast (null as integer) as website_id
FROM people p
WHERE not exists (select id from users u where u.person_id = p.id);

CREATE VIEW detail_people AS
SELECT p.id, p.first_name, p.surname, p.initials, p.email_address,
    p.website_url, p.created_by_id, c.username AS created_by,
    p.updated_by_id, u.username AS updated_by, uw.website_id
FROM ((((people p
		JOIN users c ON (c.id = p.created_by_id))
		JOIN users u ON (u.id = p.updated_by_id))
		JOIN users us ON (us.person_id = p.id))
		JOIN users_websites uw ON (uw.user_id = us.id  and uw.site_role_id is not null))
UNION ALL
SELECT p.id, p.first_name, p.surname, p.initials, p.email_address,
    p.website_url, p.created_by_id, c.username AS created_by,
    p.updated_by_id, u.username AS updated_by, cast (null as integer) as website_id
FROM ((people p
		JOIN users c ON (c.id = p.created_by_id))
		JOIN users u ON (u.id = p.updated_by_id))
WHERE not exists (select id from users us where us.person_id = p.id);

--
-- sample views
--
DROP VIEW IF EXISTS list_samples;
DROP VIEW IF EXISTS detail_samples;
DROP VIEW IF EXISTS gv_samples;

CREATE VIEW list_samples AS
SELECT s.id, su.title AS survey, l.name AS location, s.date_start,
    s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, su.website_id
FROM ((samples s LEFT JOIN locations l ON ((s.location_id = l.id))) LEFT
    JOIN surveys su ON ((s.survey_id = su.id)));

CREATE VIEW detail_samples AS
SELECT s.id, s.entered_sref, s.entered_sref_system, s.geom,
    s.location_name, s.date_start, s.date_end, s.date_type, s.location_id,
    l.name AS location, l.code AS location_code, s.created_by_id,
    c.username AS created_by, s.created_on, s.updated_by_id, u.username AS
    updated_by, s.updated_on, su.website_id
FROM ((((samples s LEFT JOIN locations l ON ((l.id = s.location_id))) LEFT
    JOIN surveys su ON ((s.survey_id = su.id))) JOIN users c ON ((c.id =
    s.created_by_id))) JOIN users u ON ((u.id = s.updated_by_id)));

CREATE OR REPLACE VIEW gv_samples AS
 SELECT s.id, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system,
 	s.location_name, s.deleted, su.title, l.name as "location", su.website_id
   FROM samples s
   LEFT JOIN surveys su ON s.survey_id = su.id
   LEFT JOIN locations l ON s.location_id = l.id;

--
-- survey views: list_ and detail_ OK as is.
--
DROP VIEW IF EXISTS gv_surveys;

CREATE VIEW gv_surveys AS
SELECT s.id, s.title, s.description, w.title AS website, s.deleted, s.website_id
FROM (surveys s LEFT JOIN websites w ON ((s.website_id = w.id)));

--
-- taxon_group views, NB no gv_
--
DROP VIEW IF EXISTS list_taxon_groups;
DROP VIEW IF EXISTS detail_taxon_groups;

CREATE VIEW list_taxon_groups AS
SELECT t.id, t.title, cast (null as integer) as website_id
FROM taxon_groups t;

CREATE VIEW detail_taxon_groups AS
SELECT t.id, t.title, t.created_by_id, c.username AS created_by,
    t.updated_by_id, u.username AS updated_by, cast (null as integer) as website_id
FROM ((taxon_groups t JOIN users c ON ((c.id = t.created_by_id))) JOIN
    users u ON ((u.id = t.updated_by_id)));


--
-- list_ and detail_ taxon_lists need no alterations. No gv_
--

--
-- taxa_taxon_lists views. No gv_
--
DROP VIEW IF EXISTS list_taxa_taxon_lists;
DROP VIEW IF EXISTS detail_taxa_taxon_lists;

CREATE VIEW detail_taxa_taxon_lists AS
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, tl.title AS taxon_list, ttl.taxon_meaning_id, ttl.preferred, ttl.parent_id, tp.taxon AS parent,
	l.iso as language_iso, t.image_path as taxon_image_path, ttl.image_path, t.description as taxon_description, ttl.description,
	ttl.created_by_id, c.username AS created_by, ttl.updated_by_id, u.username AS updated_by, tl.website_id
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN users c ON c.id = ttl.created_by_id
   JOIN users u ON u.id = ttl.updated_by_id
   JOIN languages l ON l.id=t.language_id
   LEFT JOIN taxa_taxon_lists ttlp ON ttlp.id = ttl.parent_id
   LEFT JOIN taxa tp ON tp.id = ttlp.taxon_id
  WHERE ttl.deleted = false;

CREATE VIEW list_taxa_taxon_lists AS
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list,
	l.iso as language_iso, t.image_path as taxon_image_path, ttl.image_path, tl.website_id
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN languages l ON l.id=t.language_id
  WHERE ttl.deleted = false;


--
-- terms views, no gv_
--
DROP VIEW IF EXISTS list_terms;
DROP VIEW IF EXISTS detail_terms;

CREATE VIEW list_terms AS
SELECT t.id, t.term, t.language_id, l.language, l.iso, cast (null as integer) as website_id
FROM (terms t JOIN languages l ON ((l.id = t.language_id)));

CREATE VIEW detail_terms AS
SELECT t.id, t.term, t.language_id, l.language, l.iso, t.created_by_id,
    c.username AS created_by, t.updated_by_id, u.username AS updated_by, cast (null as integer) as website_id
FROM (((terms t JOIN languages l ON ((l.id = t.language_id))) JOIN users c
    ON ((c.id = t.created_by_id))) JOIN users u ON ((u.id = t.updated_by_id)));

--
-- termlists views need no alteration
--

--
-- termlist_terms views: list_ need no alteration
-- gv_ would require a new join - will not update due to potential impacts.
-- This gv_termlist_terms view will not be useable over the services.
--
DROP VIEW IF EXISTS detail_termlists_terms;

CREATE OR REPLACE VIEW detail_termlists_terms AS
 SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist,
	tlt.meaning_id, tlt.preferred, tlt.parent_id, tp.term as parent, tl.website_id,
	tlt.created_by_id, c.username as created_by, tlt.updated_by_id, u.username as updated_by
 FROM termlists_terms tlt
 INNER JOIN termlists tl ON tl.id=tlt.termlist_id
 INNER JOIN terms t ON t.id=tlt.term_id
 INNER JOIN users c on c.id=tlt.created_by_id
 INNER JOIN users u on u.id=tlt.updated_by_id
 LEFT JOIN  termlists_terms tltp on tltp.id=tlt.parent_id
 LEFT JOIN terms tp on tp.id=tltp.term_id
 WHERE tlt.deleted=false;


--
-- user views - no details or list - need to add a list in order to access the data.
-- the gv does not have a website_id on it but am not going to fix that, due to impacts on core module.
-- This gv_users view will not be useable over the services.
--
DROP VIEW IF EXISTS list_users;

CREATE VIEW list_users AS
SELECT u.id, u.username, uw.website_id
FROM users u
		JOIN users_websites uw ON (u.id = uw.user_id AND uw.site_role_id IS NOT NULL)
WHERE u.deleted = false;

--
-- websites views: no gv
--
DROP VIEW IF EXISTS detail_websites;
DROP VIEW IF EXISTS list_websites;

CREATE VIEW list_websites AS
SELECT w.id, w.title, w.id as website_id
FROM websites w;

CREATE VIEW detail_websites AS
SELECT w.id, w.title, w.url, w.description, w.created_by_id, c.username AS
    created_by, w.updated_by_id, u.username AS updated_by, w.id as website_id
FROM ((websites w JOIN users c ON ((c.id = w.created_by_id))) JOIN users u
    ON ((u.id = w.updated_by_id)));

--
-- occurence_comments view: list only
--
DROP VIEW list_occurrence_comments;

CREATE OR REPLACE VIEW list_occurrence_comments AS
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, oc.person_name, u.username, o.website_id
   FROM occurrence_comments oc
		JOIN occurrences o on (o.id = oc.occurrence_id)
   LEFT JOIN users u ON oc.created_by_id = u.id;

