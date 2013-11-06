CREATE OR REPLACE function f_add_ct (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 

INSERT INTO control_types (control, for_data_type, multi_value)
SELECT distinct 'complex_attr_grid2', 'T', true FROM control_types WHERE NOT EXISTS(SELECT id FROM control_types WHERE control='complex_attr_grid2');

END
$func$;

SELECT f_add_ct();

DROP FUNCTION f_add_ct();