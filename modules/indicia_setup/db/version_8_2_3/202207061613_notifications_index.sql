-- #slow script#

CREATE INDEX ix_notifications_digest_mode
ON notifications(digest_mode)
WHERE acknowledged='f' AND digest_mode IS NOT NULL;