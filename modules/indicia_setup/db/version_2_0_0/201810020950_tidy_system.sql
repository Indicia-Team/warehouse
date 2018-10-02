-- Table no longer uses scheduled tasks directly as updated to work_queue.
UPDATE system SET last_scheduled_task_check=NULL WHERE name='spatial_index_builder';