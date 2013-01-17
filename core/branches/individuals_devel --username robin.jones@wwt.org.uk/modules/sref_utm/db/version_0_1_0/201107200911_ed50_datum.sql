update spatial_ref_sys 
set proj4text = '+proj=utm +zone=30 +ellps=intl +units=m +no_defs +towgs84=-87,-98,-121'
where srid = 23030;