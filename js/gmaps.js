    function initialize() {
        var myOptions = {
          center: new google.maps.LatLng(53.20, -7.0),
          zoom: 7,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map(document.getElementById("map_canvas"),
            myOptions);
      }

