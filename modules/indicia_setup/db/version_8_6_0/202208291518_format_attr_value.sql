CREATE OR REPLACE FUNCTION get_formatted_attr_text_value(
  caption text,
  value text,
  output_formatting bool
)
RETURNS text
LANGUAGE 'plpgsql'
IMMUTABLE
AS
$BODY$
--DECLARE
BEGIN
  RETURN
    CASE
      WHEN substring(caption from (char_length(caption)-5) for 4) = 'link' AND substring(value from 0 for 4) = 'http' THEN
        '<a href="' || value || '">' || value || '</a>'
      -- Colour value with a secondary colour.
      WHEN value LIKE '#%;%' THEN '<span style="width: 30px; height: 15px; display: inline-block; background-color: ' || split_part(value, ';', 1) || '"> </span>'
        || '<span style="width: 30px; height: 15px; display: inline-block; background-color: ' || split_part(value, ';', 2) || '"> </span>'
      -- Single colour value.
      WHEN value LIKE '#%' THEN '<span style="width: 30px; height: 15px; display: inline-block; background-color: ' || value || '"> </span>'
      ELSE
        CASE output_formatting
          -- Newlines
          WHEN 't' THEN
            replace(
              -- Embedded links in text block formatted if param set.
              regexp_replace(value, '(http[^\s]*)', '<a href="\1">\1</a>'),
              CHR(13),
              '<br/>'
            )
          ELSE
            value
        END
    END;
END
$BODY$;