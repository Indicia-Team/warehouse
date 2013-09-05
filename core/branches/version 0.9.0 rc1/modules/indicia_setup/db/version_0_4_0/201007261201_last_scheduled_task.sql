ALTER TABLE "system"
   ADD COLUMN last_scheduled_task_check timestamp with time zone NOT NULL DEFAULT now();

COMMENT ON COLUMN "system"."last_scheduled_task_check" IS 'Timestamp of the last time the scheduled task checker was run.';