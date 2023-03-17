-- #slow script#

-- Convert comment source type to query source type where appropriate.

UPDATE notifications n
SET source_type='Q'
FROM occurrence_comments oc
WHERE n.source_detail like 'oc_id:%'
AND oc.id=replace(n.source_detail, 'oc_id:', '')::int
AND oc.query=true
AND n.source_type='C';