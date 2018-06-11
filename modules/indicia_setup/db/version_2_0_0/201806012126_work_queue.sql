CREATE TABLE work_queue
(
  id serial NOT NULL,
  task character varying NOT NULL, -- Name of the task to be performed.
  entity character varying, -- Name of the database entity associated with the task where relevant.
  record_id integer, -- ID of the record associated with the task where relevant.
  params json, -- Additional parameters to pass to the function performing the task.
  cost_estimate integer, -- Indication of the cost of the operation from 1 to 100. Cheap/fast operations can be assigned a cost < 10, expensive/slow operations can be assigned a cost of > 50. Used to aid load management.
  priority integer, -- Lower values are given a higher priority.
  created_on timestamp without time zone, -- Date/time the task was queued.
  claimed_on timestamp without time zone, -- Date/time that a worker claimed the task for processing.
  claimed_by character varying, -- Unique ID of the worker claiming the item. Allows workers to ensure they only work on items they claim.
  error_detail character varying, -- If the task processing resulted in an error, then details are logged here.
  CONSTRAINT pk_work_queue PRIMARY KEY (id),
  CONSTRAINT chk_cost_estimate_range CHECK (cost_estimate between 1 and 100)
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE work_queue IS 'A queue of tasks to be performed in the background, e.g. cache table updates.';
COMMENT ON COLUMN work_queue.task IS 'Name of the task to be performed.';
COMMENT ON COLUMN work_queue.entity IS 'Name of the database entity associated with the task where relevant.';
COMMENT ON COLUMN work_queue.record_id IS 'ID of the record associated with the task where relevant.';
COMMENT ON COLUMN work_queue.params IS 'Additional Parameters to pass to the function performing the task.';
COMMENT ON COLUMN work_queue.cost_estimate IS 'Indication of the estimated cost of the operation from 1 to 100. '
  'Cheap/fast operations can be assigned a cost < 10, expensive/slow operations can be assigned a cost of > 50. Used '
  'to aid load management.';
COMMENT ON COLUMN work_queue.priority IS 'Lower values are given a higher priority.';
COMMENT ON COLUMN work_queue.created_on IS 'Date/time the task was queued.';
COMMENT ON COLUMN work_queue.claimed_on IS 'Date/time that a worker claimed the task for processing.';
COMMENT ON COLUMN work_queue.claimed_by IS 'Unique ID of the worker claiming the item. Allows workers to ensure they '
  'only work on items they claim.';
COMMENT ON COLUMN work_queue.error_detail IS 'If the task processing resulted in an error, then details are logged here.';

CREATE INDEX ix_work_queue_priority
  ON work_queue
  USING btree
  (priority);
CREATE INDEX ix_work_queue_created_on
  ON work_queue
  USING btree
  (created_on);
CREATE INDEX ix_work_queue_claimed_by
  ON work_queue
  USING btree
  (claimed_by_on);
CREATE UNIQUE INDEX ix_work_queue_unique_task ON work_queue (task, COALESCE(entity, ''), COALESCE(record_id, 0), COALESCE((params::text), '')) WHERE (claimed_by is null);