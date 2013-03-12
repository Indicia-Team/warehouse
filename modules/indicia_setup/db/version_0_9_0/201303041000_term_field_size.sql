-- #postgres user#
-- Hacky but efficient way to increase terms.term field size across all dependencies
update pg_attribute set atttypmod=204 where attname='term' and atttypmod=104 and atttypid=1043