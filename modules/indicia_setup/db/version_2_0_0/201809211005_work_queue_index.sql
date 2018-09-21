CREATE INDEX ix_work_queue_error_detail
  ON work_queue
  USING btree
  (error_detail NULLS LAST);