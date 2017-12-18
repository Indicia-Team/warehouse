ALTER TABLE occurrence_comments
ADD COLUMN comment_type_id integer,
ADD CONSTRAINT fk_comment_type_id FOREIGN KEY (comment_type_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN occurrence_comments.comment_type_id IS
  'Points to a termlists_term which describes the type of the comment.';

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Comment types', 'Lookup list of comment types.', now(), 1, now(), 1, 'indicia:comment_types');
 
SELECT insert_term('comment', 'eng', null, 'indicia:comment_types');
SELECT insert_term('email', 'eng', null, 'indicia:comment_types');