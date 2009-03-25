CREATE VIEW detail_occurrences AS
SELECT occ.*,
s.location_name,
s.date_start,
s.date_end,
s.date_type,
su.title as "survey", 
(d.first_name || ' ' || d.surname) as "determiner",
t.taxon, t.authority, l.language, tg.title
FROM occurrences occ
JOIN samples s ON occ.sample_id = s.id
JOIN surveys su ON s.survey_id = su.id
JOIN people d ON occ.determiner_id = d.id
JOIN taxa_taxon_lists ttl ON occ.taxa_taxon_list_id = ttl.id
JOIN taxa t ON ttl.taxon_id = t.id
JOIN taxon_groups tg ON t.taxon_group_id = tg.id
JOIN languages l ON t.language_id = l.id;

CREATE OR REPLACE VIEW list_samples AS
SELECT s.date_start, s.date_end, s.date_type, s.entered_sref, s.location_name, s.comment, su.title, su.description
FROM samples s
JOIN surveys su ON s.survey_id = su.id;

CREATE OR REPLACE VIEW detail_samples AS
SELECT s.*, su.title AS "survey", su.description
FROM samples s
JOIN surveys su ON s.survey_id = su.id;