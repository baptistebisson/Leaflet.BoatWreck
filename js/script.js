var first = true;
//Notification instance
var notyf = new Notyf({
    delay: 2000,
});

resize()
window.onresize = resize;

function resize() {
    if ($(window).width() < 1024) {
        $('body').addClass('mobile')
        $('body').removeClass('desktop')
    } else if ($('body').hasClass('mobile')) {
        $('body').removeClass('mobile')
    } else {
        $('body').addClass('desktop')
        if ($('#sidebar').hasClass('close-sidebar')) {
            $('#sidebar').addClass('open-sidebar')
            $('#sidebar').removeClass('close-sidebar')
        }
    }
}

var vessels = JSON.parse(vesselsTmp)

$('.help').on('click', function() {
    var idHelp = $(this).attr('data-help')
    var helpHtml = $('#sidebar').find('div[data-help='+ idHelp +']')
    if (helpHtml.hasClass('show-help')) {
        helpHtml.removeClass('show-help')
        helpHtml.addClass('hide-help')
    } else {
        helpHtml.removeClass('hide-help')
        helpHtml.addClass('show-help')        
    }
})

$('input.form-control').keypress(function(e) {
    if(e.which == 13) {
        submitForm()
    }
});

var pluriel = ''
if (total > 1) {
    pluriel = 's'
}

$(document).ready(function() {
    $('#types').select2({
        dropdownParent: $('#sidebar'),
        placeholder: 'Cargo',
        allowClear: true
    });
    $('#pavillons').select2({
        dropdownParent: $('#sidebar'),
        placeholder: 'France',
        allowClear: true
    });
});


function openSidebar() {
    $('#sidebar').addClass('open-sidebar')
    $('#sidebar').removeClass('close-sidebar')
}
function closeSidebar() {
    $('#sidebar').removeClass('open-sidebar')
    $('#sidebar').addClass('close-sidebar')
}


var map = L.map('map', {
    maxBounds:[ [150, 540], [-150, -540] ],
    boxZoom: true,
    noWrap: true,
    minZoom: 2,
    preferCanvas: true
}).setView([0, 0], 1),
tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>', 
    id: 'openstreetmap.streets'
}).addTo(map);

//Avoid zoom when scrolling the page
map.scrollWheelZoom.disable()
map.on('focus', function() { map.scrollWheelZoom.enable(); });

//Add ocean data to the layer
var OpenSeaMap = L.tileLayer('https://tiles.openseamap.org/seamark/{z}/{x}/{y}.png', {
    attribution: 'Map data: &copy; <a href="http://www.openseamap.org">OpenSeaMap</a> contributors'
}).addTo(map);

//Add the scale on the bottom left of the map
map.addControl(new L.Control.ScaleNautic({
    metric: false,
    imperial: false,
    nautic: true
}));

L.control.coordinates({
    position:"topright",
    useDMS:true,
    labelTemplateLat:"Lat {y}",
    labelTemplateLng:"Long {x}",
    useLatLngOrder:true,
    customLabelFcn: function(latLonObj, opts) {
        return convertDMS(latLonObj.lat, latLonObj.lng); 
    }
}).addTo(map);

var listOfMarkers = new L.FeatureGroup();
var allMarkers = new L.FeatureGroup();
var markers = L.markerClusterGroup({
    polygonOptions: {
        fillColor: '#1b2557',
        color: '#1b2557',
        weight: 0.5,
        opacity: 1,
        fillOpacity: 0.5
    }
});

if (vessels != null) {
    showAllVessels()
}

function showAllVessels() {
    listOfMarkers.clearLayers()
    markers.clearLayers()
    $('.liste-epaves').html()
    var lastPositionMarker = L.geoJSON(vessels, {
        pointToLayer: function (feature, latlng) {
            if (latlng['lat'] !== 0 && latlng['lng'] !== 0) {
                var tooltip = L.tooltip({
                    interactive: true,
                    className: "tooltipVessel"
                }).setContent(feature.properties['data']['0']);
                
                var vesselMarker = L.marker(latlng).bindTooltip(tooltip).on('click', onClick);
                myId = L.stamp(vesselMarker)
                $('.liste-epaves').append("<li class='vessel-name' data-id='"+ myId +"'>"+ feature.properties['data']['0'] +"</li>")
                return vesselMarker;                
            } else {
                //vessel without position. We don't need it on the map but we need it in the list
                $('.liste-epaves').append("<li class='vessel-name no-data' title='Position unknown' data-name='"+ feature.properties['data']['0'] +"' data-error='true' id-vessel='"+ feature.properties['data']['1'] +"'>"+ feature.properties['data']['0'] +"</li>")
            }
        },
    }).addTo(listOfMarkers);
    listOfMarkers.addTo(markers);    
    markers.addTo(map)
    fitMap()
    $('.total').html(vessels.features.length+ ' Wreck'+pluriel+' on ' + total)
}

getAllMarkers()
function getAllMarkers() {
    $.ajax({
        url: "/model.php",
        dataType: 'json',
        type: 'POST',
        data: {
            getAll: true,
        },
        success: function(response) {
            L.geoJSON(JSON.parse(response), {
                pointToLayer: function (feature, latlng) {                    
                    if (latlng['lat'] !== 0 && latlng['lng'] !== 0) {
                        var tooltip = L.tooltip({
                            interactive: true,
                            className: "tooltipVessel"
                        }).setContent(feature.properties['data']['0']);
                        
                        var vesselMarker = L.marker(latlng).bindTooltip(tooltip).on('click', onClick);
                        myId = L.stamp(vesselMarker)
                        return vesselMarker;                
                    }
                },
            }).addTo(allMarkers);
        }
    })
}

function findArea()
{
    var tmpMarkers = new L.FeatureGroup()
    var tmpHtml = ''
    for (var variable in allMarkers._layers) {
        allMarkers._layers[variable].eachLayer(function(layer) {
            if (map.getBounds().contains(layer.getLatLng())) {
                layer.addTo(tmpMarkers)
                myId = layer._leaflet_id
                if (layer._latlng['lat'] !== 0 && layer._latlng['lng'] !== 0) {
                    tmpHtml += "<li class='vessel-name' data-id='"+ myId +"'>"+ layer.feature.properties['data']['0'] +"</li>"
                } else {
                    tmpHtml += "<li class='vessel-name no-data' title='Position unknown' data-name='"+ layer.feature.properties['data']['0'] +"' data-error='true' id-vessel='"+ layer.feature.properties['data']['1'] +"'>"+ layer.feature.properties['data']['0'] +"</li>"
                }
            }
        })
    }
    //If we have something
    if (Object.keys(tmpMarkers._layers).length >= 1) {
        $('.liste-epaves').empty()
        console.log(tmpHtml);
        $('.liste-epaves').append(tmpHtml)
        handleClick()
        var plurielTmp = ''
        if (Object.keys(tmpMarkers._layers).length > 1 ) {
            plurielTmp = 's'
        }
        $('.total').html(Object.keys(tmpMarkers._layers).length+ ' Wreck'+plurielTmp+' on ' + total)
        notyf.confirm(Object.keys(tmpMarkers._layers).length + ' Result'+ plurielTmp);
        listOfMarkers.clearLayers()
        markers.clearLayers()
        tmpMarkers.addTo(listOfMarkers)
        listOfMarkers.addTo(markers)            
    } else {
        notyf.alert('No result');
    }
}

function resetView() {
    //Show again all vessels
    showAllVessels();
    //Cleaning form
    $("#types").val(null).trigger('change');
    $("#pavillons").val(null).trigger('change');
    $(".form input, .form select").each(function(index) {
        $(this).val('')
    })
}

map.on('zoomend', function() {
    var zoom = map.getZoom()
    var btn = $('#find-area')
    if (zoom >= 10) {
        btn.prop('disabled', false)
    } else {
        btn.prop('disabled', true)
    }
})

//Form submits
function submitForm() {
    var valid = false
    //Regarde si au moins un champ en remplit afin de lancer la recherche 
    if ($('#pavillons').select2('data').length !== 0 || $('#types').select2('data').length !== 0) {
        valid = true
    }
    $(".form input, .form select").each(function(index) {
        if ($(this).val().length !== 0) {
            valid = true
        }
    })
    
    //If form valid
    if (valid == true) {
        $.ajax({
            url: "/model.php",
            dataType: 'json',
            type: 'POST',
            data: {
                nom: $('#nom').val(),
                annee: $('#annee').val(),
                types: JSON.stringify($('#types').select2('data'), null, 2),
                pavillons: JSON.stringify($('#pavillons').select2('data'), null, 2),
            },
            success: function(response) {
                if (response !== null) {
                    var plurielTmp = ''
                    if (JSON.parse(response).features.length > 1 ) {
                        plurielTmp = 's'
                    }
                    
                    notyf.confirm(JSON.parse(response).features.length + ' Result'+ plurielTmp);
                    //We have a response
                    //Remove data from list and clear layers
                    $('.liste-epaves').empty()
                    listOfMarkers.clearLayers()
                    markers.clearLayers()
                    var lastPositionMarker = L.geoJSON(JSON.parse(response), {
                        pointToLayer: function (feature, latlng) {
                            var tooltip = L.tooltip({
                                interactive: true,
                                className: "tooltipVessel"
                            }).setContent(feature.properties['data']['0']);
                            
                            var vesselMarker = L.marker(latlng).bindTooltip(tooltip).on('click', onClick);
                            myId = L.stamp(vesselMarker)
                            $('.liste-epaves').append("<li class='vessel-name' data-id='"+ myId +"'>"+ feature.properties['data']['0'] +"</li>")
                            return vesselMarker;
                        },
                    }).addTo(listOfMarkers);
                    listOfMarkers.addTo(markers);
                    fitMap()
                    markers.addTo(map)
                    $('.total').html(JSON.parse(response).features.length+ ' Wreck'+plurielTmp+' on ' + total)
                    
                    //The DOM was updated, we need to add again this function
                    handleClick()
                } else {
                    notyf.alert('No result');
                }
            },
        })
    }
}

handleClick()
function handleClick()
{
    //Handle click on list of vessel
    $('.vessel-name:not(.no-data)').click(function() {
        var vesselClicked = $(this).attr("data-id");
        listOfMarkers.eachLayer(function(layer){
            //Zoom on the vessel clicked
            map.setView(layer.getLayer(vesselClicked).getLatLng(), 13)
            //Open the popup
            onClick(layer.getLayer(vesselClicked))
        })
        //If no data container open, close it
        var container = $('.no-data-container')
        if (container.hasClass('show')) {
            container.removeClass('show')
            container.addClass('hide')
        }
    })
    $('.no-data').click(function() {
        var vesselId = $(this).attr("id-vessel");
        var vesselName = $(this).attr("data-name");
        $.ajax({
            url: "/model.php",
            dataType: 'json',
            type: 'POST',
            data: {
                id: vesselId,
            },
            success: function(response) {
                if (response !== null) {
                    var container = $('.no-data-container')
                    container.html('<div class="header">'+ vesselName +'</div>'+
                    '<i class="material-icons close-container">close</i><div class="content"><span class="error">Incorrect position</span><span>Type: '+ response[0][0] +'</span><span>Year: '+ response[0][1] +'</span><a href="details.php?id='+ vesselId +'"><button type="button" class="btn btn-outline-primary">Sheet</button></a></div>')
                    container.removeClass('hide')
                    container.addClass('show')
                    
                    //Handle close icon
                    $('.close-container').on('click', function() {
                        var container = $('.no-data-container')
                        if (container.hasClass('show')) {
                            container.removeClass('show')
                            container.addClass('hide')
                        }
                    })
                }
            },
        })
    })
    
}

var oldClickData = null;

function onClick(e) {
    var valid = false
    //If data come from clicked marker
    if (typeof(e.target) !== 'undefined') {
        //Avoid user to click two times on the same marker and execute 
        //again the function
        if (valid !== e.target._leaflet_id) {
            valid = true
            var vesselId = e.target.feature.properties.data[1]
            var vesselName = e.target.feature.properties.data[0]            
        }
    } else {
        if (valid !== e._leaflet_id) {
            valid = true
            //Data come from clicked vessel name from list
            var vesselId = e.feature.properties.data[1]
            var vesselName = e.feature.properties.data[0]
        }
    }
    if (valid !== false) {        
        $.ajax({
            url: "/model.php",
            dataType: 'json',
            type: 'POST',
            data: {
                id: vesselId,
            },
            success: function(response) {
                if (response !== null) {
                    var data = response
                    console.log(data);
                    //We have a response
                    //creation of the popup
                    var popup = L.popup({className: 'big-popup'})
                    .setContent('<div class="header">'+ vesselName +'</div>'+
                    '<div class="content"><span>Année: '+ data[0] +'</span><span>Lat: '+ data[1] +'</span><span>Long: '+ data[2] +'</span><a href="/bdd/fichetech.php?id='+ vesselId +'"><button type="button" class="btn btn-outline-primary">Sheet</button></a></div>');
                    if (typeof(e.target) !== 'undefined') {
                        e.target.bindPopup(popup)
                        e.target.openPopup()
                        //Log clicked vessel
                        oldClickData = e.target._leaflet_id
                    } else {
                        e.bindPopup(popup)
                        e.openPopup()
                        oldClickData = e._leaflet_id
                    }
                }
            },
        })
    }
}

//Handle popup inside cluster
L.Marker.prototype.openPopupBackup = L.Marker.prototype.openPopup;
L.Marker.prototype.openPopup = function() {
    if (!this._map && this.__parent) {
        this.__parent._group.on('spiderfied', this.doneSpiderfy, this);
        this.__parent.spiderfy();
    }
    else {
        this.openPopupBackup();
    }
}
L.Marker.prototype.doneSpiderfy = function() {
    this.__parent._group.off('spiderfied', this.doneSpiderfy, this);
    this.openPopupBackup();
}

//Convert to DMS by replacing minus by letter
function convertDMS(lat, lng) {
    var latitude = toDegreesMinutesAndSeconds(lat, 'lat');
    var latitudeCardinal = Math.sign(lat) >= 0 ? "N" : "S";
    
    var longitude = toDegreesMinutesAndSeconds(lng, 'lng');
    var longitudeCardinal = Math.sign(lng, 'lng') >= 0 ? "E" : "W";
    
    return "Lat : " + latitude + " " + latitudeCardinal + "\n" + "Long : " + longitude + " " + longitudeCardinal;
}

//Convert decimals to DMS for map coordinates
function toDegreesMinutesAndSeconds(coordinate, latOrLong) {
    var absolute = Math.abs(coordinate);
    var degrees = Math.floor(absolute);
    var minutesNotTruncated = (absolute - degrees) * 60;
    var minutes = Math.floor(minutesNotTruncated);
    var seconds = Math.floor((minutesNotTruncated - minutes) * 60);
    
    if (latOrLong == "lng") {
        var myDegree = String(degrees).padStart(3, '0');
    } else {
        var myDegree = String(degrees).padStart(2, '0');
    }
    
    return myDegree + "&deg; " + minTwoDigits(minutes) + "&apos; " + minTwoDigits(seconds) + "&quot; ";
}

//Convert to DMS by replacing minus by letter
function convertDMS(lat, lng) {
    var latitude = toDegreesMinutesAndSeconds(lat, 'lat');
    var latitudeCardinal = Math.sign(lat) >= 0 ? "N" : "S";
    
    var longitude = toDegreesMinutesAndSeconds(lng, 'lng');
    var longitudeCardinal = Math.sign(lng, 'lng') >= 0 ? "E" : "W";
    
    return "Lat : " + latitude + " " + latitudeCardinal + "\n" + "Long : " + longitude + " " + longitudeCardinal;
}

//Avoid single digit on coordinates
function minTwoDigits(n) {
    return (n < 10 ? '0' : '') + n;
}

//Handle zoom cluster when added
function fitMap()
{
    map.fitBounds(markers.getBounds());
}