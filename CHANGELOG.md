Version 2.0.0

* Overhaul the warehouse UI with a new Bootstrap 3 based theme and more logical menus.
* Move cient_helpers and media code to use jQuery 3+.
* Support for prioritised load aware background task scheduling via a work queue.
* Removed the following:
  * index_locations_samples table
  * fields named location_id_* from cache_occurrences_functional
  * fields named location_id_* from cache_samples_functional

  Replaced the above with cache_occurrences_functional.location_ids (integer[]) and
  cache_samples_functional.location_ids (integer[]). This means there is no need to
  distinguish between uniquely indexed location types (linked via the locaiton_id_*
  fields) and non-uniquely indexed location types (linked in the index_locations_samples
  table). Removes the need for additional join to index_locations_samples so will improve
  performance in many cases.
* Updated reports which used the location_id_* fields to output a location for a record
  to now update a list of overlapping locations rather than being limited to a single
  location. For example, if a record is added which overlaps 2 country boundaries then
  the report may include both country names in the output field rather than just one.
* Removed the following unused verification reports. Existing verification
  implementations on client websites should be updated to use the verification_5 prebuilt
  form and its reports:
  * reports/library/occurrences/verification_list.xml
  * reports/library/occurrences/verification_list_2.xml
  * reports/library/occurrences/verification_list_3.xml
  * reports/library/occurrences/verification_list_3_mapping.xml
  * reports/library/occurrences/verification_list_3_mapping_using_spatial_index_builder.xml
  * reports/library/occurrences/verification_list_3_using_spatial_index_builder.xml

* Other unused reports removed:
  * reports/library/locations/filterable_occurrence_counts_mappable_2.xml
  * reports/library/locations/filterable_species_counts_mappable_2.xml

* Attribute values for taxa, samples and occurrences are now stored in the relevant
  reporting cache tables in a JSON document (attrs_json field). This means that reports
  can output custom attribute values for a record without additional joins for each
  attribute. To enable this functionality, the report needs a parameter of type taxattrs,
  smpattrs or occattrs (allowing attributes to include to be dynamically declared in a
  parameter). Then provide a parameter useJsonAttributes set to a value of '1' to enable
  the new method of accessing attribute values. This has the potential to improve
  performance significantly for reports which include many different attribute values in
  the output.

* Support for dynamic attributes, i.e custom sample or occurrence attributes which are
  linked to a taxon. They can then be included on a recording form only when entering a
  taxon that is, or is a descendant of, the linked taxon.
