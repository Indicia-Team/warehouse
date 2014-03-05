INSERT INTO control_types (control, for_data_type, multi_value) VALUES('time_input', 'T', 'f');
UPDATE control_types SET multi_value='f' WHERE control='text_input';
