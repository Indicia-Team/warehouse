CREATE OR REPLACE VIEW gv_location_images AS 
 SELECT location_images.id, location_images.path, location_images.caption, location_images.deleted, location_images.location_id
   FROM location_images
   WHERE deleted=false;

CREATE OR REPLACE VIEW gv_occurrences AS 
 SELECT o.id, w.title AS website, s.title AS survey, o.sample_id, t.taxon, sa.date_start, sa.date_end, sa.date_type, sa.entered_sref, sa.entered_sref_system, sa.location_name, l.name, o.deleted, o.website_id
   FROM occurrences o
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN samples sa ON o.sample_id = sa.id AND sa.deleted=false
   JOIN websites w ON w.id = o.website_id AND w.deleted=false
   JOIN surveys s ON s.id = sa.survey_id AND s.deleted=false
   LEFT JOIN locations l ON sa.location_id = l.id AND l.deleted=false
   WHERE o.deleted=false;

CREATE OR REPLACE VIEW gv_sample_attribute_by_surveys AS 
 SELECT fsb2.weight AS weight1, fsb.weight AS weight2, saw.weight AS weight3, sa.id, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, saw.website_id, saw.restrict_to_survey_id AS survey_id, w.title AS website, s.title AS survey, sa.caption, 
        CASE sa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE sa.data_type
        END AS data_type, ct.control, sa.public, sa.created_by_id, sa.deleted
   FROM sample_attributes sa
   JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id AND saw.deleted = false
   JOIN websites w ON w.id = saw.website_id AND w.deleted=false
   JOIN surveys s ON s.id = saw.restrict_to_survey_id AND s.deleted=false
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE sa.deleted=false
  ORDER BY fsb2.weight, fsb.weight, saw.weight, sa.caption;

CREATE OR REPLACE VIEW gv_sample_images AS 
 SELECT sample_images.id, sample_images.path, sample_images.caption, sample_images.deleted, sample_images.sample_id
   FROM sample_images
   WHERE deleted=false;

CREATE OR REPLACE VIEW gv_samples AS 
 SELECT s.id, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, s.location_name, s.deleted, su.title AS survey, w.title AS website, l.name AS location, su.website_id
   FROM samples s
   JOIN surveys su ON s.survey_id = su.id AND su.deleted=false
   JOIN websites w ON w.id = su.website_id AND w.deleted=false
   LEFT JOIN locations l ON s.location_id = l.id
   WHERE s.deleted=false;

CREATE OR REPLACE VIEW gv_taxon_groups_taxon_lists AS 
 SELECT tgtl.id, tg.id AS taxon_group_id, tg.title, tgtl.deleted, tgtl.taxon_list_id
   FROM taxon_groups_taxon_lists tgtl
   JOIN taxon_groups tg ON tg.id = tgtl.taxon_group_id AND tg.deleted = false
   WHERE tgtl.deleted=false;

CREATE OR REPLACE VIEW gv_taxon_relations AS 
   SELECT tr.id, tr.from_taxon_meaning_id AS my_taxon_meaning_id, t1.taxon AS my_taxon, tr.to_taxon_meaning_id AS other_taxon_meaning_id, t2.taxon AS other_taxon, tr.taxon_relation_type_id, trt.forward_term AS term, tr.deleted
           FROM taxon_relations tr
   JOIN taxon_relation_types trt ON tr.taxon_relation_type_id = trt.id AND trt.deleted = false
   JOIN taxa_taxon_lists ttl1 ON ttl1.taxon_meaning_id = tr.from_taxon_meaning_id AND ttl1.preferred = true AND ttl1.deleted = false
   JOIN taxa t1 ON t1.id = ttl1.taxon_id AND t1.deleted = false
   JOIN taxa_taxon_lists ttl2 ON ttl2.taxon_meaning_id = tr.to_taxon_meaning_id AND ttl2.preferred = true AND ttl2.deleted = false
   JOIN taxa t2 ON t2.id = ttl2.taxon_id AND t2.deleted = false
   WHERE tr.deleted=false
UNION ALL 
   SELECT tr.id, tr.to_taxon_meaning_id AS my_taxon_meaning_id, t2.taxon AS my_taxon, tr.from_taxon_meaning_id AS other_taxon_meaning_id, t1.taxon AS other_taxon, tr.taxon_relation_type_id, trt.reverse_term AS term, tr.deleted
           FROM taxon_relations tr
   JOIN taxon_relation_types trt ON tr.taxon_relation_type_id = trt.id AND trt.deleted = false
   JOIN taxa_taxon_lists ttl1 ON ttl1.taxon_meaning_id = tr.from_taxon_meaning_id AND ttl1.preferred = true AND ttl1.deleted = false
   JOIN taxa t1 ON t1.id = ttl1.taxon_id AND t1.deleted = false
   JOIN taxa_taxon_lists ttl2 ON ttl2.taxon_meaning_id = tr.to_taxon_meaning_id AND ttl2.preferred = true AND ttl2.deleted = false
   JOIN taxa t2 ON t2.id = ttl2.taxon_id AND t2.deleted = false
   WHERE tr.deleted=false;

CREATE OR REPLACE VIEW gv_term_termlists AS 
 SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id, tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id, tt.preferred, tt.sort_order, t.title, t.description
   FROM termlists_terms tt
   JOIN termlists t ON tt.termlist_id = t.id AND t.deleted=false
   WHERE tt.deleted=false;

CREATE OR REPLACE VIEW gv_termlists_terms AS 
 SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id, tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id, tt.preferred, tt.sort_order, tt.deleted, t.term, l.language
   FROM termlists_terms tt
   JOIN terms t ON tt.term_id = t.id AND t.deleted=false
   JOIN languages l ON t.language_id = l.id AND l.deleted=false
   WHERE tt.deleted=false;