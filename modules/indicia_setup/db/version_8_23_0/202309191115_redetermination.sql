DROP FUNCTION IF EXISTS f_handle_determination(occ_ids integer[], redet_by_user_id integer, log_determination boolean, reset_classification boolean);

CREATE OR REPLACE FUNCTION f_handle_determination(occ_ids integer[], redet_by_user_id integer, redet_by_person_id integer, log_determination boolean, reset_classification boolean)
  RETURNS boolean AS
$BODY$
BEGIN

  IF (log_determination) THEN
    INSERT INTO determinations(
      taxa_taxon_list_id,
      machine_involvement,
      classification_event_id,
      determination_type,
      occurrence_id,
      created_by_id,
      updated_by_id,
      created_on,
      updated_on,
      person_name
      )
    SELECT
      occ.taxa_taxon_list_id,
      occ.machine_involvement,
      occ.classification_event_id,
      'B',
      occ.id,
      COALESCE(ud.id, occ.updated_by_id),
      COALESCE(ud.id, occ.updated_by_id),
      COALESCE((SELECT max(updated_on) FROM determinations WHERE occurrence_id=occ.id AND deleted=false), occ.created_on),
      now(),
      -- Determiner name can come from several places.
      COALESCE(
        -- best option is person linked by determiner_id
        TRIM(COALESCE(pd.first_name || ' ', '') || pd.surname),
        -- 2nd option is the determiner attribute values
        onf.attr_det_full_name, TRIM(COALESCE(onf.attr_det_first_name || ' ', '') || onf.attr_det_last_name),
        -- last option is the recorder names
        snf.recorders)
    FROM occurrences occ
    LEFT JOIN people pd ON pd.id=occ.determiner_id AND pd.deleted=false
    LEFT JOIN users ud ON ud.person_id=pd.id AND ud.deleted=false
    JOIN cache_occurrences_nonfunctional onf ON onf.id=occ.id
    JOIN cache_samples_nonfunctional snf ON snf.id=occ.sample_id
    WHERE occ.id=ANY(occ_ids);
  END IF;

  -- Person_id -1 is a special case which means don't update attribute values.
  -- Also don't want to update if user ID = 1 (anonymous).
  IF redet_by_person_id NOT IN (-1, 1) THEN

    -- Update pre-existing determiner custom attributes.
    UPDATE occurrence_attribute_values v
    SET text_value=CASE a.system_function
      WHEN 'det_full_name' THEN TRIM(COALESCE(p.first_name || ' ', '') || p.surname)
      WHEN 'det_first_name' THEN p.first_name
      WHEN 'det_last_name' THEN p.surname
    END,
    updated_on=now(), updated_by_id=redet_by_user_id
    FROM occurrence_attributes a, users u
    -- Get person for determiner name, either from person ID (provided in determiner_id), or from current user otherwise.
    JOIN people p ON p.id=COALESCE(redet_by_person_id, u.person_id)
      AND p.deleted=false
    WHERE a.deleted=false
    AND v.deleted=false
    AND v.occurrence_attribute_id=a.id
    AND v.occurrence_id=ANY(occ_ids)
    AND a.system_function in ('det_full_name', 'det_first_name', 'det_last_name')
    AND u.id=redet_by_user_id
    AND u.deleted=false;

    -- Add any missing attribute values, if determiner attributes are linked to the survey.
    INSERT INTO occurrence_attribute_values (
      occurrence_id,
      occurrence_attribute_id,
      text_value,
      created_on,
      created_by_id,
      updated_on,
      updated_by_id)
    SELECT o.id,
      a.id,
      CASE a.system_function
        WHEN 'det_full_name' THEN TRIM(COALESCE(p.first_name || ' ', '') || p.surname)
        WHEN 'det_first_name' THEN p.first_name
        WHEN 'det_last_name' THEN p.surname
      END,
      now(),
      redet_by_user_id,
      now(),
      redet_by_user_id
    FROM users u
    -- Get person for determiner name, either from person ID (provided in determiner_id), or from current user otherwise.
    JOIN people p ON p.id=COALESCE(redet_by_person_id, u.person_id),
    occurrences o
    JOIN samples s ON s.id=o.sample_id AND s.deleted=false
    JOIN occurrence_attributes_websites aw ON aw.restrict_to_survey_id=s.survey_id AND aw.deleted=false
    JOIN occurrence_attributes a ON a.id=aw.occurrence_attribute_id
      AND a.deleted=false
      AND a.system_function in ('det_full_name', 'det_first_name', 'det_last_name')
    -- Exclude existing.
    LEFT JOIN occurrence_attribute_values vexist ON vexist.occurrence_id=o.id AND vexist.occurrence_attribute_id=a.id AND vexist.deleted=false
    WHERE o.id=ANY(occ_ids)
    AND o.deleted=false
    AND u.deleted=false
    AND u.id=redet_by_user_id
    AND vexist.id IS NULL;

    INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
      SELECT 'task_cache_builder_attrs_occurrence', 'occurrence', o.id, 50, 2, now()
      FROM occurrences o
      -- Exclude existing.
      LEFT JOIN work_queue q ON q.task='task_cache_builder_attrs_occurrence' AND q.entity='occurrence' AND q.record_id=o.id
      WHERE o.deleted=false AND o.id=ANY(occ_ids)
      AND q.id IS NULL;
  END IF;

  IF (reset_classification) THEN
    UPDATE occurrences
      SET classification_event_id = null,
      machine_involvement = null
    WHERE id=ANY(occ_ids);
  END IF;

  RETURN true;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;