-- #slow script#

--  Indexes improve query selectVerificationAndCommentNotifications in notify_verifications_and_comments module.
CREATE INDEX ix_cache_occurrences_functional_verified_on
  ON cache_occurrences_functional
  USING btree
  (verified_on);