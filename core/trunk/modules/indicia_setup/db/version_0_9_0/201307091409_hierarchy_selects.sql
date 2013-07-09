insert into control_types (control, for_data_type, multi_value)
select 'hierarchical_select', 'L', 'f'
where not exists(select 1 from control_types where control='hierarchical_select' and for_data_type='L')