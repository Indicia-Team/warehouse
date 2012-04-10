
CREATE OR REPLACE VIEW list_taxa_taxon_list_attribute_values AS 
         SELECT NULL::unknown AS id, NULL::unknown AS taxa_taxon_list_id, ttla.id AS taxa_taxon_list_attribute_id, 
                CASE ttla.data_type
                    WHEN 'T'::bpchar THEN 'Text'::bpchar
                    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
                    WHEN 'I'::bpchar THEN 'Integer'::bpchar
                    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
                    WHEN 'F'::bpchar THEN 'Float'::bpchar
                    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
                    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
                    WHEN 'G'::bpchar THEN 'Geometry'::bpchar
                    ELSE ttla.data_type
                END AS data_type, ttla.caption, ttla.termlist_id, NULL::unknown AS value, NULL::unknown AS raw_value, NULL::unknown AS iso, tlttla.taxon_list_id
           FROM taxa_taxon_list_attributes ttla
      JOIN taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxa_taxon_list_attribute_id = ttla.id AND tlttla.deleted = false
     WHERE ttla.deleted = false
UNION 
         SELECT ttlav.id, ttl.id AS taxa_taxon_list_id, ttla.id AS taxa_taxon_list_attribute_id, 
                CASE ttla.data_type
                    WHEN 'T'::bpchar THEN 'Text'::bpchar
                    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
                    WHEN 'I'::bpchar THEN 'Integer'::bpchar
                    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
                    WHEN 'F'::bpchar THEN 'Float'::bpchar
                    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
                    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
                    WHEN 'G'::bpchar THEN 'Geometry'::bpchar
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
                    WHEN 'G'::bpchar THEN st_astext(ttlav.geom_value)::character varying::text
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
                    WHEN 'G'::bpchar THEN st_astext(ttlav.geom_value)::character varying::text
                    ELSE NULL::text
                END AS raw_value, l.iso, tlttla.taxon_list_id
           FROM taxa_taxon_lists ttl
      JOIN taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxon_list_id = ttl.taxon_list_id AND tlttla.deleted = false
   JOIN taxa_taxon_list_attributes ttla ON ttla.id = tlttla.taxa_taxon_list_attribute_id AND ttla.deleted = false
   LEFT JOIN taxa_taxon_list_attribute_values ttlav ON ttlav.taxa_taxon_list_attribute_id = ttla.id AND ttlav.taxa_taxon_list_id = ttl.id AND ttlav.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id AND l.deleted = false) ON tt.meaning_id = ttlav.int_value AND ttla.data_type = 'L'::bpchar AND tt.deleted = false
  WHERE ttl.deleted = false;	

