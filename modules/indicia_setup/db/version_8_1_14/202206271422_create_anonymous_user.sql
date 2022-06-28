
INSERT INTO people (
  first_name,
  surname,
  email_address,
  created_on,
  created_by_id,
  updated_on,
  updated_by_id,
  external_key
)
VALUES (
  'anonymous',
  'anonymous',
  'anonymous@anonymous.anonymous',
  now(),
  1,
  now(),
  1,
  'indicia:anonymous'
);