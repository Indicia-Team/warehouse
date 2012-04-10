UPDATE sample_attributes SET validation_rules='numeric
minimum[-20]
maximum[45]' WHERE caption='Temperature (Celsius)';



UPDATE sample_attributes SET validation_rules='numeric' WHERE caption='Altitude (m)';
