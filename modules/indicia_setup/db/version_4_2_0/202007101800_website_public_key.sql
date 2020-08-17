ALTER TABLE websites ADD COLUMN public_key text;

COMMENT ON COLUMN websites.public_key IS 'Public key for checking signed JWT access tokens in the API.';