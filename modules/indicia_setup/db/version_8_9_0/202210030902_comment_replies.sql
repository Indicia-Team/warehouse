ALTER TABLE occurrence_comments
    ADD COLUMN reply_to_id int,
    ADD CONSTRAINT fk_occurrence_comments_reply_to FOREIGN KEY (reply_to_id)
      REFERENCES occurrence_comments (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrence_comments.reply_to_id
    IS 'Comment that this comment is a reply to.';

CREATE INDEX fki_occurrence_comments_reply_to ON occurrence_comments(reply_to_id);

ALTER TABLE location_comments
    ADD COLUMN reply_to_id int,
    ADD CONSTRAINT fk_location_comments_reply_to FOREIGN KEY (reply_to_id)
      REFERENCES location_comments (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN location_comments.reply_to_id
    IS 'Comment that this comment is a reply to.';

CREATE INDEX fki_location_comments_reply_to ON location_comments(reply_to_id);

ALTER TABLE sample_comments
    ADD COLUMN reply_to_id int,
    ADD CONSTRAINT fk_sample_comments_reply_to FOREIGN KEY (reply_to_id)
      REFERENCES sample_comments (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN sample_comments.reply_to_id
    IS 'Comment that this comment is a reply to.';

CREATE INDEX fki_sample_comments_reply_to ON sample_comments(reply_to_id);

ALTER TABLE survey_comments
    ADD COLUMN reply_to_id int,
    ADD CONSTRAINT fk_survey_comments_reply_to FOREIGN KEY (reply_to_id)
      REFERENCES survey_comments (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN survey_comments.reply_to_id
    IS 'Comment that this comment is a reply to.';

CREATE INDEX fki_survey_comments_reply_to ON survey_comments(reply_to_id);