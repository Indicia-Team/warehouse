DROP VIEW IF EXISTS gv_users;
DROP VIEW IF EXISTS gv_surveys;
DROP VIEW IF EXISTS list_samples;
DROP VIEW IF EXISTS detail_samples;
DROP VIEW IF EXISTS list_occurrences;
DROP VIEW IF EXISTS detail_occurrences;
DROP VIEW IF EXISTS list_locations;
DROP VIEW IF EXISTS detail_locations;
DROP VIEW IF EXISTS gv_sample_attributes;
DROP VIEW IF EXISTS gv_location_attributes;
DROP VIEW IF EXISTS gv_occurrence_attributes;
DROP VIEW IF EXISTS list_occurrence_attributes;
DROP VIEW IF EXISTS list_termlists_terms;
DROP VIEW IF EXISTS list_termlists;
DROP VIEW IF EXISTS list_taxon_lists;
DROP VIEW IF EXISTS list_taxa_taxon_lists;
DROP VIEW IF EXISTS list_surveys;
DROP VIEW IF EXISTS list_people;
DROP VIEW IF EXISTS detail_websites;
DROP VIEW IF EXISTS list_websites;
DROP VIEW IF EXISTS detail_termlists_terms;
DROP VIEW IF EXISTS detail_termlists;
DROP VIEW IF EXISTS detail_terms;
DROP VIEW IF EXISTS list_terms;
DROP VIEW IF EXISTS detail_taxon_lists;
DROP VIEW IF EXISTS detail_taxon_groups;
DROP VIEW IF EXISTS list_taxon_groups;
DROP VIEW IF EXISTS detail_taxa_taxon_lists;
DROP VIEW IF EXISTS detail_surveys;
DROP VIEW IF EXISTS detail_people;
DROP VIEW IF EXISTS detail_languages;
DROP VIEW IF EXISTS list_languages;
DROP VIEW IF EXISTS gv_taxon_lists_taxa;
DROP VIEW IF EXISTS gv_termlists_terms;
DROP VIEW IF EXISTS gv_term_termlists;
DROP VIEW IF EXISTS gv_termlists;
SET check_function_bodies = false;
--
-- Definition for view gv_termlists (OID = 118986) :
--
CREATE VIEW gv_termlists AS
SELECT t.id, t.title, t.description, t.website_id, t.parent_id, t.deleted,
    t.created_on, t.created_by_id, t.updated_on, t.updated_by_id, w.title
    AS website, p.surname AS creator
FROM (((termlists t LEFT JOIN websites w ON ((t.website_id = w.id))) JOIN
    users u ON ((t.created_by_id = u.id))) JOIN people p ON ((u.person_id =
    p.id)));

--
-- Definition for view gv_term_termlists (OID = 119031) :
--
CREATE VIEW gv_term_termlists AS
SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id,
    tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id,
    tt.preferred, tt.sort_order, t.title, t.description
FROM (termlists_terms tt JOIN termlists t ON ((tt.termlist_id = t.id)));

--
-- Definition for view gv_termlists_terms (OID = 119084) :
--
CREATE VIEW gv_termlists_terms AS
SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id,
    tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id,
    tt.preferred, tt.sort_order, tt.deleted, t.term, l.language
FROM ((termlists_terms tt JOIN terms t ON ((tt.term_id = t.id))) JOIN
    languages l ON ((t.language_id = l.id)));

--
-- Definition for view gv_taxon_lists_taxa (OID = 119088) :
--
CREATE VIEW gv_taxon_lists_taxa AS
SELECT tt.id, tt.taxon_list_id, tt.taxon_id, tt.created_on,
    tt.created_by_id, tt.parent_id, tt.taxon_meaning_id,
    tt.taxonomic_sort_order, tt.preferred, tt.deleted, t.taxon,
    t.taxon_group_id, t.language_id, t.authority, t.search_code,
    t.scientific, l.language
FROM ((taxa_taxon_lists tt JOIN taxa t ON ((tt.taxon_id = t.id))) JOIN
    languages l ON ((t.language_id = l.id)));

--
-- Definition for view list_languages (OID = 119102) :
--
CREATE VIEW list_languages AS
SELECT l.id, l.language, l.iso
FROM languages l;

--
-- Definition for view detail_languages (OID = 119106) :
--
CREATE VIEW detail_languages AS
SELECT l.id, l.language, l.iso, l.created_by_id, c.username AS created_by,
    l.updated_by_id, u.username AS updated_by
FROM ((languages l JOIN users c ON ((c.id = l.created_by_id))) JOIN users u
    ON ((u.id = l.updated_by_id)));

--
-- Definition for view detail_people (OID = 119124) :
--
CREATE VIEW detail_people AS
SELECT p.id, p.first_name, p.surname, p.initials, p.email_address,
    p.website_url, p.created_by_id, c.username AS created_by,
    p.updated_by_id, u.username AS updated_by
FROM ((people p JOIN users c ON ((c.id = p.created_by_id))) JOIN users u ON
    ((u.id = p.updated_by_id)));

--
-- Definition for view detail_surveys (OID = 119133) :
--
CREATE VIEW detail_surveys AS
SELECT s.id, s.title, s.owner_id, p.surname AS owner, s.description,
    s.website_id, w.title AS website, s.created_by_id, c.username AS
    created_by, s.updated_by_id, u.username AS updated_by
FROM ((((surveys s JOIN users c ON ((c.id = s.created_by_id))) JOIN users u
    ON ((u.id = s.updated_by_id))) JOIN people p ON ((p.id = s.owner_id)))
    JOIN websites w ON ((w.id = s.website_id)));

--
-- Definition for view detail_taxa_taxon_lists (OID = 119143) :
--
CREATE VIEW detail_taxa_taxon_lists AS
SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id,
    tl.title AS taxon_list, ttl.taxon_meaning_id, ttl.preferred,
    ttl.parent_id, tp.taxon AS parent, ttl.created_by_id, c.username AS
    created_by, ttl.updated_by_id, u.username AS updated_by
FROM ((((((taxa_taxon_lists ttl JOIN taxon_lists tl ON ((tl.id =
    ttl.taxon_list_id))) JOIN taxa t ON ((t.id = ttl.taxon_id))) JOIN users
    c ON ((c.id = ttl.created_by_id))) JOIN users u ON ((u.id =
    ttl.updated_by_id))) LEFT JOIN taxa_taxon_lists ttlp ON ((ttlp.id =
    ttl.parent_id))) LEFT JOIN taxa tp ON ((tp.id = ttlp.taxon_id)))
WHERE (ttl.deleted = false);

--
-- Definition for view list_taxon_groups (OID = 119148) :
--
CREATE VIEW list_taxon_groups AS
SELECT t.id, t.title
FROM taxon_groups t;

--
-- Definition for view detail_taxon_groups (OID = 119152) :
--
CREATE VIEW detail_taxon_groups AS
SELECT t.id, t.title, t.created_by_id, c.username AS created_by,
    t.updated_by_id, u.username AS updated_by
FROM ((taxon_groups t JOIN users c ON ((c.id = t.created_by_id))) JOIN
    users u ON ((u.id = t.updated_by_id)));

--
-- Definition for view detail_taxon_lists (OID = 119161) :
--
CREATE VIEW detail_taxon_lists AS
SELECT t.id, t.title, t.description, t.website_id, w.title AS website,
    t.parent_id, p.title AS parent, t.created_by_id, c.username AS
    created_by, t.updated_by_id, u.username AS updated_by
FROM ((((taxon_lists t LEFT JOIN websites w ON ((w.id = t.website_id)))
    LEFT JOIN taxon_lists p ON ((p.id = t.parent_id))) JOIN users c ON
    ((c.id = t.created_by_id))) JOIN users u ON ((u.id = t.updated_by_id)));

--
-- Definition for view list_terms (OID = 119166) :
--
CREATE VIEW list_terms AS
SELECT t.id, t.term, t.language_id, l.language, l.iso
FROM (terms t JOIN languages l ON ((l.id = t.language_id)));

--
-- Definition for view detail_terms (OID = 119170) :
--
CREATE VIEW detail_terms AS
SELECT t.id, t.term, t.language_id, l.language, l.iso, t.created_by_id,
    c.username AS created_by, t.updated_by_id, u.username AS updated_by
FROM (((terms t JOIN languages l ON ((l.id = t.language_id))) JOIN users c
    ON ((c.id = t.created_by_id))) JOIN users u ON ((u.id = t.updated_by_id)));

--
-- Definition for view detail_termlists (OID = 119179) :
--
CREATE VIEW detail_termlists AS
SELECT t.id, t.title, t.description, t.website_id, w.title AS website,
    t.parent_id, p.title AS parent, t.created_by_id, c.username AS
    created_by, t.updated_by_id, u.username AS updated_by
FROM ((((termlists t LEFT JOIN websites w ON ((w.id = t.website_id))) LEFT
    JOIN termlists p ON ((p.id = t.parent_id))) JOIN users c ON ((c.id =
    t.created_by_id))) JOIN users u ON ((u.id = t.updated_by_id)))
WHERE (t.deleted = false);

--
-- Definition for view detail_termlists_terms (OID = 119188) :
--
CREATE VIEW detail_termlists_terms AS
SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist,
    tlt.meaning_id, tlt.preferred, tlt.parent_id, tp.term AS parent,
    tlt.created_by_id, c.username AS created_by, tlt.updated_by_id,
    u.username AS updated_by
FROM ((((((termlists_terms tlt JOIN termlists tl ON ((tl.id =
    tlt.termlist_id))) JOIN terms t ON ((t.id = tlt.term_id))) JOIN users c
    ON ((c.id = tlt.created_by_id))) JOIN users u ON ((u.id =
    tlt.updated_by_id))) LEFT JOIN termlists_terms tltp ON ((tltp.id =
    tlt.parent_id))) LEFT JOIN terms tp ON ((tp.id = tltp.term_id)))
WHERE (tlt.deleted = false);

--
-- Definition for view list_websites (OID = 119193) :
--
CREATE VIEW list_websites AS
SELECT w.id, w.title
FROM websites w;

--
-- Definition for view detail_websites (OID = 119197) :
--
CREATE VIEW detail_websites AS
SELECT w.id, w.title, w.url, w.description, w.created_by_id, c.username AS
    created_by, w.updated_by_id, u.username AS updated_by
FROM ((websites w JOIN users c ON ((c.id = w.created_by_id))) JOIN users u
    ON ((u.id = w.updated_by_id)));

--
-- Definition for view list_people (OID = 119303) :
--
CREATE VIEW list_people AS
SELECT p.id, p.first_name, p.surname, p.initials, (((p.first_name)::text ||
    ' '::text) || (p.surname)::text) AS caption
FROM people p;

--
-- Definition for view list_surveys (OID = 119404) :
--
CREATE VIEW list_surveys AS
SELECT s.id, s.title, s.website_id
FROM surveys s;

--
-- Definition for view list_taxa_taxon_lists (OID = 119408) :
--
CREATE VIEW list_taxa_taxon_lists AS
SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id,
    ttl.preferred, tl.title AS taxon_list, tl.website_id
FROM ((taxa_taxon_lists ttl JOIN taxon_lists tl ON ((tl.id =
    ttl.taxon_list_id))) JOIN taxa t ON ((t.id = ttl.taxon_id)))
WHERE (ttl.deleted = false);

--
-- Definition for view list_taxon_lists (OID = 119413) :
--
CREATE VIEW list_taxon_lists AS
SELECT t.id, t.title, t.website_id
FROM taxon_lists t;

--
-- Definition for view list_termlists (OID = 119417) :
--
CREATE VIEW list_termlists AS
SELECT t.id, t.title, t.website_id
FROM termlists t
WHERE (t.deleted = false);

--
-- Definition for view list_termlists_terms (OID = 119421) :
--
CREATE VIEW list_termlists_terms AS
SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist,
    tl.website_id
FROM ((termlists_terms tlt JOIN termlists tl ON ((tl.id =
    tlt.termlist_id))) JOIN terms t ON ((t.id = tlt.term_id)))
WHERE (tlt.deleted = false);

--
-- Definition for view list_occurrence_attributes (OID = 119429) :
--
CREATE VIEW list_occurrence_attributes AS
SELECT oa.id, oa.caption, oa.data_type, oa.termlist_id, oa.multi_value,
    oaw.website_id, ((((oa.id || '|'::text) || (oa.data_type)::text) ||
    '|'::text) || COALESCE((oa.termlist_id)::text, ''::text)) AS signature
FROM (occurrence_attributes oa LEFT JOIN occurrence_attributes_websites oaw
    ON ((oaw.occurrence_attribute_id = oa.id)));

--
-- Definition for view gv_occurrence_attributes (OID = 119440) :
--
CREATE VIEW gv_occurrence_attributes AS
SELECT oaw.id, oaw.website_id, oaw.restrict_to_survey_id AS survey_id,
    w.title AS website, s.title AS survey, oa.caption, oa.data_type
FROM (((occurrence_attributes_websites oaw LEFT JOIN occurrence_attributes
    oa ON ((oa.id = oaw.occurrence_attribute_id))) LEFT JOIN websites w ON
    ((w.id = oaw.website_id))) LEFT JOIN surveys s ON ((s.id =
    oaw.restrict_to_survey_id)));

--
-- Definition for view gv_location_attributes (OID = 119445) :
--
CREATE VIEW gv_location_attributes AS
SELECT law.id, law.website_id, law.restrict_to_survey_id AS survey_id,
    w.title AS website, s.title AS survey, la.caption, la.data_type
FROM (((location_attributes_websites law LEFT JOIN location_attributes la
    ON ((la.id = law.location_attribute_id))) LEFT JOIN websites w ON
    ((w.id = law.website_id))) LEFT JOIN surveys s ON ((s.id =
    law.restrict_to_survey_id)));

--
-- Definition for view gv_sample_attributes (OID = 119450) :
--
CREATE VIEW gv_sample_attributes AS
SELECT saw.id, saw.website_id, saw.restrict_to_survey_id AS survey_id,
    w.title AS website, s.title AS survey, sa.caption, sa.data_type
FROM (((sample_attributes_websites saw LEFT JOIN sample_attributes sa ON
    ((sa.id = saw.sample_attribute_id))) LEFT JOIN websites w ON ((w.id =
    saw.website_id))) LEFT JOIN surveys s ON ((s.id = saw.restrict_to_survey_id)));

--
-- Definition for view detail_locations (OID = 119469) :
--
CREATE VIEW detail_locations AS
SELECT l.id, l.name, l.code, l.parent_id, p.name AS parent,
    l.centroid_sref, l.centroid_sref_system, l.created_by_id, c.username AS
    created_by, l.updated_by_id, u.username AS updated_by
FROM (((locations l JOIN users c ON ((c.id = l.created_by_id))) JOIN users
    u ON ((u.id = l.updated_by_id))) LEFT JOIN locations p ON ((p.id =
    l.parent_id)));

--
-- Definition for view list_locations (OID = 119474) :
--
CREATE VIEW list_locations AS
SELECT l.id, l.name, l.code, l.centroid_sref
FROM locations l;

--
-- Definition for view detail_occurrences (OID = 119763) :
--
CREATE VIEW detail_occurrences AS
SELECT o.id, o.confidential, o.comment, o.taxa_taxon_list_id, t.taxon,
    s.entered_sref, s.entered_sref_system, s.geom, s.location_name,
    s.date_start, s.date_end, s.date_type, s.location_id, l.name AS
    location, l.code AS location_code, (((d.first_name)::text || ' '::text)
    || (d.surname)::text) AS determiner, o.website_id, o.created_by_id,
    c.username AS created_by, o.created_on, o.updated_by_id, u.username AS
    updated_by, o.updated_on
FROM ((((((((occurrences o JOIN samples s ON ((s.id = o.sample_id))) LEFT
    JOIN people d ON ((d.id = o.determiner_id))) LEFT JOIN locations l ON
    ((l.id = s.location_id))) JOIN taxa_taxon_lists ttl ON ((ttl.id =
    o.taxa_taxon_list_id))) JOIN taxa t ON ((t.id = ttl.taxon_id))) LEFT
    JOIN surveys su ON ((s.survey_id = su.id))) JOIN users c ON ((c.id =
    o.created_by_id))) JOIN users u ON ((u.id = o.updated_by_id)));

--
-- Definition for view list_occurrences (OID = 119768) :
--
CREATE VIEW list_occurrences AS
SELECT su.title AS survey, l.name AS location, s.date_start, s.date_end,
    s.date_type, s.entered_sref, s.entered_sref_system, t.taxon, o.website_id
FROM (((((occurrences o JOIN samples s ON ((o.sample_id = s.id))) LEFT JOIN
    locations l ON ((s.location_id = l.id))) JOIN taxa_taxon_lists ttl ON
    ((o.taxa_taxon_list_id = ttl.id))) JOIN taxa t ON ((ttl.taxon_id =
    t.id))) LEFT JOIN surveys su ON ((s.survey_id = su.id)));

--
-- Definition for view detail_samples (OID = 119773) :
--
CREATE VIEW detail_samples AS
SELECT s.id, s.entered_sref, s.entered_sref_system, s.geom,
    s.location_name, s.date_start, s.date_end, s.date_type, s.location_id,
    l.name AS location, l.code AS location_code, s.created_by_id,
    c.username AS created_by, s.created_on, s.updated_by_id, u.username AS
    updated_by, s.updated_on
FROM ((((samples s LEFT JOIN locations l ON ((l.id = s.location_id))) LEFT
    JOIN surveys su ON ((s.survey_id = su.id))) JOIN users c ON ((c.id =
    s.created_by_id))) JOIN users u ON ((u.id = s.updated_by_id)));

--
-- Definition for view list_samples (OID = 119778) :
--
CREATE VIEW list_samples AS
SELECT s.id, su.title AS survey, l.name AS location, s.date_start,
    s.date_end, s.date_type, s.entered_sref, s.entered_sref_system
FROM ((samples s LEFT JOIN locations l ON ((s.location_id = l.id))) LEFT
    JOIN surveys su ON ((s.survey_id = su.id)));

--
-- Definition for view gv_surveys (OID = 119783) :
--
CREATE VIEW gv_surveys AS
SELECT s.id, s.title, s.description, w.title AS website, s.deleted
FROM (surveys s LEFT JOIN websites w ON ((s.website_id = w.id)));

--
-- Definition for view gv_users (OID = 119787) :
--
CREATE VIEW gv_users AS
SELECT p.id AS person_id, (COALESCE(((p.first_name)::text || ' '::text),
    ''::text) || (p.surname)::text) AS name, u.id, u.username, cr.title AS
    core_role, p.deleted
FROM ((people p LEFT JOIN users u ON (((p.id = u.person_id) AND (u.deleted
    = false)))) LEFT JOIN core_roles cr ON ((u.core_role_id = cr.id)))
WHERE (p.email_address IS NOT NULL);

--
-- Comments
--
COMMENT ON VIEW gv_term_termlists IS 'View for the terms page - shows the list of termlists that a term belongs to.';
