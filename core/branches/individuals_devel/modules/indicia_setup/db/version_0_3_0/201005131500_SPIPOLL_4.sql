DROP VIEW IF EXISTS list_determinations;

ALTER TABLE determinations
ADD COLUMN dubious character(1) NOT NULL DEFAULT 'N'::bpchar, --Flag to indicate whether the determination has been flagged as dubious;
ADD CONSTRAINT determinations_dubious_check CHECK (dubious = ANY (ARRAY['N'::bpchar, 'Y'::bpchar]));

COMMENT ON COLUMN occurrences.use_determination IS 'Flag to indicate whether the determination has been flagged as dubious';

CREATE OR REPLACE VIEW list_determinations AS 
 SELECT d.id, d.taxa_taxon_list_id, t.taxon, d.taxon_text_description, d.taxon_extra_info, d.occurrence_id,
 		d.email_address, d.person_name, d.cms_ref, d.deleted, d.updated_on, d.dubious, o.website_id
   FROM determinations d
   JOIN occurrences o ON d.occurrence_id = o.id
   LEFT JOIN taxa_taxon_lists ttl ON d.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   ;
