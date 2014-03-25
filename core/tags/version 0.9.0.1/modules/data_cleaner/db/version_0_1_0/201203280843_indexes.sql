CREATE INDEX ix_verification_rule_data_key
   ON verification_rule_data ("key" ASC NULLS LAST);

CREATE INDEX ix_verification_rule_data_value
   ON verification_rule_data ("value" ASC NULLS LAST) WHERE "value"<>'-';

CREATE INDEX ix_verification_rule_data_value_geom
  ON verification_rule_data
  USING gist
  (value_geom);
