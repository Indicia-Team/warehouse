update spatial_ref_sys 
set proj4text = '+proj=utm +zone=30 +ellps=intl +units=m +no_defs +towgs84=506,-122,611'
where srid = 29903;