-- This file is required because the titles table id column has not been bound to the sequence.
ALTER TABLE titles ALTER COLUMN id SET DEFAULT nextval('titles_id_seq'::regclass);


