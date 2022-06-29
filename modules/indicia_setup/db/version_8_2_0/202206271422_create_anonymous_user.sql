
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

INSERT INTO users (
  person_id,
  created_on,
  created_by_id,
  updated_on,
  updated_by_id,
  username
)
VALUES (
  (select id from people where external_key = 'indicia:anonymous' AND deleted = false),
  now(),
  1,
  now(),
  1,
  'anonymous'
);