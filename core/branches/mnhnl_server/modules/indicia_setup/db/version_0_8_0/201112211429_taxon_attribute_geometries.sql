COMMENT ON COLUMN taxa_taxon_list_attributes.data_type IS 'Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist), G (geometry).';

ALTER TABLE taxa_taxon_list_attribute_values ADD COLUMN geom_value geometry;
COMMENT ON COLUMN taxa_taxon_list_attribute_values.geom_value IS 'For geometry values, stores the geometry.';