ALTER TABLE scratchpad_lists
  ADD COLUMN scratchpad_type_id integer,
  ADD CONSTRAINT fk_scratchpad_lists_type FOREIGN KEY (scratchpad_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

CREATE INDEX fki_scratchpad_lists_type
  ON scratchpad_lists
  USING btree
  (scratchpad_type_id);

COMMENT ON COLUMN scratchpad_lists.scratchpad_type_id IS 'Categorisation of the scratchpad list, which may be used to indicate purpose';

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Scratchpad list types', 'Definitions of types of scratchpad list.', now(), 1, now(), 1, 'indicia:scratchpad_list_types');

SELECT insert_term('Site species list', 'eng', 1, null, 'indicia:scratchpad_list_types');
SELECT insert_term('Sensitive species list', 'eng', 1, null, 'indicia:scratchpad_list_types');

CREATE OR REPLACE VIEW list_scratchpad_lists AS
 SELECT s.id,
    s.title,
    s.description,
    s.entity,
    s.website_id,
    s.expires_on,
    s.scratchpad_type_id,
    t.term as scratchpad_type_term
   FROM scratchpad_lists s
   LEFT JOIN cache_termlists_terms t ON t.id=s.scratchpad_type_id
  WHERE s.deleted = false;