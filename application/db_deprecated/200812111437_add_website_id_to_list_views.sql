DROP VIEW list_occurrences;

CREATE OR REPLACE VIEW list_occurrences AS 
 SELECT su.title AS survey, l.name AS location, (d.first_name::text || ' '::text) || d.surname::text AS determiner, t.taxon, su.website_id
   FROM occurrences o
   JOIN samples s ON o.sample_id = s.id
   JOIN people d ON o.determiner_id = d.id
   JOIN locations l ON s.location_id = l.id
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN surveys su ON s.survey_id = su.id;

DROP VIEW list_surveys;

CREATE OR REPLACE VIEW list_surveys AS 
 SELECT s.id, s.title, s.website_id
   FROM surveys s;

DROP VIEW list_taxa_taxon_lists;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, tl.website_id
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
  WHERE ttl.deleted = false;

DROP VIEW list_taxon_lists;

CREATE OR REPLACE VIEW list_taxon_lists AS 
 SELECT t.id, t.title, t.website_id
   FROM taxon_lists t;

DROP VIEW list_termlists;

CREATE OR REPLACE VIEW list_termlists AS 
 SELECT t.id, t.title, t.website_id
   FROM termlists t
  WHERE t.deleted = false;

DROP VIEW list_termlists_terms;

CREATE OR REPLACE VIEW list_termlists_terms AS 
 SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist, tl.website_id
   FROM termlists_terms tlt
   JOIN termlists tl ON tl.id = tlt.termlist_id
   JOIN terms t ON t.id = tlt.term_id
  WHERE tlt.deleted = false;