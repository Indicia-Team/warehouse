-- @todo Do we need synonym field?

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
    output_group character varying,
    marine boolean,
    terrestrial boolean,
    freshwater boolean,
    non_native boolean,
    redundant boolean,
    deleted_date date,
    operation_processed boolean NOT NULL default false,
    batch_processed_on timestamp without time zone,
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

COMMENT ON TABLE uksi_operations IS '';

COMMENT ON COLUMN uksi_operations.id IS '';
COMMENT ON COLUMN uksi_operations.sequence IS '';
COMMENT ON COLUMN uksi_operations.operation IS '';
COMMENT ON COLUMN uksi_operations.error_detail IS '';
COMMENT ON COLUMN uksi_operations.organism_key IS '';
COMMENT ON COLUMN uksi_operations.taxon_version_key IS '';
COMMENT ON COLUMN uksi_operations.rank IS '';
COMMENT ON COLUMN uksi_operations.taxon_name IS '';
COMMENT ON COLUMN uksi_operations.authority IS '';
COMMENT ON COLUMN uksi_operations.attribute IS '';
COMMENT ON COLUMN uksi_operations.parent_organism_key IS '';
COMMENT ON COLUMN uksi_operations.parent_name IS '';
COMMENT ON COLUMN uksi_operations.output_group IS '';
COMMENT ON COLUMN uksi_operations.marine IS '';
COMMENT ON COLUMN uksi_operations.terrestrial IS '';
COMMENT ON COLUMN uksi_operations.freshwater IS '';
COMMENT ON COLUMN uksi_operations.non_native IS '';
COMMENT ON COLUMN uksi_operations.redundant IS '';
COMMENT ON COLUMN uksi_operations.deleted_date IS 'For deprecate name operations, date the name was deprecated.';
COMMENT ON COLUMN uksi_operations.operation_processed IS 'True if this operation has been applied to the database.';
COMMENT ON COLUMN uksi_operations.processed_on IS 'Date and time the operation was applied.';
COMMENT ON COLUMN uksi_operations.batch_processed_on IS 'Date and time the batch of operations was applied to the master copy of UKSI. Used to identify groups of operations to batch together.';
COMMENT ON COLUMN uksi_operations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN uksi_operations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN uksi_operations.created_on IS 'Date this record was updated.';
COMMENT ON COLUMN uksi_operations.created_by_id IS 'Foreign key to the users table (updator).';
COMMENT ON COLUMN uksi_operations.deleted IS 'Has this record been deleted?';
