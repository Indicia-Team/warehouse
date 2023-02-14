-- #slow script#

-- Convert comment source type to query source type where appropriate.

UPDATE notifications n
SET source_type='Q'
FROM occurrence_comments oc
WHERE oc.id=(regexp_matches(n.source_detail, 'oc_id:(\d+)'))[1]::int
AND oc.query=true
AND n.source_type='C'