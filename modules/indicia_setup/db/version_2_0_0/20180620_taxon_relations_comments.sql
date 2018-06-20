COMMENT ON TABLE taxon_relations IS
  'Relationships between taxon names as a result of taxonomic changes, e.g. lumping and splitting. Use the taxon '
  'associations module for ecological associations.';

COMMENT ON COLUMN taxon_relations.from_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. Identifies the taxon this relationship is from.';
COMMENT ON COLUMN taxon_relations.to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. Identifies the taxon this relationship is to.';
COMMENT ON COLUMN taxon_relations.taxon_relation_type_id IS
  'Foreign key to the taxon_relation_types table. Identifies the nature of the relationship.';
COMMENT ON COLUMN taxon_relations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_relations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_relations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_relations.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_relations.deleted IS 'Has this record been deleted?';

COMMENT ON TABLE taxon_relation_types IS
  'List of types of relationships between taxon names that result from taxonomic changes, e.g. lumping and splitting.';
COMMENT ON COLUMN taxon_relation_types.caption IS
  'Name of this relationship type.';
COMMENT ON COLUMN taxon_relation_types.forward_term IS
  'Term or phrase when the relationship is read in a forward direction, e.g. was created by splitting.';
COMMENT ON COLUMN taxon_relation_types.reverse_term IS
  'Term or phrase when the relationship is read in a reverse direction, e.g. is split into.';
COMMENT ON COLUMN taxon_relation_types.relation_code IS
  'Code allocated for this relationship type.';
COMMENT ON COLUMN taxon_relation_types.special IS
  'Contains a system readable code when the relationship has special meaning.';
COMMENT ON COLUMN taxon_relation_types.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_relation_types.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_relation_types.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_relation_types.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_relation_types.deleted IS 'Has this record been deleted?';