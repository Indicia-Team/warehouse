select insert_term('Pdf', 'eng', null, 'indicia:media_types');
select insert_term('Pdf:Local', 'eng', null, 'indicia:media_types');

update termlists_terms c
set parent_id=p.id
from terms tc, termlists_terms p
join terms tp on tp.id=p.term_id
join termlists tl on tl.id=p.termlist_id and tl.external_key='indicia:media_types'
where c.termlist_id=p.termlist_id
and tc.term like tp.term || ':%'
and tc.id=c.term_id
and c.parent_id is null;