ALTER TABLE "system"
   ALTER COLUMN last_scheduled_task_check DROP NOT NULL;

ALTER TABLE "system"
   ALTER COLUMN last_scheduled_task_check DROP DEFAULT;
