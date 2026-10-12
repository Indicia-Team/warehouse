-- Before adding constraint, ensure no existing queue records will cause it to fail.
UPDATE work_queue SET priority=2 WHERE priority > 3;

ALTER TABLE work_queue
  ADD CONSTRAINT chk_work_queue_priority CHECK (priority >= 1 AND priority <= 3);

COMMENT ON COLUMN work_queue.priority IS 'Work queue task item prioprity. 1=high, 2=medium, 3=low.';