DROP INDEX ix_unique_email_address;

CREATE UNIQUE INDEX ix_unique_email_address ON people (email_address)  WHERE deleted='f' AND email_address !='' AND email_address is not null;