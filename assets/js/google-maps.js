(function ($) {

  // Main init for ONE .acf-map element
  function initAcfMap($el) {
    var $markers = $el.find('.marker');

    var mapStyle = [
      { featureType: "poi", elementType: "all", stylers: [{ visibility: "off" }] },
      { featureType: "poi.business", elementType: "all", stylers: [{ visibility: "off" }] },
      { featureType: "administrative.land_parcel", elementType: "labels", stylers: [{ visibility: "off" }] },
      { featureType: "road.local", elementType: "labels", stylers: [{ visibility: "on" }] }
    ];

    var mapArgs = {
      zoom: getResponsiveZoom($el),
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      fullscreenControl: false,
      streetViewControl: false,
      mapTypeControl: false,
      zoomControl: true,
      scrollwheel: false,
      styles: mapStyle,
      clickableIcons: false
    };

    var map = new google.maps.Map($el[0], mapArgs);

    map.markers = [];
    $markers.each(function () {
      initMarker($(this), map);
    });

    centerMap(map);
    return map;
  }

  function initMarker($marker, map) {
    var lat = $marker.data('lat');
    var lng = $marker.data('lng');
    var latLng = { lat: parseFloat(lat), lng: parseFloat(lng) };

    var marker = new google.maps.Marker({
      position: latLng,
      map: map,
      // icon: { url: '/path/to/pin.png', scaledSize: new google.maps.Size(32, 32) }
    });

    map.markers.push(marker);

    if ($marker.html()) {
      var infowindow = new google.maps.InfoWindow({ content: $marker.html() });
      google.maps.event.addListener(marker, 'click', function () {
        infowindow.open(map, marker);
      });
    }
  }

  function isMobile() {
    return window.matchMedia('(max-width: 1280px)').matches;
  }

  function getResponsiveZoom($el) {
    var zDesktop = parseInt($el.data('zoom') || 16, 10);
    var zMobile  = parseInt($el.data('zoom-mobile') || (zDesktop - 2), 10);
    return isMobile() ? zMobile : zDesktop;
  }

  function centerMap(map) {
    if (!map.markers.length) return;
    var bounds = new google.maps.LatLngBounds();
    map.markers.forEach(function (marker) {
      bounds.extend(marker.getPosition());
    });
    if (map.markers.length === 1) {
      map.setCenter(bounds.getCenter());
    } else {
      map.fitBounds(bounds);
    }
  }

  //  Run after DOM is ready AND after Google Maps is available
  window.initMap = function () {
    jQuery(function ($) {
      $('.acf-map').each(function () {
        initAcfMap($(this));
      });
    });
  };

})(jQuery);