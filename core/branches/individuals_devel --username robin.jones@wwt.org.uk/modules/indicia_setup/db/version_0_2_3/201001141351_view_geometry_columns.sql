-- Fixed geometry columns for the views

INSERT INTO public.geometry_columns(f_table_catalog, f_table_schema, f_table_name, f_geometry_column, coord_dimension, srid, type)
SELECT '', current_schema(), 'detail_occurrences', 'geom', 2, 900913, 'GEOMETRY'
WHERE NOT EXISTS(SELECT 1 FROM public.geometry_columns WHERE f_table_name='detail_occurrences' AND f_geometry_column='geom');

INSERT INTO public.geometry_columns(f_table_catalog, f_table_schema, f_table_name, f_geometry_column, coord_dimension, srid, type)
SELECT '', current_schema(), 'detail_locations', 'centroid_geom', 2, 900913, 'GEOMETRY'
WHERE NOT EXISTS(SELECT 1 FROM public.geometry_columns WHERE f_table_name='detail_locations' AND f_geometry_column='centroid_geom');

INSERT INTO public.geometry_columns(f_table_catalog, f_table_schema, f_table_name, f_geometry_column, coord_dimension, srid, type)
SELECT '', current_schema(), 'detail_locations', 'buffer_geom', 2, 900913, 'GEOMETRY'
WHERE NOT EXISTS(SELECT 1 FROM public.geometry_columns WHERE f_table_name='detail_locations' AND f_geometry_column='buffer_geom');

INSERT INTO public.geometry_columns(f_table_catalog, f_table_schema, f_table_name, f_geometry_column, coord_dimension, srid, type)
SELECT '', current_schema(), 'detail_samples', 'geom', 2, 900913, 'GEOMETRY'
WHERE NOT EXISTS(SELECT 1 FROM public.geometry_columns WHERE f_table_name='detail_samples' AND f_geometry_column='geom');
