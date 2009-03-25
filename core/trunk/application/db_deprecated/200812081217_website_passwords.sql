ALTER TABLE websites ADD COLUMN "password" character varying(30);

-- Set a default value to allow us to make this non-null
UPDATE websites SET "password"='password';

ALTER TABLE websites
	ALTER COLUMN "password" SET NOT NULL;
	
COMMENT ON COLUMN websites."password" IS 'Encrypted password for the website. Enables secure access to services.';
