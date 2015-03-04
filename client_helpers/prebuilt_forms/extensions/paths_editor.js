jQuery(document).ready(function ($) {
  "use strict";
  var walkLayer, lineStrings=[];

  /**
   * Takes an array of segments (either features or geometries) and returns a single linestring with everything sorted into
   * linear order
   * @param segments Array of features or geometries.
   */
  function multiLineToSingleLine(segments) {
    var allVertices = [], v;
    $.each(segments, function() {
      if (typeof this.geometry==="undefined") {
        v = this.getVertices();
      } else {
        v = this.geometry.getVertices();
      }
      if (allVertices.length===0 || v[0].equals(allVertices[allVertices.length-1])) {
        // vertices are in correct order, so just join them up
        allVertices = allVertices.concat(v);
      } else if (v[v.length-1].equals(allVertices[allVertices.length-1])) {
        // the segment we are adding to the end of the chain is backwards
        allVertices = allVertices.concat(v.reverse());
      } else if (v[0].equals(allVertices[0])) {
        // the segment to add's first node joins to the start of the chain. So reverse our chain and add to the end
        allVertices = allVertices.reverse().concat(v);
      } else if (v[v.length-1].equals(allVertices[0])) {
        // the segment to add's last node joins to the start of the chain. So reverse our chain and add reversed segment to the end
        allVertices = allVertices.reverse().concat(v.reverse());
      } else {
        // The ends don't join. We are probably dealing with a T junction.
        var nearest, distance, minDistance=false, lineSoFar = new OpenLayers.Geometry.LineString(allVertices);
        $.each(v, function(idx) {
          distance = this.distanceTo(lineSoFar);
          if (!minDistance || minDistance > distance) {
            nearest = idx;
            minDistance = distance;
          }
        });
        //alert ('Found a t junction. Nearest node was ' + nearest + ' out of ' + v.length);
        //alert('This was ' + minDistance + ' away');
        alert('Unable to work out the path you took. Please try again.');
      }
    });
    return new OpenLayers.Geometry.LineString(allVertices);
  }

  /**
   * The geometries might include multilinestrings which have junctions in them. Our calculation code works best on
   * just linestrings, so this creates a list of the geometries broken into linestrings.
   */
  function createLineStringList() {
    var count=0;
    lineStrings=[];
    $.each(indiciaData.reportlayer.features, function() {
      if (this.onScreen()) {
        count++;
        if (this.geometry.CLASS_NAME === 'OpenLayers.Geometry.LineString') {
          lineStrings.push(this.geometry);
        } else if (this.geometry.CLASS_NAME === 'OpenLayers.Geometry.MultiLineString') {
          $.each(this.geometry.components, function(i, c) {
            if (c.CLASS_NAME === 'OpenLayers.Geometry.LineString') {
              lineStrings.push(c);
            }
          });
        }
      }
    });
    return count;
  }

  /**
   * Adds an array of segments to the walk layer to draw where the walk has been defined.
   * @param path array of geometries defining the route.
   */
  function addWalk(path) {
    var ll, ns, ew;
    walkLayer.addFeatures([new OpenLayers.Feature.Vector(path, {}, {strokeColor: "blue", strokeWidth: 6})]);
    $('#imp-geom').val(path.toString());
    // build a lat long string
    ll = path.getCentroid().transform(indiciaData.mapdiv.map.projection, 'epsg:4326').toString()
      .replace('POINT(', '')
      .replace(')', '')
      .split(' ');
    ll[0] = Math.round(ll[0] * 1000) / 1000;
    ll[1] = Math.round(ll[1] * 1000) / 1000;
    ns = ll[1]>0 ? 'N' : 'S';
    ew = ll[0]>0 ? 'E' : 'W';

    $('#imp-sref').val(Math.abs(ll[1]) + ns + ', ' + Math.abs(ll[0]) + ew);
  }

  function ensureClickedOnRiver(clickPointFeature) {
    var found, clickLayer = indiciaData.mapdiv.map.editLayer;
    if (clickLayer.features.length === 3) {
      // 3rd click, so we are retrying. Remove the first attempt.
      clickLayer.removeFeatures([clickPointFeature.layer.features[0], clickPointFeature.layer.features[1]]);
      walkLayer.removeAllFeatures();
    }
    // a click could be on several paths if at an intersection. Find them all.
    $.each(indiciaData.reportlayer.features, function (idx, feature) {
      if (feature.geometry.intersects(clickPointFeature.geometry)) {
        found = true;
      }
    });
    if (!found) {
      alert('The point you have clicked on is not recognised as a river section. Please click on a marked river section ' +
          'to define the start and end of your walk.');
      // undo the last click
      if (clickLayer.length) {
        clickLayer.removeFeatures([clickLayer.features[clickLayer.features.length - 1]]);
      }
      return false;
    }
    return true;
  }

  function searchFor2ndClickPoint(geomToTestFor1stClickPoint, geomToTestFor2ndClickPoint) {
    var found = false, remainingLineStrings = lineStrings.slice(0), possiblePath1, possiblePath2, allPossiblePaths = [], i,
        newlyFoundPaths, split, piecesToAdd, piecesToAddTo;
    i = 0;
    // find the geometries that intersect the starting point. We'll then explode out from there.
    while (i < remainingLineStrings.length) {
      if (remainingLineStrings[i].intersects(geomToTestFor1stClickPoint)) {
        // Found a linestring that intersects the first click. Need to split at the click point and start searching along
        // all the possible paths starting with each split line.
        split = remainingLineStrings[i].splitWith(geomToTestFor1stClickPoint);
        if (split===null) {
          // splitwith returns null if point at end of line. In which case just use the whole line and search from there.
          split = [remainingLineStrings[i]];
        }
        $.each(split, function() {
          // With any luck, the 2nd click is further down the same line
          if (this.intersects(geomToTestFor2ndClickPoint)) {
            found = [this];
          }
          // 1st click on the line, not the 2nd. Collect this geom so we can scan everything that connects to it to look for the 2nd.
          allPossiblePaths.push([this]);
        });
        // no need to go on if we found the 2 click point hits the same line
        if (found) {
          return found;
        }
        // remove the line from the list to consider, so we don't loop or degrade performance
        remainingLineStrings.splice(i, 1);
      } else {
        i++;
      }
    }
    newlyFoundPaths = allPossiblePaths;
    while (remainingLineStrings.length>0 && newlyFoundPaths.length>0) {
      allPossiblePaths = newlyFoundPaths;
      newlyFoundPaths = [];
      $.each(allPossiblePaths, function (j, path) {
        i = 0;
        // Scan through the linestrings we've not connected to our network of search paths yet.
        while (i < remainingLineStrings.length) {
          // does the linestring join to the path we are searching along?
          if (remainingLineStrings[i].intersects(path[path.length-1])) {
            // allow for t junctions - first, the existing path could join half way along the new one
            piecesToAdd = remainingLineStrings[i].splitWith(path[path.length-1]);
            if (piecesToAdd===null) {
              // splitwith returns null if point at end of line. In which case just use the whole line and search from there.
              piecesToAdd = [remainingLineStrings[i]];
            }
            possiblePath1 = path.slice(0);
            // t junctions could also be the new line joins half way along the existing path
            piecesToAddTo = path[path.length-1].splitWith(remainingLineStrings[i]);
            if (piecesToAddTo!==null) {
              // if this type of t-junction is found, we can discard the tail end
              $.each(piecesToAddTo, function() {
                if ((path.length === 1 && this.intersects(geomToTestFor1stClickPoint)) ||
                    (path.length > 1 && this.intersects(path[path.length-2]))) {
                  possiblePath1[path.length-1] = this;
                }
              });
            }
            // explore the routes down the pieces to add (which could be a single route or both branches of a t junction)
            $.each(piecesToAdd, function() {
              // copy the path so far so we can change it
              possiblePath2 = possiblePath1.splice(0);
              possiblePath2.push(this);
              if (this.intersects(geomToTestFor2ndClickPoint)) {
                // got a hit, so hold the path and bomb out of the loops
                found = possiblePath2;
                return false; // from $.each
              }
              // path not a hit on the 2nd click point yet, but could be if we explore further
              newlyFoundPaths.push(possiblePath2);
            });
            // don't rescan this linestring as we don't want loops or slow performance
            remainingLineStrings.splice(i, 1);
          } else {
            i++;
          }
        }
      });
    }
    return found;
  }

  function createPathFromStartToEnd() {
    var clickLayer = indiciaData.mapdiv.map.editLayer,
      clickedPointGeom1 = clickLayer.features[0].geometry,
      clickedPointGeom2 = clickLayer.features[1].geometry,
      p1l = new OpenLayers.Geometry.Point(clickedPointGeom1.x - 0.1, clickedPointGeom1.y),
      p1r = new OpenLayers.Geometry.Point(clickedPointGeom1.x + 0.1, clickedPointGeom1.y),
      p2l = new OpenLayers.Geometry.Point(clickedPointGeom2.x - 0.1, clickedPointGeom2.y),
      p2r = new OpenLayers.Geometry.Point(clickedPointGeom2.x + 0.1, clickedPointGeom2.y),
      geomToTestFor1stClickPoint = new OpenLayers.Geometry.LineString([p1l, p1r]),
      geomToTestFor2ndClickPoint = new OpenLayers.Geometry.LineString([p2l, p2r]),
      path, finalGeom, split;
    path = searchFor2ndClickPoint(geomToTestFor1stClickPoint, geomToTestFor2ndClickPoint);
    if (path) {
      finalGeom = multiLineToSingleLine(path);
      // the last geom added to the path might go further than the clicked point, so we need to lop it at the click point.
      split = finalGeom.splitWith(geomToTestFor2ndClickPoint);
      if (split!==null) {
        $.each(split, function() {
          if (this.intersects(geomToTestFor1stClickPoint)) {
            finalGeom = this;
            return false; // from $.each
          }
        });
      }
      addWalk(finalGeom);
    } else {
      alert("The start and end points you clicked on don't seem to be connected. Please try again.");
    }
  }

  function onPointAdded(evt) {
    $.each(evt.features, function (idx, clickPointFeature) {
      if (clickPointFeature.attributes.type !== "ghost") {
        ensureClickedOnRiver(clickPointFeature);
        // Are there 2 points on the edit layer to mark the start and end?
        if (indiciaData.mapdiv.map.editLayer.features.length === 2) {
          // retrieve all the individual linestrings for all the features on the map
          createLineStringList();
          createPathFromStartToEnd();
        }
      }
    });
  }


  mapInitialisationHooks.push(function (div) {
    // If we have a path template layer loaded on the map, then we want to configure the controls to pick up the start and
    // end of paths.
    if (typeof indiciaData.wantPathEditor!=="undefined") {
      var snap;
      walkLayer = new OpenLayers.Layer.Vector('Your walk', {
        style: {strokeColor: "red", strokeWidth: 5},
        sphericalMercator: true,
        displayInLayerSwitcher: false
      });
      div.map.addLayer(walkLayer);
      snap = new OpenLayers.Control.Snapping({
        layer: div.map.editLayer,
        targets: [{layer: indiciaData.reportlayer, tolerance: 20}]
      });
      div.map.addControl(snap);
      snap.activate();
      div.map.editLayer.events.on({
        featuresadded: onPointAdded
      });
    }
    var f;
    if (typeof indiciaData.showParentSampleGeom!=="undefined") {
      f = new OpenLayers.Feature.Vector(
        OpenLayers.Geometry.fromWKT(indiciaData.showParentSampleGeom),
        {type: "boundary"}
      );
      div.map.editLayer.addFeatures([f]);
      div.map.zoomToExtent(div.map.editLayer.getDataExtent());
    }
    if (typeof indiciaData.showChildSampleGeoms!=="undefined") {
      $.each(indiciaData.showChildSampleGeoms, function() {
        f = new OpenLayers.Feature.Vector(
          OpenLayers.Geometry.fromWKT(this),
          {type: "childsample"}
        );
        div.map.editLayer.addFeatures([f]);
        div.map.zoomToExtent(div.map.editLayer.getDataExtent());
      });
    }
    // event handler for the select_map_control button click
    $('button.select_map_control').click(function() {
      var control = $(this).attr('data-control'),
        // convert control name to the expected display class
        controlDisplayClass = 'olControl' + control.charAt(0).toUpperCase() + control.slice(1),
        minzoomlevel = $(this).attr('data-minzoomlevel') ? $(this).attr('data-minzoomlevel') : 0;
      if (minzoomlevel && div.map.getZoom()<minzoomlevel) {
        alert('Please zoom in a bit further before attempting to define your walk.');
        return;
      }
      $.each(div.map.controls, function() {
        if (this.displayClass.split(' ').indexOf(controlDisplayClass)!==-1) {
          this.activate();
        }
      });
      if (typeof indiciaData[$(this).attr('id')]!=="undefined") {
        $(this).after('<p>' + indiciaData[$(this).attr('id')] + '</p>');
        delete indiciaData[$(this).attr('id')];
      }
    });
  });

});