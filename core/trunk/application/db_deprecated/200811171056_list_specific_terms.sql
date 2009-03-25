DROP VIEW IF EXISTS gv_terms;

ALTER TABLE terms
DROP COLUMN parent_id,
DROP COLUMN meaning_id,
DROP COLUMN preferred;

ALTER TABLE termlists_terms
ADD COLUMN parent_id integer, -- Foreign key to the termlist_terms table. For heirarchical data, identifies the parent term.
ADD COLUMN meaning_id integer, -- Foreign key to the meaning table - identifies synonymous terms within this list.
ADD COLUMN preferred BOOLEAN NOT NULL DEFAULT FALSE, -- Flag set to true if the term is the preferred term amongst the group of terms with the same meaning.
ADD COLUMN sort_order INTEGER, -- Allows control of ordering
ADD CONSTRAINT fk_termlists_term_parent FOREIGN KEY (parent_id)
	REFERENCES termlists_terms (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_termlists_term_meaning FOREIGN KEY (meaning_id)
	REFERENCES meanings (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN termlists_terms.parent_id IS 'Foreign key to the termlist_terms table. For heirarchical data, identifies the parent term.';
COMMENT ON COLUMN termlists_terms.meaning_id IS 'Foreign key to the meaning table - identifies synonymous terms within this list.';
COMMENT ON COLUMN termlists_terms.preferred IS 'Flag set to true if the term is the preferred term amongst the group of terms with the same meaning.';
COMMENT ON COLUMN termlists_terms.sort_order IS 'Used to control sort ordering';
