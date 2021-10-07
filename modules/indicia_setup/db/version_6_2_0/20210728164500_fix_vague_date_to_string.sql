CREATE OR REPLACE FUNCTION vague_date_to_string(
	date_start date,
	date_end date,
	date_type character varying)
    RETURNS character varying
    LANGUAGE 'plpgsql'
    COST 100
    IMMUTABLE PARALLEL UNSAFE
AS $BODY$
DECLARE
  DAY_FORMAT CONSTANT varchar := 'DD/MM/YYYY';
  YEAR_FORMAT CONSTANT varchar := 'YYYY';
  MONTH_FORMAT CONSTANT varchar := 'MM/YYYY';
BEGIN
  CASE date_type
	WHEN 'D'::bpchar THEN
		RETURN to_char(date_start, DAY_FORMAT);
	WHEN 'DD'::bpchar THEN
		RETURN to_char(date_start, DAY_FORMAT) || ' to '::text || to_char(date_end, DAY_FORMAT);
	WHEN 'O'::bpchar THEN
		RETURN to_char(date_start, MONTH_FORMAT);
	WHEN 'OO'::bpchar THEN
		RETURN to_char(date_start, MONTH_FORMAT) || ' to '::text || to_char(date_end, MONTH_FORMAT);
	WHEN 'Y'::bpchar THEN
		RETURN to_char(date_start, YEAR_FORMAT);
	WHEN 'YY'::bpchar THEN
		RETURN to_char(date_start, YEAR_FORMAT) || ' to '::text || to_char(date_end, YEAR_FORMAT);
	WHEN 'Y-'::bpchar THEN
		RETURN 'From ' || to_char(date_start, YEAR_FORMAT);
	WHEN '-Y'::bpchar THEN
		RETURN 'To ' || to_char(date_end, YEAR_FORMAT);
	WHEN 'M'::bpchar THEN
		RETURN to_char(date_start, 'Month');
	WHEN 'U'::bpchar THEN
		RETURN 'Unknown';
	WHEN 'C'::bpchar THEN
		RETURN to_char(date_start, 'CC') || 'c';
	WHEN 'CC'::bpchar THEN
		RETURN to_char(date_start, 'CC') || 'c to ' || to_char(date_end, 'CC');
	WHEN 'C-'::bpchar THEN
		RETURN 'From ' || to_char(date_start, 'CC');
	WHEN '-C'::bpchar THEN
		RETURN 'To ' || to_char(date_end, 'CC');
	WHEN 'S'::bpchar THEN
		RETURN date_to_season(date_start);
	WHEN 'P'::bpchar THEN
		RETURN date_to_season(date_start) || ' ' || to_char(date_end, YEAR_FORMAT);
	ELSE
		RETURN NULL::TEXT;
	END CASE;
END
$BODY$;

COMMENT ON FUNCTION vague_date_to_string(date, date, character varying)
    IS 'Formats a vague date to a string.';
