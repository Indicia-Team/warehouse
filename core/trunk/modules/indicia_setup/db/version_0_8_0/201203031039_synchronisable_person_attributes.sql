ALTER TABLE person_attributes
   ADD COLUMN synchronisable boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN person_attributes.synchronisable IS 'Does this attribute allow the values to be synchronised with client website users associated with the person? Set to false for attributes that should keep their values on the warehouse only.';
