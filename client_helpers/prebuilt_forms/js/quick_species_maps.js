$(document).ready(function () {
  
  $('table.report-grid tbody').click(function (evt) {
    var tvk=$(evt.target).parents('tr')[0].id.substr(3);
    filter=indiciaData.indiciaSpeciesLayer.cqlFilter.replace('{filterValue}', tvk);
    layer = new OpenLayers.Layer.WMS(indiciaData.indiciaSpeciesLayer.title, indiciaData.indiciaSpeciesLayer.wmsUrl, 
        {layers: indiciaData.indiciaSpeciesLayer.featureType, transparent: true, CQL_FILTER: filter, STYLES: indiciaData.indiciaSpeciesLayer.sld},
        {isBaseLayer: false, sphericalMercator: true, singleTile: true, opacity: 0.5});
    indiciaData.mapdiv.map.addLayer(layer);
  });
});