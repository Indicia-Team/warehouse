CREATE VIEW list_taxon_codes AS 
select tc.id, tc.taxon_meaning_id, tc.code, t.term
from taxon_codes tc
join termlists_terms tlt on tlt.id=tc.code_type_id and tlt.deleted=false
join terms t on t.id=tlt.term_id and t.deleted=false
where tc.deleted=false;

CREATE VIEW gv_taxon_codes AS 
select tc.id, tc.taxon_meaning_id, tc.code, t.term
from taxon_codes tc
join termlists_terms tlt on tlt.id=tc.code_type_id and tlt.deleted=false
join terms t on t.id=tlt.term_id and t.deleted=false
where tc.deleted=false;

CREATE VIEW detail_taxon_codes AS 
select tc.id, tc.taxon_meaning_id, tc.code, t.term, tc.created_by_id, c.username AS created_by, tc.created_on, tc.updated_by_id, u.username AS updated_by, tc.updated_on
from taxon_codes tc
join termlists_terms tlt on tlt.id=tc.code_type_id and tlt.deleted=false
join terms t on t.id=tlt.term_id and t.deleted=false
join users c ON c.id = tc.created_by_id
join users u ON u.id = tc.updated_by_id
where tc.deleted=false;