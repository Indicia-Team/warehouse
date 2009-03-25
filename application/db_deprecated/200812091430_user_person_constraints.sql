
ALTER TABLE users ADD CONSTRAINT unique_username UNIQUE (username);
ALTER TABLE people ALTER COLUMN first_name SET NOT NULL;
ALTER TABLE people ADD CONSTRAINT unique_email UNIQUE (email_address);

