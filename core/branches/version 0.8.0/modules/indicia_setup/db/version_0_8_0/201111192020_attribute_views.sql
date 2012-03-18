

CREATE OR REPLACE VIEW list_taxa_taxon_list_attributes AS 
 SELECT ta.id, ta.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, ta.data_type, ct.control AS control_type, ta.termlist_id, ta.multi_value, tla.taxon_list_id, (((ta.id || '|'::text) || ta.data_type::text) || '|'::text) || COALESCE(ta.termlist_id::text, ''::text) AS signature, tla.default_text_value, tla.default_int_value, tla.default_float_value, tla.default_date_start_value, tla.default_date_end_value, tla.default_date_type_value, COALESCE((tla.validation_rules::text || '
'::text) || ta.validation_rules::text, COALESCE(tla.validation_rules, ''::character varying)::text || COALESCE(ta.validation_rules, ''::character varying)::text) AS validation_rules, ta.deleted, tla.deleted AS taxon_list_deleted
   FROM taxa_taxon_list_attributes ta
   LEFT JOIN taxon_lists_taxa_taxon_list_attributes tla ON ta.id = tla.taxa_taxon_list_attribute_id AND tla.deleted = false
   LEFT JOIN control_types ct ON ct.id = tla.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = tla.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE ta.deleted = false
  ORDER BY fsb2.weight, fsb.weight, tla.weight;


CREATE OR REPLACE VIEW list_taxa_taxon_list_attribute_values AS 
   SELECT null as id, null as taxa_taxon_list_id, ttla.id as taxa_taxon_list_attribute_id, CASE ttla.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE ttla.data_type
        END AS data_type, ttla.caption, ttla.termlist_id,
        null as value, 
        null as raw_value,
        null as iso, tlttla.taxon_list_id
   FROM taxa_taxon_list_attributes ttla
   JOIN taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxa_taxon_list_attribute_id=ttla.id AND tlttla.deleted=false
   WHERE ttla.deleted=false
   UNION
   SELECT ttlav.id, ttl.id as taxa_taxon_list_id, ttla.id as taxa_taxon_list_attribute_id, CASE ttla.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE ttla.data_type
        END AS data_type, ttla.caption, ttla.termlist_id,
        CASE ttla.data_type
            WHEN 'T'::bpchar THEN ttlav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN ttlav.int_value::character varying::text
            WHEN 'B'::bpchar THEN ttlav.int_value::character varying::text
            WHEN 'F'::bpchar THEN ttlav.float_value::character varying::text
            WHEN 'D'::bpchar THEN ttlav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (ttlav.date_start_value::character varying::text || ' - '::text) || ttlav.date_end_value::character varying::text
            ELSE NULL::text
        END AS value, 
        CASE ttla.data_type
            WHEN 'T'::bpchar THEN ttlav.text_value
            WHEN 'L'::bpchar THEN ttlav.int_value::character varying::text
            WHEN 'I'::bpchar THEN ttlav.int_value::character varying::text
            WHEN 'B'::bpchar THEN ttlav.int_value::character varying::text
            WHEN 'F'::bpchar THEN ttlav.float_value::character varying::text
            WHEN 'D'::bpchar THEN ttlav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (ttlav.date_start_value::character varying::text || ' - '::text) || ttlav.date_end_value::character varying::text
            ELSE NULL::text
        END AS raw_value,
        l.iso, tlttla.taxon_list_id
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxon_list_id=ttl.taxon_list_id AND tlttla.deleted=false
   JOIN taxa_taxon_list_attributes ttla ON ttla.id=tlttla.taxa_taxon_list_attribute_id AND ttla.deleted=false
   LEFT JOIN taxa_taxon_list_attribute_values ttlav 
       ON ttlav.taxa_taxon_list_attribute_id=ttla.id
       AND ttlav.taxa_taxon_list_id=ttl.id
       AND ttlav.deleted=false
   LEFT JOIN (termlists_terms tt
     JOIN terms t ON t.id = tt.term_id AND t.deleted=false
     JOIN languages l ON l.id = t.language_id AND l.deleted=false) ON tt.meaning_id = ttlav.int_value AND ttla.data_type = 'L'::bpchar AND tt.deleted=false
  WHERE ttl.deleted=false;