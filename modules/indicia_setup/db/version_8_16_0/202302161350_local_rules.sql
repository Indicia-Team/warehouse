CREATE TABLE IF NOT EXISTS custom_verification_rulesets
(
    id serial NOT NULL,
    title character varying NOT NULL,
    description text,
    fail_icon character varying NOT NULL,
    fail_message text NOT NULL,
    limit_to_stages character varying[],
    limit_to_geography json,
    website_id integer NOT NULL,
    created_on timestamp without time zone,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone,
    updated_by_id integer NOT NULL,
    deleted boolean NOT NULL DEFAULT false,
    CONSTRAINT pk_custom_verification_rulesets PRIMARY KEY (id),
    CONSTRAINT fk_custom_verification_rulesets_website FOREIGN KEY (website_id)
        REFERENCES websites (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_custom_verification_rulesets_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_custom_verification_rulesets_updator FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE custom_verification_rulesets
  IS 'Sets of verification rules for checking occurrences that can be run together by the verifier that created the set.';

COMMENT ON COLUMN custom_verification_rulesets.title IS 'Title of the ruleset.';
COMMENT ON COLUMN custom_verification_rulesets.description IS 'Description of the ruleset.';
COMMENT ON COLUMN custom_verification_rulesets.fail_icon IS 'Name of the icon to show for a rule failuer, e.g. calendar.';
COMMENT ON COLUMN custom_verification_rulesets.fail_message IS 'Message to show for a failure of any rule in this ruleset, unless overridden by the rule message field.';
COMMENT ON COLUMN custom_verification_rulesets.limit_to_stages IS 'If this ruleset only applies to certain life stages, list the stage terms here.';
COMMENT ON COLUMN custom_verification_rulesets.limit_to_geography IS
  'If this ruleset only applies to a geopgraphic area, define the area here, either by constraining to a range by
  latitude/longitude, or by defining the IDs of higher geogprahy locations that the ruleset is applied to. JSON object,
  possible properties are min_lat, min_lng, max_lat, max_lng, higher_geography_ids (array of IDs).';
COMMENT ON COLUMN custom_verification_rulesets.website_id IS 'Website this ruleset was created for.';
COMMENT ON COLUMN custom_verification_rulesets.created_on IS 'Date this record was created.';
COMMENT ON COLUMN custom_verification_rulesets.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN custom_verification_rulesets.updated_on IS 'Date this record was updated.';
COMMENT ON COLUMN custom_verification_rulesets.updated_by_id IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN custom_verification_rulesets.deleted IS 'Has this record been deleted?';

CREATE TABLE IF NOT EXISTS custom_verification_rules
(
    id serial NOT NULL,
    custom_verification_ruleset_id integer NOT NULL,
    taxon_external_key character varying NOT NULL,
    fail_icon character varying,
    fail_message text,
    limit_to_stages character varying[],
    limit_to_geography json,
    rule_type character varying NOT NULL,
    reverse_rule boolean default false,
    definition json NOT NULL,
    created_on timestamp without time zone,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone,
    updated_by_id integer NOT NULL,
    deleted boolean NOT NULL DEFAULT false,
    CONSTRAINT pk_custom_verification_rules PRIMARY KEY (id),
    CONSTRAINT fk_custom_verification_rules_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_custom_verification_rules_updator FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT chk_custom_verification_rules_rule_type CHECK (rule_type=ANY(ARRAY['abundance','geography','phenology','period','species_recorded']))
);

COMMENT ON TABLE custom_verification_rules
  IS 'Custom verification rules for a specific taxon within a ruleset.';

COMMENT ON COLUMN custom_verification_rules.custom_verification_ruleset_id IS 'Ruleset this rule belongs to.';
COMMENT ON COLUMN custom_verification_rules.taxon_external_key IS 'External key of the taxon the rule applies to.';
COMMENT ON COLUMN custom_verification_rulesets.fail_icon IS 'Optional name of the icon to show for a rule failure, e.g. calendar. Overrides the one specified for the ruleset when this specific rule is triggered.';
COMMENT ON COLUMN custom_verification_rules.fail_message IS 'Optional message for failures of this rule. Overrides the one specified for the ruleset when this specific rule is triggered.';
COMMENT ON COLUMN custom_verification_rules.limit_to_stages IS
  'If this rule only applies to certain life stages, list the stage terms here. If the ruleset also defines a stage
  limit, then a record stage must match one of the rule stages AND one of the ruleset stages in order to be checked.';
COMMENT ON COLUMN custom_verification_rules.limit_to_geography IS
  'If this rule only applies to a geopgraphic area, define the area here, either by constraining to a range by
  latitude/longitude, or by defining the IDs of higher geogprahy locations that the ruleset is applied to. JSON object,
  possible properties are min_lat, min_lng, max_lat, max_lng, higher_geography_ids (array of IDs). If the ruleset
  also defines a geography limit, then a record must match the geography constraints of both the rule and the ruleset
  in order to be checked.';
COMMENT ON COLUMN custom_verification_rules.rule_type IS 'Type of rule, either abundance, geography, phenology, period or species_recorded.';
COMMENT ON COLUMN custom_verification_rules.reverse_rule IS 'Set to true to trigger a check failure if the conditions are met rather than if the conditions are not met.';
COMMENT ON COLUMN custom_verification_rules.definition IS 'JSON object defining the rule conditions. Properties depend on the rule type.';
COMMENT ON COLUMN custom_verification_rules.created_on IS 'Date this record was created.';
COMMENT ON COLUMN custom_verification_rules.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN custom_verification_rules.updated_on IS 'Date this record was updated.';
COMMENT ON COLUMN custom_verification_rules.updated_by_id IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN custom_verification_rules.deleted IS 'Has this record been deleted?';

CREATE OR REPLACE VIEW list_custom_verification_rulesets AS
  SELECT rs.id, rs.title, rs.description, rs.fail_icon, rs.fail_message, rs.limit_to_stages, rs.limit_to_geography, rs.website_id
  FROM custom_verification_rulesets rs
  WHERE rs.deleted = false;