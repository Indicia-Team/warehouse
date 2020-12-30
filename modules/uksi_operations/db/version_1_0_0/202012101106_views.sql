CREATE VIEW gv_uksi_operations AS
  SELECT id,
    sequence,
    operation,
    taxon_name,
    case when operation_processed = 't' then 'Yes' else null end as operation_processed,
    case when error_detail is not null then 'Yes' else null end as has_errors,
    batch_processed_on::date
  FROM uksi_operations
  WHERE deleted=false;