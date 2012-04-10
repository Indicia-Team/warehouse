
CREATE OR REPLACE VIEW gv_user_identifiers AS 
 SELECT um.id, um.identifier, um.user_id, u.person_id, t.term as type
   FROM user_identifiers um
   JOIN users u ON u.id=um.user_id and u.deleted=false
   JOIN termlists_terms tlt on tlt.id=um.type_id and tlt.deleted=false
   JOIN terms t on t.id=tlt.term_id and t.deleted=false
  WHERE um.deleted = false;