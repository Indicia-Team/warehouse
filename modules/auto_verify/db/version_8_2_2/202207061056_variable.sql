-- Ensure that the switch of tracking method doesn't cause it to restart from 0.
INSERT INTO variables(name, value)
SELECT 'auto_verify_last_tracking', '[' || o.tracking || ']'
FROM cache_occurrences_functional o
JOIN system s ON s.name='auto_verify' AND o.updated_on<=s.last_scheduled_task_check
ORDER BY o.updated_on DESC LIMIT 1