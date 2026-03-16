INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Basis of record terms', 'Controlled terminology for occurrences.basis_of_record (see http://rs.tdwg.org/dwc/terms/basisOfRecord).', now(), 1, now(), 1, 'indicia:basis_of_record');

SELECT insert_term('PreservedSpecimen', 'eng', 1, null, 'indicia:basis_of_record');
SELECT insert_term('FossilSpecimen', 'eng', 2, null, 'indicia:basis_of_record');
SELECT insert_term('LivingSpecimen', 'eng', 3, null, 'indicia:basis_of_record');
SELECT insert_term('MaterialSample', 'eng', 4, null, 'indicia:basis_of_record');
SELECT insert_term('HumanObservation', 'eng', 5, null, 'indicia:basis_of_record');
SELECT insert_term('MachineObservation', 'eng', 6, null, 'indicia:basis_of_record');

ALTER TABLE occurrences
  ADD COLUMN basis_of_record_id integer,
  ADD CONSTRAINT fk_occurrences_basis_of_record FOREIGN KEY (basis_of_record_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrences.basis_of_record_id IS 'The basis of record for the occurrence as per dwc:basisOfRecord. This is a foreign key to the termlists_terms table.';