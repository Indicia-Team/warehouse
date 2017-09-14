-- #slow script#

--  Indexes improve query selectVerificationAndCommentNotifications in notify_verifications_and_comments module.
CREATE INDEX ix_occurrence_comments_created_on
  ON occurrence_comments
  USING btree
  (created_on);

-- Indexes to improve queries in verifier_notifications module
CREATE INDEX ix_notifications_user_id_partial_vt
  ON notifications
  USING btree
  (user_id)
  WHERE source_type IN ('VT', 'PT') and acknowledged=false;