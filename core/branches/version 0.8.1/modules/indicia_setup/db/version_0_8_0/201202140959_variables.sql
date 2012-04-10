-- Table: variables

-- DROP TABLE variables;

CREATE TABLE variables
(
  id serial NOT NULL, -- Unique identifier for the variables table.
  "name" character varying NOT NULL, -- Variable name.
  "value" character varying NOT NULL, -- Variable value stored as JSON.
  CONSTRAINT pk_variables PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

CREATE UNIQUE INDEX ix_variable_name
   ON variables ("name" ASC NULLS LAST);

COMMENT ON COLUMN variables.id IS 'Unique identifier for the variables table.';
COMMENT ON COLUMN variables."name" IS 'Variable name.';
COMMENT ON COLUMN variables."value" IS 'Variable value stored as JSON.';

