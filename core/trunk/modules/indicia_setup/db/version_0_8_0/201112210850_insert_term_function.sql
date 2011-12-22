-- Function: insert_term(character varying, character, integer, character varying)

CREATE OR REPLACE FUNCTION insert_term(t character varying, lang_iso character, sort_order integer, list integer, list_external_key character varying)
  RETURNS integer AS
$BODY$
DECLARE
  m_id integer;
  t_id integer;
  l_id integer;
  ttl_id integer;
BEGIN
  l_id := CASE WHEN list IS NULL THEN (SELECT id FROM termlists WHERE external_key=list_external_key) ELSE list END;

  t_id := (SELECT id FROM terms WHERE term=t AND language_id=(SELECT id from languages WHERE iso = lang_iso) ORDER BY id LIMIT 1);

  IF t_id IS NULL THEN
    t_id := nextval('terms_id_seq'::regclass);

    INSERT INTO terms (id, term, language_id, created_on, created_by_id, updated_on, updated_by_id)
    VALUES (t_id, t, (SELECT id from languages WHERE iso = lang_iso), now(), 1, now(), 1);
  END IF;

  ttl_id := id FROM termlists_terms WHERE term_id=t_id AND termlist_id=l_id AND preferred='t' LIMIT 1;

  IF ttl_id IS NULL THEN

    m_id := nextval('meanings_id_seq'::regclass);

    INSERT INTO meanings VALUES (m_id);

    INSERT INTO termlists_terms (term_id, termlist_id, meaning_id, preferred, sort_order, created_on, created_by_id, updated_on, updated_by_id)
    VALUES (t_id, l_id, m_id, 't', sort_order, now(), 1, now(), 1);
  END IF;

  RETURN 1;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
