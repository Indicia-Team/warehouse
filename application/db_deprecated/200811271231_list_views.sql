-- View: list_languages

-- DROP VIEW list_languages;

CREATE OR REPLACE VIEW list_languages AS 
 SELECT l.id, l.language, l.iso
 FROM languages l;

-- View: detail_languages

-- DROP VIEW detail_languages;

CREATE OR REPLACE VIEW detail_languages AS 
 SELECT l.id, l.language, l.iso, 
	l.created_by_id, c.username as created_by, l.updated_by_id, u.username as updated_by
 FROM languages l
 INNER JOIN users c on c.id=l.created_by_id
 INNER JOIN users u on u.id=l.updated_by_id;

 



-- View: list_locations

-- DROP VIEW list_locations;

CREATE OR REPLACE VIEW list_locations AS 
 SELECT l.id, l.name, l.code, l.centroid_sref
 FROM locations l; 

-- View: detail_locations

-- DROP VIEW detail_locations;

CREATE OR REPLACE VIEW detail_locations AS 
 SELECT l.id, l.name, l.code, l.parent_id, p.name as parent, l.centroid_sref, l.centroid_sref_system, 
	l.created_by_id, c.username as created_by, l.updated_by_id, u.username as updated_by
 FROM locations l
 INNER JOIN users c on c.id=l.created_by_id
 INNER JOIN users u on u.id=l.updated_by_id
 LEFT JOIN locations p on p.id=l.parent_id;
 


-- View: list_people

-- DROP VIEW list_people;

CREATE OR REPLACE VIEW list_people AS 
 SELECT p.id, p.first_name, p.surname, p.initials
 FROM people p;

 -- View: detail_people

-- DROP VIEW detail_people;

CREATE OR REPLACE VIEW detail_people AS 
 SELECT p.id, p.first_name, p.surname, p.initials, p.email_address, p.website_url, 
	p.created_by_id, c.username as created_by, p.updated_by_id, u.username as updated_by
 FROM people p
 INNER JOIN users c on c.id=p.created_by_id
 INNER JOIN users u on u.id=p.updated_by_id;
 
 


-- View: list_surveys

-- DROP VIEW list_surveys;

CREATE OR REPLACE VIEW list_surveys AS 
 SELECT s.id, s.title
 FROM surveys s;


-- View: detail_surveys

-- DROP VIEW detail_surveys;

CREATE OR REPLACE VIEW detail_surveys AS 
 SELECT s.id, s.title, s.owner_id, p.surname as owner, s.description, s.website_id, w.title as website, 
	s.created_by_id, c.username as created_by, s.updated_by_id, u.username as updated_by
 FROM surveys s
 INNER JOIN users c on c.id=s.created_by_id
 INNER JOIN users u on u.id=s.updated_by_id
 INNER JOIN people p on p.id=s.owner_id
 INNER JOIN websites w on w.id=s.website_id;



-- View: list_taxa_taxon_lists

-- DROP VIEW list_taxa_taxon_lists;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, tl.title AS taxon_list
 FROM taxa_taxon_lists ttl
 INNER JOIN taxon_lists tl ON tl.id=ttl.taxon_list_id
 INNER JOIN taxa t ON t.id=ttl.taxon_id
 WHERE ttl.deleted=false;

-- View: detail_taxa_taxon_lists

-- DROP VIEW detail_taxa_taxon_lists;

CREATE OR REPLACE VIEW detail_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, tl.title AS taxon_list, 
	ttl.taxon_meaning_id, ttl.preferred, ttl.parent_id, tp.taxon as parent,
	ttl.created_by_id, c.username as created_by, ttl.updated_by_id, u.username as updated_by
 FROM taxa_taxon_lists ttl
 INNER JOIN taxon_lists tl ON tl.id=ttl.taxon_list_id
 INNER JOIN taxa t ON t.id=ttl.taxon_id
 INNER JOIN users c on c.id=ttl.created_by_id
 INNER JOIN users u on u.id=ttl.updated_by_id
 LEFT JOIN taxa_taxon_lists ttlp on ttlp.id=ttl.parent_id
 LEFT JOIN taxa tp on tp.id=ttlp.taxon_id
 WHERE ttl.deleted=false;




-- View: list_taxon_groups

-- DROP VIEW list_taxon_groups;

CREATE OR REPLACE VIEW list_taxon_groups AS 
 SELECT t.id, t.title
 FROM taxon_groups t;

-- View: detail_taxon_groups

-- DROP VIEW detail_taxon_groups;

CREATE OR REPLACE VIEW detail_taxon_groups AS 
 SELECT t.id, t.title, t.created_by_id, 
	c.username as created_by, t.updated_by_id, u.username as updated_by 
 FROM taxon_groups t
 INNER JOIN users c on c.id=t.created_by_id
 INNER JOIN users u on u.id=t.updated_by_id;



-- View: list_taxon_lists

-- DROP VIEW list_taxon_lists;

CREATE OR REPLACE VIEW list_taxon_lists AS 
 SELECT t.id, t.title
 FROM taxon_lists t;

-- View: detail_taxon_lists

-- DROP VIEW detail_taxon_lists;

CREATE OR REPLACE VIEW detail_taxon_lists AS 
 SELECT t.id, t.title, t.description, t.website_id, w.title as website, t.parent_id, p.title as parent,
	t.created_by_id, c.username as created_by, t.updated_by_id, u.username as updated_by 
 FROM taxon_lists t
 LEFT JOIN websites w on w.id=t.website_id
 LEFT JOIN taxon_lists p on p.id=t.parent_id
 INNER JOIN users c on c.id=t.created_by_id
 INNER JOIN users u on u.id=t.updated_by_id;



-- View: list_terms

-- DROP VIEW list_terms;

CREATE OR REPLACE VIEW list_terms AS 
 SELECT t.id, t.term, t.language_id, l.language, l.iso
 FROM terms t
 INNER JOIN languages l ON l.id=t.language_id;

-- View: detail_terms

-- DROP VIEW detail_terms;

CREATE OR REPLACE VIEW detail_terms AS 
 SELECT t.id, t.term, t.language_id, l.language, l.iso,
	t.created_by_id, c.username as created_by, t.updated_by_id, u.username as updated_by 
 FROM terms t
 INNER JOIN languages l ON l.id=t.language_id
 INNER JOIN users c on c.id=t.created_by_id
 INNER JOIN users u on u.id=t.updated_by_id; 



-- View: list_termlists

-- DROP VIEW list_termlists;

CREATE OR REPLACE VIEW list_termlists AS 
 SELECT t.id, t.title, t.website_id
 FROM termlists t
 WHERE deleted='f';

-- View: detail_termlists

-- DROP VIEW detail_termlists;

CREATE OR REPLACE VIEW detail_termlists AS 
 SELECT t.id, t.title, t.description, t.website_id, w.title as website, t.parent_id, p.title as parent,
	t.created_by_id, c.username as created_by, t.updated_by_id, u.username as updated_by 
 FROM termlists t
 LEFT JOIN websites w on w.id=t.website_id
 LEFT JOIN termlists p on p.id=t.parent_id
 INNER JOIN users c on c.id=t.created_by_id
 INNER JOIN users u on u.id=t.updated_by_id
 WHERE t.deleted='f';
 


-- View: list_termlists_terms

-- DROP VIEW list_termlists_terms;

CREATE OR REPLACE VIEW list_termlists_terms AS 
 SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist
 FROM termlists_terms tlt
 INNER JOIN termlists tl ON tl.id=tlt.termlist_id
 INNER JOIN terms t ON t.id=tlt.term_id
 WHERE tlt.deleted=false;

 
-- View: detail_termlists_terms

-- DROP VIEW detail_termlists_terms;

CREATE OR REPLACE VIEW detail_termlists_terms AS 
 SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist, 
	tlt.meaning_id, tlt.preferred, tlt.parent_id, tp.term as parent,
	tlt.created_by_id, c.username as created_by, tlt.updated_by_id, u.username as updated_by
 FROM termlists_terms tlt
 INNER JOIN termlists tl ON tl.id=tlt.termlist_id
 INNER JOIN terms t ON t.id=tlt.term_id
 INNER JOIN users c on c.id=tlt.created_by_id
 INNER JOIN users u on u.id=tlt.updated_by_id
 LEFT JOIN  termlists_terms tltp on tltp.id=tlt.parent_id
 LEFT JOIN terms tp on tp.id=tltp.term_id
 WHERE tlt.deleted=false;



-- View: list_websites

-- DROP VIEW list_websites;

CREATE OR REPLACE VIEW list_websites AS 
 SELECT w.id, w.title
 FROM websites w;

-- View: detail_websites

-- DROP VIEW detail_websites;

CREATE OR REPLACE VIEW detail_websites AS 
 SELECT w.id, w.title, w.url, w.description,
	w.created_by_id, c.username as created_by, w.updated_by_id, u.username as updated_by	
 FROM websites w
 INNER JOIN users c on c.id=w.created_by_id
 INNER JOIN users u on u.id=w.updated_by_id;
