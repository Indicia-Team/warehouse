
DROP VIEW IF EXISTS list_determinations;

ALTER TABLE determinations
DROP CONSTRAINT determinations_dubious_check;

-- determination_type can be one of:;
-- 'A' : Considered correct;
-- 'B' : Considered incorrect;
-- 'C' : Correct;
-- 'I' : Incorrect;
-- 'R' : Requires confirmation;
-- 'U' : Unconfirmed;
-- 'X' : Unidentified;

ALTER TABLE determinations
RENAME COLUMN dubious TO determination_type;

ALTER TABLE determinations
RENAME COLUMN taxon_text_description TO comment;

ALTER TABLE determinations
ADD COLUMN taxon_details text,
ADD COLUMN taxa_taxon_list_id_list integer[];
-- Should put in FK consraint between array and taxa_taxon_lists;

UPDATE determinations SET determination_type = 'A' where determination_type = 'N';
UPDATE determinations SET determination_type = 'B' where determination_type = 'Y';

ALTER TABLE determinations
ADD CONSTRAINT determinations_determination_type_check CHECK (determination_type = ANY (ARRAY['A'::bpchar, 'B'::bpchar, 'C'::bpchar, 'I'::bpchar, 'R'::bpchar, 'U'::bpchar, 'X'::bpchar]));

CREATE OR REPLACE VIEW list_determinations AS 
 SELECT d.id, d.taxa_taxon_list_id, t.taxon, d.comment, d.taxon_extra_info, d.occurrence_id, d.taxon_details, d.taxa_taxon_list_id_list,
 		d.email_address, d.person_name, d.cms_ref, d.deleted, d.updated_on, d.determination_type, o.website_id
   FROM determinations d
   JOIN occurrences o ON d.occurrence_id = o.id
   LEFT JOIN taxa_taxon_lists ttl ON d.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   ;
   