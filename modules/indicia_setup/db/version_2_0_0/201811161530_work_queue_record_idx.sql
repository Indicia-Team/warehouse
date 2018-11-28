CREATE INDEX ix_work_queue_record_id
  ON work_queue
  USING btree
  (record_id);