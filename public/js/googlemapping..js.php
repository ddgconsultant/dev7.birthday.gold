<?PHP


$polyline = $app->googlemap($companies);
$coords = $app->googlemap_decodePolyline($polyline);
// Generate map with route
?>

<div id="map"></div>

<script>
var map = new google.maps.Map(document.getElementById('map'), {
  zoom: 7
});

var route = new google.maps.Polyline({
  path: <?php echo json_encode($coords); ?>,
  map: map
});

map.fitBounds(route.getBounds());
</script>

<?php




/*
// Map Function

function basicmap() {
    // Basic options for a simple Google Map
    // For more options see: https://developers.google.com/maps/documentation/javascript/reference#MapOptions
    var mapOptions = {
      // How zoomed in you want the map to start at (always required)
      zoom: 11,
      scrollwheel: false,
      // The latitude and longitude to center the map (always required)
      // center: new google.maps.LatLng(40.6700, -73.9400), // New York
       center: new google.maps.LatLng(32.7767, -96.7970) // Dallas, TX
      // This is where you would paste any style found on Snazzy Maps.
      styles: [
      {
          "featureType": "all",
          "elementType": "all",
          "stylers": [
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "all",
          "elementType": "geometry",
          "stylers": [
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "all",
          "elementType": "labels.text.fill",
          "stylers": [
              {
                  "saturation": 36
              },
              {
                  "color": "#000000"
              },
              {
                  "lightness": 40
              }
          ]
      },
      {
          "featureType": "all",
          "elementType": "labels.text.stroke",
          "stylers": [
              {
                  "visibility": "on"
              },
              {
                  "color": "#000000"
              },
              {
                  "lightness": 16
              }
          ]
      },
      {
          "featureType": "all",
          "elementType": "labels.icon",
          "stylers": [
              {
                  "visibility": "off"
              }
          ]
      },
      {
          "featureType": "administrative",
          "elementType": "geometry.stroke",
          "stylers": [
              {
                  "color": "#000000"
              },
              {
                  "lightness": 17
              },
              {
                  "weight": 1.2
              }
          ]
      },
      {
          "featureType": "administrative.country",
          "elementType": "geometry",
          "stylers": [
              {
                  "color": "#868686"
              },
              {
                  "visibility": "off"
              }
          ]
      },
      {
          "featureType": "administrative.country",
          "elementType": "geometry.stroke",
          "stylers": [
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "administrative.country",
          "elementType": "labels.text.fill",
          "stylers": [
              {
                  "color": "#d2d2d2"
              }
          ]
      },
      {
          "featureType": "administrative.province",
          "elementType": "geometry",
          "stylers": [
              {
                  "color": "#676767"
              },
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "administrative.locality",
          "elementType": "labels.text.fill",
          "stylers": [
              {
                  "color": "#848484"
              }
          ]
      },
      {
          "featureType": "landscape",
          "elementType": "geometry",
          "stylers": [
              {
                  "color": "#000000"
              },
              {
                  "lightness": 20
              }
          ]
      },
      {
          "featureType": "landscape",
          "elementType": "geometry.fill",
          "stylers": [
              {
                  "color": "#212121"
              },
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "landscape.man_made",
          "elementType": "geometry.fill",
          "stylers": [
              {
                  "visibility": "on"
              },
              {
                  "color": "#212121"
              }
          ]
      },
      {
          "featureType": "landscape.natural",
          "elementType": "geometry.fill",
          "stylers": [
              {
                  "visibility": "on"
              },
              {
                  "color": "#212121"
              }
          ]
      },
      {
          "featureType": "landscape.natural.landcover",
          "elementType": "geometry.fill",
          "stylers": [
              {
                  "color": "#212121"
              },
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "landscape.natural.terrain",
          "elementType": "geometry.fill",
          "stylers": [
              {
                  "color": "#212121"
              },
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "poi",
          "elementType": "geometry",
          "stylers": [
              {
                  "lightness": 21
              },
              {
                  "color": "#212121"
              }
          ]
      },
      {
          "featureType": "poi.park",
          "elementType": "geometry",
          "stylers": [
              {
                  "color": "#181818"
              }
          ]
      },
      {
          "featureType": "road",
          "elementType": "geometry",
          "stylers": [
              {
                  "visibility": "simplified"
              }
          ]
      },
      {
          "featureType": "road.highway",
          "elementType": "geometry",
          "stylers": [
              {
                  "visibility": "simplified"
              },
              {
                  "color": "#3c3c3c"
              }
          ]
      },
      {
          "featureType": "road.highway",
          "elementType": "geometry.stroke",
          "stylers": [
              {
                  "lightness": 29
              },
              {
                  "weight": 0.2
              }
          ]
      },
      {
          "featureType": "road.highway.controlled_access",
          "elementType": "geometry",
          "stylers": [
              {
                  "color": "#4e4e4e"
              },
              {
                  "visibility": "simplified"
              },
              {
                  "lightness": "-20"
              }
          ]
      },
      {
          "featureType": "road.arterial",
          "elementType": "geometry",
          "stylers": [
              {
                  "visibility": "simplified"
              },
              {
                  "color": "#373737"
              }
          ]
      },
      {
          "featureType": "road.arterial",
          "elementType": "geometry.fill",
          "stylers": [
              {
                  "visibility": "on"
              }
          ]
      },
      {
          "featureType": "road.local",
          "elementType": "geometry",
          "stylers": [
              {
                  "lightness": 16
              },
              {
                  "color": "#313131"
              },
              {
                  "visibility": "simplified"
              }
          ]
      },
      {
          "featureType": "transit",
          "elementType": "geometry",
          "stylers": [
              {
                  "color": "#212121"
              },
              {
                  "lightness": 19
              },
              {
                  "visibility": "off"
              }
          ]
      },
      {
          "featureType": "water",
          "elementType": "geometry",
          "stylers": [
              {
                  "color": "#000000"
              },
              {
                  "visibility": "on"
              }
          ]
      }
  ]
    };
    // Get the HTML DOM element that will contain your map
    // We are using a div with id="map" seen below in the <body>
    var mapElement = document.getElementById('');

    // Create the Google Map using our element and options defined above
    var map = new google.maps.Map(mapElement, mapOptions);

    // Let's also add a marker while we're at it
    var marker = new google.maps.Marker({
      position: new google.maps.LatLng(40.6700, -73.9400),
      map: map,
      title: 'Cryptox'
    });
  }
  if ($('#').length != 0) {
    google.maps.event.addDomListener(window, 'load', basicmap);
  }