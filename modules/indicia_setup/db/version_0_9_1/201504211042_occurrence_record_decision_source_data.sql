-- SLOW SCRIPT
-- all decisions to this point have been human
UPDATE occurrences o
	SET record_decision_source='H'
WHERE o.record_status IN ('V','R');