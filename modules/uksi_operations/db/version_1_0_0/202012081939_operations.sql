
CREATE TABLE uksi_operations
(
    id serial NOT NULL,
    sequence integer NOT NULL,
    operation character varying NOT NULL,
    error_detail character varying,
    organism_key character(16),
    taxon_version_key character(16),
    rank character varying,
    taxon_name character varying,
    authority character varying,
    attribute character varying,
    parent_organism_key character(16),
    parent_name character varying,
    synonym character varying,
    taxon_group_key character(16),
    marine boolean,
    terrestrial boolean,
    freshwater boolean,
    non_native boolean,
    redundant boolean,
    deleted_date date,
    operation_processed boolean NOT NULL default false,
    batch_processed_on timestamp without time zone NOT NULL,
    processed_on timestamp without time zone,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean NOT NULL DEFAULT false,
    PRIMARY KEY (id),
    CONSTRAINT fk_uksi_operations_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE uksi_operations IS 'List of operations to apply to the UKSI taxonomy information on this warehouse.';

COMMENT ON COLUMN uksi_operations.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN uksi_operations.sequence IS 'Sequence order the operation should be applied in, within the operations where batch_processed_on indicates they were processed on the same date.';
COMMENT ON COLUMN uksi_operations.operation IS 'Name of the operation to apply.';
COMMENT ON COLUMN uksi_operations.error_detail IS 'For operations that have been applied but failed, details of the error.';
COMMENT ON COLUMN uksi_operations.organism_key IS 'Organism key for operations which affect a taxon concept, where a new organism_key was generated during processeing.';
COMMENT ON COLUMN uksi_operations.taxon_version_key IS 'For operations that affect a specific name, Taxon Version Key of the name.';
COMMENT ON COLUMN uksi_operations.rank IS 'Taxon rank, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.taxon_name IS 'Taxon name, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.authority IS 'Taxon authority, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.attribute IS 'Taxon attribute, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.parent_organism_key IS 'Taxon parent organism key, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.parent_name IS 'Taxon parent name, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.synonym IS 'For promote name operations, the TVK of the name being promoted. For merge taxa operations, the organism key of the taxon being merged into another and relegated to junior synonym.';
COMMENT ON COLUMN uksi_operations.taxon_group_key IS 'UKSI key for the taxon group, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.marine IS 'Taxon marine flag, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.terrestrial IS 'Taxon terrestrial flag, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.freshwater IS 'Taxon freshwater flag, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.non_native IS 'Taxon non-native flag, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.redundant IS 'Taxon redundant, if needed by the operation.';
COMMENT ON COLUMN uksi_operations.deleted_date IS 'For deprecate name operations, date the name was deprecated.';
COMMENT ON COLUMN uksi_operations.operation_processed IS 'True if this operation has been applied to the database.';
COMMENT ON COLUMN uksi_operations.processed_on IS 'Date and time the operation was applied.';
COMMENT ON COLUMN uksi_operations.batch_processed_on IS 'Date and time the batch of operations was applied to the master copy of UKSI. Used to identify groups of operations to batch together.';
COMMENT ON COLUMN uksi_operations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN uksi_operations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN uksi_operations.created_on IS 'Date this record was updated.';
COMMENT ON COLUMN uksi_operations.created_by_id IS 'Foreign key to the users table (updator).';
COMMENT ON COLUMN uksi_operations.deleted IS 'Has this record been deleted?';