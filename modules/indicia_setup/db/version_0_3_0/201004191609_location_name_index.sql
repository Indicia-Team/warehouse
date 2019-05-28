-- #slow script#
CREATE INDEX ix_location_name_fulltext ON locations USING gin
    (to_tsvector('simple'::regconfig, quote_literal(quote_literal(name::text))))
    WHERE deleted=false;