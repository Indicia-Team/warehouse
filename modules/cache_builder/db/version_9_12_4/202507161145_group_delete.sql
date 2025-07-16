INSERT INTO work_queue(task, entity, record_id, priority, cost_estimate, created_on)
SELECT 'task_group_delete', 'group', id, 3, 100, now()
FROM groups
WHERE deleted=true;