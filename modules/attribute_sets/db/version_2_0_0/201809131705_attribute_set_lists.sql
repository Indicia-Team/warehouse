ALTER TABLE attribute_sets
  ADD COLUMN taxon_list_id integer,
  ADD CONSTRAINT fk_attribute_set_taxon_list FOREIGN KEY (taxon_list_id)
      REFERENCES taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN attribute_sets.taxon_list_id IS
  'Links a set of attributes to a taxon list. If populated then the attribute sets code '
  'can create links in the taxa_taxon_list_attribute_taxon_restrictions table to keep '
  'things in sync.';