-- This file updates names for some identifier types.
-- Colour ring is changed to Colour ring combination
-- Darvic ring is changed to Coloured ring

-- SET search_path TO ind01,public;



update terms 
set term = 'Colour ring combination'
where id = (
select t.id
from terms t
join termlists_terms tlt on t.id = tlt.term_id
join termlists tl on tl.id = tlt.termlist_id
where tl.external_key = 'indicia:assoc:identifier_type'
and t.term = 'Colour ring'
)
;
update terms 
set term = 'Coloured ring'
where id = (
select t.id
from terms t
join termlists_terms tlt on t.id = tlt.term_id
join termlists tl on tl.id = tlt.termlist_id
where tl.external_key = 'indicia:assoc:identifier_type'
and t.term = 'Darvic ring'
)
;
