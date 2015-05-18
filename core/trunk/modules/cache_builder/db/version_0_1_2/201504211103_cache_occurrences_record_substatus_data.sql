-- SLOW SCRIPT
UPDATE cache_occurrences co set record_status=o.record_status, record_substatus=o.record_substatus
FROM occurrences o
WHERE o.id=co.id
AND (co.record_status<>o.record_status OR o.record_substatus IS NOT NULL);