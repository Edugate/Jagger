////////////////////////////////////
/////// plugins ///////////////////

/*
 * Copyright (c) 2008 Greg Weber greg at gregweber.info
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * documentation at http://gregweber.info/projects/uitablefilter
 *
 * allows table rows to be filtered (made invisible)
 * <code>
 * t = $('table')
 * $.uiTableFilter( t, phrase )
 * </code>
 * arguments:
 *   jQuery object containing table rows
 *   phrase to search for
 *   optional arguments:
 *     column to limit search too (the column title in the table header)
 *     ifHidden - callback to execute if one or more elements was hidden
 */
jQuery.uiTableFilter = function (jq, phrase, column, ifHidden) {
    var new_hidden = false;
    if (this.last_phrase === phrase) return false;

    var phrase_length = phrase.length;
    var words = phrase.toLowerCase().split(" ");

    // these function pointers may change
    var matches = function (elem) {
        elem.show()
    }
    var noMatch = function (elem) {
        elem.hide();
        new_hidden = true
    }
    var getText = function (elem) {
        return elem.text()
    }

    if (column) {
        var index = null;
        jq.find("thead > tr:last > th").each(function (i) {
            if ($(this).text() == column) {
                index = i;
                return false;
            }
        });
        if (index == null) throw("given column: " + column + " not found")

        getText = function (elem) {
            return jQuery(elem.find(
                ("td:eq(" + index + ")"))).text()
        }
    }

    // if added one letter to last time,
    // just check newest word and only need to hide
    if ((words.size > 1) && (phrase.substr(0, phrase_length - 1) ===
        this.last_phrase)) {

        if (phrase[-1] === " ") {
            this.last_phrase = phrase;
            return false;
        }

        var words = words[-1]; // just search for the newest word

        // only hide visible rows
        matches = function (elem) {
            ;
        }
        var elems = jq.find("tbody > tr:visible")
    }
    else {
        new_hidden = true;
        var elems = jq.find("tbody > tr")
    }

    elems.each(function () {
        var elem = jQuery(this);
        jQuery.uiTableFilter.has_words(getText(elem), words, false) ?
            matches(elem) : noMatch(elem);
    });

    last_phrase = phrase;
    if (ifHidden && new_hidden) ifHidden();
    return jq;
};

// caching for speedup
jQuery.uiTableFilter.last_phrase = ""

// not jQuery dependent
// "" [""] -> Boolean
// "" [""] Boolean -> Boolean
jQuery.uiTableFilter.has_words = function (str, words, caseSensitive) {
    var text = caseSensitive ? str : str.toLowerCase();
    for (var i = 0; i < words.length; i++) {
        if (text.indexOf(words[i]) === -1) return false;
    }
    return true;
}


/// third party plugin //





////////////////
/// JAGGER /////
////////////////
var map;
var mapSearchInput;
function mapInitialize() {

    window.console.log('map init');
    var markers = [];
    var mapOptions = {
        zoom: 4,
        center: new google.maps.LatLng(50.019036, 13.007813),
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: true,
        mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DEFAULT}
    };
    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
    // var dymek = new google.maps.InfoWindow();
    google.maps.event.addListener(map, 'click', function (event) {
        //  dymek.setContent('Location :<br />' + event.latLng);
        $('#latlng').val(event.latLng.lat().toFixed(6) + ',' + event.latLng.lng().toFixed(6));
        //  dymek.setPosition(event.latLng);
        //  dymek.open(map);
    });

    mapSearchInput = (document.getElementById('map-search'));
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(mapSearchInput);
    var searchBox = new google.maps.places.SearchBox((mapSearchInput));
    google.maps.event.addListener(searchBox, 'places_changed', function () {

        var places = searchBox.getPlaces();
        if (places.length === 0) {
            return;
        }
        for (var i = 0, marker; marker = markers[i]; i++) {
            marker.setMap(null);
        }
        markers = [];
        var bounds = new google.maps.LatLngBounds();
        for (var i = 0, place; place = places[i]; i++) {
            var image = {
                url: place.icon,
                size: new google.maps.Size(71, 71),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17, 34),
                scaledSize: new google.maps.Size(25, 25)
            };

            // Create a marker for each place.
            var marker = new google.maps.Marker({
                map: map,
                icon: image,
                title: place.name,
                position: place.geometry.location
            });

            markers.push(marker);
            bounds.extend(place.geometry.location);
        }

        map.fitBounds(bounds);
        var listener = google.maps.event.addListener(map, "idle", function () {
            if (map.getZoom() > 16) map.setZoom(16);
            google.maps.event.removeListener(listener);
        });

    });
    google.maps.event.addListener(map, 'bounds_changed', function () {
        var bounds = map.getBounds();
        searchBox.setBounds(bounds);
    });

}


jQuery.fn.toggleOption = function (show) {

    jQuery(this).toggle(show);
    if (show) {
        while (jQuery(this).parent('span.toggleOption').length) {
            jQuery(this).unwrap();
        }
    } else {
        jQuery(this).wrap('<span class="toggleOption" style="display: none;" />');
    }
};
var createRowWithLangRm = function (langCode, langString, inputName, rmbtn) {
    console.log('createRowWithLangRm fired');
    return $('<div class=\"large-12 small-12 columns\"><div class=\"small-3 columns\"><label for=\"' + inputName + '\" class=\"right inline\">' + langString + '</label></div><div class=\"small-6 large-7 columns\"><input id=\"' + inputName + '\" name=\"' + inputName + '\" type=\"text\" class=\"validurl\"/></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm button inline tiny left alert\" name=\"langrm\" value=\"' + langCode + '\">' + rmbtn + '</button></div></div>');
};

var createRowTaskParams = function (label1, label2) {
    var rname = 'z';
    var possible = "0123456789";
    for (var i = 0; i < 5; i++) {
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    ;
    return  $('<div class=\"row\"><div class=\"small-6 column\"><label>' + label1 + '<input name=\"params[' + rname + '][name]\" type=\"text\" value=\"\"/></label></div>' +
    '<div class=\"small-6 column\"><label>' + label2 + '<input name=\"params[' + rname + '][value]\" type="text"  value=\"\"/></label></div></div>');
};


var BINIT = {
    initFvalidators: function () {

        console.log('BININT');


        $("ul.validatorbuttons button").on('click', function (e) {
            var link = $(this).attr("value");
            $.ajax({
                type: "GET",
                url: link,
                timeout: 2500,
                cache: true,
                success: function (json) {
                    $('#spinner').hide();
                    var data = $.parseJSON(json);
                    if (data) {
                        var vfedid = data.fedid;
                        var fvalidid = data.id;
                        var fvalidname = data.name;
                        var fvaliddesc = data.desc;
                        $('#fvform input[name="fedid"]').val(vfedid);
                        $('#fvform input[name="fvid"]').val(fvalidid);
                        $("div#fvalidesc").replaceWith('<div id="fvalidesc"><b>' + fvalidname + '</b><p>' + fvaliddesc + '</p></div>');
                        $('#fvform').show();

                    }

                },
                beforeSend: function () {
                    $("#fvresult").hide();
                    $('#spinner').show();

                },
                error: function () {
                    $('#spinner').hide();
                    $('#fvform').hide();
                    $('#fvresult').hide();
                }


            });
        });

    }
};

var GINIT = {
    initialize: function () {
        "use strict";


        var pJagger = $(".pjagger");
        if (pJagger.length > 0) {
            var pElement;
            var srclink;
            pJagger.each(function () {
                pElement = $(this);
                if (pElement.hasClass('piegraph') && pElement.hasClass('fedgraph')) {
                    srclink = pElement.attr('data-jagger-link');
                    var entgroups = ['idp', 'sp', 'both'];
                    var entgroupkey;
                    var countGroups = [];
                    countGroups['idp'] = 0;
                    countGroups['sp'] = 0;
                    countGroups['both'] = 0;
                    $.ajax({
                        url: srclink,
                        type: 'GET',
                        cache: true,
                        dataType: 'json',
                        success: function (data) {
                            if (data) {
                                var data2 = [
                                    {
                                        value: data.idp,
                                        color: "#F7464A",
                                        highlight: "#FF5A5E",
                                        label: data.definitions.idp
                                    },
                                    {
                                        value: data.sp,
                                        color: "#46BFBD",
                                        highlight: "#5AD3D1",
                                        label: data.definitions.sp
                                    },
                                    {
                                        value: data.both,
                                        color: "#FDB45C",
                                        highlight: "#FFC870",
                                        label: data.definitions.both
                                    }
                                ];
                                var ctx = pElement.find('canvas').get(0).getContext("2d");
                                ;
                                if (ctx) {
                                    var myPieChart = new Chart(ctx).Pie(data2, {
                                        responsive: true,
                                        legendTemplate: "<div class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><div><span style=\"background-color:<%=segments[i].fillColor%>\">&nbsp;&nbsp;&nbsp;</span> <%if(segments[i].label){%><%=segments[i].label%><%}%></div><%}%></div>"
                                    });
                                    var legend = myPieChart.generateLegend();
                                    pElement.find('div.plegend').first().html(legend);
                                }
                            }
                        }
                    });
                }
            });
        }
        var membership2 = $("#membership2").first();
        if (membership2 !== undefined && membership2.length > 0) {
            var link = $(membership2).attr('data-jagger-link');
            var entgroups = ['idp', 'sp', 'both'];
            var entgroupkey;
            var countGroups = [];
            countGroups['idp'] = 0;
            countGroups['sp'] = 0;
            countGroups['both'] = 0;
            $.ajax({
                url: link,
                type: 'GET',
                cache: true,
                dataType: 'json',
                success: function (data) {
                    if (data) {

                        var preurl = data.definitions.preurl;
                        var out = [], o = -1;
                        var nr, oddeven;
                        out[++o] = '<table><tbody>';
                        for (var i = 0, total = entgroups.length; i < total; i++) {
                            entgroupkey = entgroups[i];

                            if (data[entgroupkey]) {
                                out[++o] = '<tr><td colspan="2" class="highlight">' + data.definitions[entgroupkey] + '</td></tr>';
                                out[++o] = '<tr><td colspan="2"><div class="zebramembers">';
                                nr = 0;
                                countGroups[entgroupkey] = data[entgroupkey].length;
                                $.each(data[entgroupkey], function (i, v) {
                                    ++nr;

                                    if (nr % 2) {
                                        out[++o] = '<div class="small-12 column odd">';
                                    }
                                    else {
                                        out[++o] = '<div class="small-12 column even">';
                                    }

                                    out[++o] = '<div class="large-5 column">' + nr + '. <a href="' + preurl + v.pid + '">' + v.pname + '</a></div>';
                                    out[++o] = '<div class="large-5 column">' + v.entityid + '</div>';
                                    out[++o] = '<div class="large-2 column"></div>';

                                    out[++o] = '</div>';

                                });
                                out[++o] = '</div></td></tr>';


                            }

                        }
                        out[++o] = '</tbody></table>';
                        if (!membership2.hasClass('fake')) {
                            $(membership2).html(out.join(''));
                        }

                        var data2 = [
                            {
                                value: countGroups.idp,
                                color: "#F7464A",
                                highlight: "#FF5A5E",
                                label: data.definitions.idp
                            },
                            {
                                value: countGroups.sp,
                                color: "#46BFBD",
                                highlight: "#5AD3D1",
                                label: data.definitions.sp
                            },
                            {
                                value: countGroups.both,
                                color: "#FDB45C",
                                highlight: "#FFC870",
                                label: data.definitions.both
                            }
                        ];


                        var ctx = document.getElementById("fedpiechart").getContext("2d");
                        if (ctx && (countGroups.idp > 0 || countGroups.sp > 0 || countGroups.both > 0 )) {
                            var myPieChart = new Chart(ctx).Pie(data2, {
                                responsive: true,
                                legendTemplate: "<div class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><div><span style=\"background-color:<%=segments[i].fillColor%>\">&nbsp;&nbsp;&nbsp;</span> <%if(segments[i].label){%><%=segments[i].label%><%}%></div><%}%></div>"
                            });
                            var legend = myPieChart.generateLegend();
                            $("#fedpiechartlegend").html(legend);
                        }


                    }
                }
            });
        }
        $('div[data-jagger-getmoreajax]').each(function (e) {
            var link = $(this).attr('data-jagger-getmoreajax');
            var targetresponseid = $(this).attr('data-jagger-response-msg');
            var refreshbtn = $(this).attr('data-jagger-refreshurl');
            var refreshbutton = '';
            if (targetresponseid === undefined || targetresponseid === null) {
                console.log('attribute data-jagger-response-msg not found in element with data-jagger-getmoreajax="' + link + '" - exiting');
                return false;
            }
            else {
                console.log('attribute data-jagger-response-msg="' + targetresponseid + '" in element data-jagger-getmoreajax="' + link + '"');
            }
            var targetelement = $("div#" + targetresponseid);
            if (targetelement === undefined || targetelement === null || $(targetelement).length === 0) {
                console.log('div with id="' + targetresponseid + '" not found');

                return false;
            }
            else {
                console.log('div with id="' + targetresponseid + '" found');

            }

            $.ajax({
                dataType: "json",
                type: "GET",
                url: link,
                cache: true,
                success: function (data) {
                    if (!data) {
                        console.log('no json got from ' + link);
                        return false;
                    }
                    var countresult = data.length;
                    if (countresult < 1) {
                        return false;
                    }
                    var result = $('<div/>');

                    var div_data;
                    $.each(data, function (i, v) {
                        div_data = '<div>' + v.msg + '</div>';
                        result.append(div_data);
                    });
                    if (refreshbtn !== null) {
                        refreshbutton = '<a class="refreshurl right" href="#"  data-jagger-getmoreajaxonclick= "' + refreshbtn + '" data-jagger-response-msg="prdetails"><i class="fi-refresh" ></i></a>';
                    }
                    targetelement.empty().append(refreshbutton).append(result).show();


                }
            });

        });


        $("#taskformaddparam").on('click', function(e){
            var currentRow = $(this).closest('div.row');
            var row = createRowTaskParams('','');
            row.insertBefore(currentRow);
            return false;

        });
        $("#password").on('keypress', function (e) {
            var kc = e.keyCode ? e.keyCode : e.which;
            var sk = e.shiftKey ? e.shiftKey : (kc === 16);
            if (((kc >= 65 && kc <= 90) && !sk) || ((kc >= 97 && kc <= 122) && sk)) {
                $("#capswarn").show();
            }
            else {
                $("#capswarn").hide();
            }
        });

        $("table.sortable").tablesorter();

        var baseurl = $("[name='baseurl']").val();
        if (baseurl === undefined) {
            baseurl = '';
        }


        $("#loginbtn").on('click', function (event) {
            event.preventDefault();
            var url = $(this).attr('href');
            var loginform = $("#loginform");
            var submitbutton = $(loginform).find(":submit").first();
            var usernamerow = $(loginform).find("div.usernamerow").first();
            var passwordrow = $(loginform).find("div.passwordrow").first();
            var secondfactorrow = $(loginform).find("div.secondfactorrow").first();
            var loginresponse = $("#loginresponse");
            $.ajax({
                type: "GET",
                url: url,
                dataType: "json",
                cache: false,
                success: function (data) {
                    if (data.logged !== 1) {
                        if (data.partiallogged === 0) {
                            submitbutton.prop('disabled', false);
                            usernamerow.show();
                            passwordrow.show();
                            loginresponse.hide();
                            loginform.foundation('reveal', 'open');
                            return false;
                        }
                        if (data.twofactor === 1 && data.secondfactor !== null) {
                            submitbutton.prop('disabled', true);
                            usernamerow.show();
                            $("#password").val(null);
                            passwordrow.show();
                            loginresponse.hide();
                            if (data.html) {
                                secondfactorrow.html(data.html).show();
                            }
                            loginform.foundation('reveal', 'open');
                            return false;
                        }
                    }
                    else {
                        var baseurl = $("[name='baseurl']").val();
                        if (baseurl !== undefined) {
                            window.location.href = baseurl;
                        }
                    }
                    usernamerow.show();
                    passwordrow.show();
                    loginresponse.hide();
                    loginform.foundation('reveal', 'open');
                }
            });


            return false;
        });

        $('button.modal-close').on('click', function (event) {
            $(this).foundation('reveal', 'close');
        });

        $('#providerlogtab').on('toggled', function (event, tab) {
            var oko = tab.find("[data-reveal-ajax-tab]").first();
            var link = oko.attr("data-reveal-ajax-tab");
            if (link !== undefined) {
                $.ajax({
                    cache: true,
                    type: 'GET',
                    url: link,
                    success: function (data) {
                        $('#providerlogtab').empty().append(data);
                        $(document).foundation('accordion', 'reflow');
                    }
                });
            }
        });

        $("button.cleartarget").on('click', function (e) {

            e.preventDefault();
            var targetname1 = $(this).attr('data-jagger-textarea');
            var targetname2 = $(this).attr('data-jagger-input');
            if (targetname1 !== undefined) {
                $("textarea[name='" + targetname1 + "']").val('');
            }
            if (targetname2 !== undefined) {
                $("input[name='" + targetname2 + "']").val('');
            }
            return false;
        });

        $("button.postajax").on('click', function (e) {
            e.preventDefault();
            var form = $(this).closest("form");
            var targetresponseid = $(this).attr('data-jagger-response-msg');
            var targetelement = $("div#" + targetresponseid);
            var url = form.attr('action');

            $.ajax({
                    type: "POST",
                    url: url,
                    data: form.serializeArray(),
                    beforeSend: function () {
                        targetelement.empty();
                    },
                    success: function (data) {
                        if (data) {
                            targetelement.append(data);
                        }
                        else {
                            targetelement.append('no result');
                        }
                    },
                    error: function (xhr, status, error) {
                        var alertmsg = '<div>' + error + '</div>';

                        targetelement.append(alertmsg);
                        return false;
                    }

                }
            );

            return false;
        });

        $("#confirmremover").on('click', 'div.yes', function (e) {
            e.preventDefault();
            var form = $(this).closest("form");
            var actionUrl = form.attr('action');
            var regid = form.attr('data-jagger-regpolicy');
            var ecid = form.attr('data-jagger-ec');
            $.ajax({
                type: "POST",
                url: actionUrl,
                data: form.serializeArray(),
                success: function (data) {
                    if (regid !== undefined && regid !== null) {
                        $('a[data-jagger-regpolicy="' + regid + '"]').closest('tr').hide();
                    }
                    else {
                        $('a[data-jagger-ec="' + ecid + '"]').closest('tr').hide();
                    }
                    $("#confirmremover").foundation('reveal', 'close');
                },
                error: function (xhr, status, error) {
                    var alertmsg = '' + error + '';
                    window.alert(alertmsg);
                    return false;
                }
            });

        });
        $("a.withconfirm").on('click', function (e) {

            e.preventDefault();
            var url = $(this).attr('href');
            var regid = $(this).attr('data-jagger-regpolicy');
            var ecid = $(this).attr('data-jagger-ec');
            if (url === undefined) {
                return false;
            }
            var formremover = $("#confirmremover");

            var form = formremover.find('form').first();
            form.attr('action', url);
            if (ecid !== undefined && ecid !== null) {
                form.attr('data-jagger-ec', ecid);
            }
            else {
                form.attr('data-jagger-regpolicy', regid);
            }
            formremover.foundation('reveal', 'open');

        });

        $(".dhelp").click(function () {
            var curSize = parseInt($(this).css('font-size'));
            if (curSize <= 10) {
                $(this).css('font-size', curSize + 5).removeClass('zoomin').addClass('zoomout');
            }
            else {
                $(this).css('font-size', curSize - 5).removeClass('zoomout').addClass('zoomin');
            }
        });
        $("form#availablelogos input[name='filename']").click(function () {
            $(this).after($("form#availablelogos div.buttons").show());

        });


        $("button.reqattrrm").click(function () {
            var fieldset = $(this).closest('fieldset');
            fieldset.remove();
        });

        $('form#availablelogos').on('submit', function (e) {
            e.preventDefault();
            var result = $("div.uploadresult");
            var assignedGrid = $("div.assignedlogosgrid").text();
            $('#uploadlogo').unbind();
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $("form#availablelogos").serializeArray(),
                dataType: 'html',
                cache: false,
                beforeSend: function () {
                    $('form#availablelogos div.buttons').hide().appendTo('form#availablelogos');
                },
                success: function (data) {

                    $('form#availablelogos #filename').prop('checked', false);
                    $.ajax({
                        type: 'GET',
                        url: assignedGrid,
                        cache: false,
                        success: function (data) {
                            $('#uploadlogo').unbind();
                            $("div#t1").empty().append(data);

                            $("#assignedlogos").unbind();
                            $("#availablelogos").unbind();
                            GINIT.initialize();
                        }
                    });
                    $('#spinner').hide();
                    result.html(data).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%",],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '200px',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }

                    });

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#spinner').hide();
                    result.css('color', 'red');
                    result.html(jqXHR.responseText).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%",],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '200px',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }
                    });

                }

            });

        });
        $('#uploadlogo').on('submit', (function (e) {
            e.preventDefault();
            var formData = new FormData(document.forms.namedItem("uploadlogo"));
            var result = $("div.uploadresult");
            var gridUrl = $("div.availablelogosgrid").text();
            var gridUrl2 = $("div.assignedlogosgrid").text();
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                dataType: 'html',
                cache: false,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    result.html('');
                    result.css('color', 'black');
                    $("form#availablelogos div.buttons").hide().appendTo("form#availablelogos");

                    $('#spinner').show();
                },
                success: function (data1) {
                    $('#spinner').hide();
                    result.html(data1);
                    $("div.uploadresult").append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%",],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '200px',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }


                    });
                    $.ajax({
                        type: 'GET',
                        url: gridUrl2,
                        cache: false,
                        success: function (data3) {
                            $("form#assignedlogos").replaceWith(data3);
                            $('#availablelogos').unbind();
                            $('form#assignedlogos').unbind();
                            $('#uploadlogo').unbind();
                            $("table#details").unbind();
                            $("form#availablelogos input[name='filename']").unbind("click");
                            GINIT.initialize();
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            $('#spinner').hide();
                            $('#availablelogos').unbind();
                            $('#assignedlogos').unbind();
                            $('#uploadlogo').unbind();
                            $("table#details").unbind();
                            $("form#availablelogos input[name='filename']").unbind("click");
                            GINIT.initialize();
                        }

                    });
                    $.ajax({
                        type: 'GET',
                        url: gridUrl,
                        cache: false,
                        success: function (data2) {
                            $("form#availablelogos").replaceWith(data2);
                            $('form#availablelogos').unbind();
                            $('form#assignedlogos').unbind();
                            $('#uploadlogo').unbind();
                            $("table#details").unbind();
                            $("form#availablelogos input[name='filename']").unbind("click");
                            GINIT.initialize();
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            $('#spinner').hide();
                            $('#availablelogos').unbind();
                            $('#uploadlogo').unbind();
                            $("table#details").unbind();
                            $("form#availablelogos input[name='filename']").unbind("click");
                            result.html(jqXHR.responseText).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                                closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                                position: ["20%",],
                                overlayId: 'simpledialog-overlay',
                                minHeight: '200px',
                                containerId: 'simpledialog-container',
                                onShow: function (dialog) {
                                    var modal = this;
                                }
                            });
                            GINIT.initialize();
                        }
                    });

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#spinner').hide();
                    result.html(jqXHR.responseText).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%",],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '200px',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }
                    });
                    result.css('color', 'red');
                }
            }).done(function () {
                //  $('#availablelogos').unbind();
                //  $("table#details").unbind();
                //       $('#uploadlogo').unbind();
                //  GINIT.initialize();

            });
        }));

        //   $("fieldset#general label").autoWidth();
        $("li.fromprevtoright").each(function () {
            var prevli = $(this).prev();
            var prevliOffset = prevli.offset().left;
            var previnput = $(this).prev().find("input,textarea").last();
            var previnputOffset = previnput.offset().left;
            var previnputWidth = previnput.width();
            var ln = (previnputOffset + previnputWidth) - prevliOffset;
            $(this).css('text-align', 'right').width(ln);
        });


        $("form#assignedlogos input[name='logoid']").click(function () {
            $(this).after($("div#unsignlogosbtn").show());


        });
        $("form#applyforaccount").on('submit', function (e) {
            e.preventDefault();
            var result = $("div.result");
            var postdata = $("form#applyforaccount").serializeArray();
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: postdata,
                dataType: 'html',
                cache: false,
                beforeSend: function () {
                    result.html('');
                    $('#spinner').show();
                },
                success: function (data) {
                    $('#spinner').hide();
                    result.html(data);
                    $("form#applyforaccount").remove();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#spinner').hide();
                    result.html(jqXHR.responseText);
                    result.css('color', 'red');
                    $("form#applyforaccount").remove();
                }

            });

        });
        $("form#assignedlogos").on('submit', (function (e) {
            e.preventDefault();
            var result = $("div.uploadresult");
            var postdata = $("form#assignedlogos").serializeArray();
            var checkedObj = $('input[name=logoid]:radio:checked');
            var messagereveal = $('#messagereveal');
            var infomsg = messagereveal.find("p.infomsg");
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: postdata,
                dataType: 'html',
                cache: false,
                beforeSend: function () {
                    $("div#unsignlogosbtn").hide().appendTo("form#assignedlogos");
                    result.html('');
                    $('#spinner').show();
                },
                success: function (data) {
                    $('#spinner').hide();
                    infomsg.html(data);
                    messagereveal.foundation('reveal', 'open');
                    checkedObj.parent().remove();

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#spinner').hide();
                    infomsg.html(jqXHR.responseText);
                    messagereveal.foundation('reveal', 'open');

                }
            });

        }));
        $("button.updatenotifactionstatus").click(function (e) {
            var notificationupdateform = $("#notificationupdateform");
            var related;
            var notid = $(this).attr('value');
            var ctbl = $(this).closest("tbody");
            var ctr = $(this).closest("tr");
            var subsriptionstatus = ctr.find('div.subscrstatus:first');
            var posturl = baseurl + 'notifications/subscriber/updatestatus/' + notid;
            notificationupdateform.attr('action', posturl);
            notificationupdateform.find("#noteid").first().val(notid);

        });


        $('a.showmetadata').click(function () {
            var result = $("div.metadataresult");
            var url = $(this).attr('href');
            //  var height = window.height();
            //  var width = window.width();
            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'xml',
                cache: true,
                timeout: 10000,
                success: function (data) {
                    $('#spinner').hide();
                    var xmlstr = data.xml ? data.xml : (new XMLSerializer()).serializeToString(data);
                    result.text(xmlstr).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        containerCss: {
                            padding: 5,
                            width: 800
                        },
                        maxHeight: 800,
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["10%"],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '500',
                        minWidth: '500',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }

                    });
                },
                beforeSend: function () {
                    result.text('');
                    $('#spinner').show();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#spinner').hide();
                    window.alert(jqXHR.responseText);
                }
            });
            return false;
        });

        $('#fvform').submit(function (e) {
            e.preventDefault();
            var fvform = $(this);
            var str = fvform.serializeArray();
            var url = fvform.attr('action');
            var fvid = fvform.find("button:focus").attr('id');

            $.ajax({
                type: "POST",
                url: url,
                cache: false,
                data: str,
                timeout: 120000,
                success: function (json) {
                    $('#spinner').hide();
                    var data = $.parseJSON(json);
                    if (!data) {
                        alert('no data received from upstream server');
                    }
                    else {
                        if (data.returncode) {
                            $("span#fvreturncode").append(data.returncode);
                            $("div#fvresult").show();
                        }
                        if (data.returncode === "success") {
                            fvform.find("button:focus").css("background-color", "#00aa00");
                            fvform.find("button:focus").data("passed", "true");
                            fvform.find("button:focus").attr("disabled", "true");
                        } else if (data.returncode === "error") {
                            fvform.find("button:focus").css("background-color", "#aa0000");
                            fvform.find("button:focus").data("passed", "false");
                        }
                        if (data.message) {
                            var msgdata;
                            $.each(data.message, function (i, v) {
                                $.each(v, function (j, m) {
                                    msgdata = '<div>' + i + ': ' + m + '</div>';
                                    $("div#fvmessages").append(msgdata);
                                });
                            });

                        }

                    }
                },
                beforeSend: function () {
                    $("span#fvreturncode").text('');
                    $("div#fvmessages").text('');
                    $('#spinner').show();
                },
                error: function (x, t, m) {
                    $('#spinner').hide();
                    if (t === 'timeout') {
                        window.alert('got timeout from validation server');
                    }
                    else {
                        window.alert("unknown problem with receiving data");
                    }
                }
            });

            //return false;
        });

        $("form#approvequeue").submit(function (e) {
            var result = 0;
            var validators = 0;

            $("button[name='mandatory']").each(function (i) {
                if ($(this).data("passed") === "true") {
                    result += 1;
                }

                validators += 1;
            });

            if (validators !== result) {
                window.alert('All mandatory validations have to pass successfully!');
                e.preventDefault();
            }
        });


        $(document).on('click', '.fmembers', 'a', function () {

            var link = $(this), url = link.attr("href");
            var row = $(this).parent().parent();
            if ($(row).hasClass('opened') === true) {
                $(row).next().remove();
                $(row).removeClass('opened').removeClass('highlight');

            }
            else {
                var value = $('<ul/>');

                $.ajax({
                    url: url,
                    timeout: 9500,
                    cache: true,
                    success: function (data) {
                        $('#spinner').hide();
                        var stitle;
                        var nlist;
                        var div_data;
                        $(row).addClass('opened').addClass('highlight');
                        if (!data) {
                            window.alert('no data');
                        }
                        else {
                            if (!data.idp && !data.sp && !data.both) {
                                div_data = '<div>' + data.definitions.nomembers + '</div>';
                                value.append(div_data);
                            }
                            else {
                                var preurl = data.definitions.preurl;
                                if (data.idp) {
                                    stitle = $('<div>' + data.definitions.idp + '</div>');
                                    nlist = $('<ol/>');
                                    $.each(data.idp, function (i, v) {
                                        div_data = '<li class="homeorg"><a href="' + preurl + v.pid + '">' + v.pname + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                                if (data.sp) {
                                    stitle = $('<div>' + data.definitions.sp + '</div>');
                                    nlist = $('<ol/>');
                                    $.each(data.sp, function (i, v) {
                                        div_data = '<li class="resource"><a href="' + preurl + v.pid + '">' + v.pname + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                                if (data.both) {
                                    stitle = $('<div>' + data.definitions.both + '</div>');
                                    nlist = $('<ol/>');
                                    $.each(data.both, function (i, v) {
                                        div_data = '<li class="both"><a href="' + preurl + v.pid + '">' + v.pname + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                            }
                        }


                    },
                    beforeSend: function () {
                        $('#spinner').show();
                    },
                    error: function () {
                        $('#spinner').hide();
                        window.alert('problem with loading data');
                    }
                }).done(function () {
                        var nextrow = '<tr class="feddetails"><td colspan="7"><ul class="feddetails">' + value.html() + '</ul></td></tr>';
                        $(nextrow).insertAfter(row);
                    }
                );
            }

            return false;


        });
        $("a#getmembers").click(function () {
            var link = $(this), url = link.attr("href");
            var value = $('<ul/>');

            $.ajax({
                url: url,
                timeout: 22500,
                cache: true,
                success: function (json) {
                    $('#spinner').hide();
                    var data = $.parseJSON(json);
                    if (!data) {
                        window.alert('no data');
                    }
                    else {
                        var nlist = $('<div/>');
                        nlist.addClass('zebralist row');
                        nlist.css("list-style-type", "decimal");
                        var div_data;
                        var n = 1;
                        var counter = 1;
                        $.each(data, function (i, v) {
                            var span_feds = $('<span/>');
                            $.each(v.feds, function (x, z) {
                                var spanb = '<span class="label">' + z + '</span>&nbsp;';
                                span_feds.append(spanb);
                            });
                            div_data = '<div class="large-12 columns" style="margin-top: 2px; margin-bottom: 2px"><div class="large-9 columns">' + counter + '. <a href="' + v.url + '">' + v.name + '</a> <i> (' + v.entityid + ') </i></div><div class="fedlbl large-3 end text-right columns">' + span_feds.html() + '</div></div>';
                            nlist.append(div_data);
                            n = n + 1;
                            counter = counter + 1;
                        });
                        value.append(nlist);

                    }
                },
                beforeSend: function () {
                    $('#spinner').show();
                },
                error: function () {
                    $('#spinner').hide();
                    window.alert('problem with loading data');
                }

            }).done(function () {
                var nextrow = value.html();
                //$(nextrow).insertAfter(row);
                $("div#membership").replaceWith(nextrow);

            });
            return false;
        });
        $('.accordionButton').addClass('off');

        $('.accordionButton').mouseover(function () {
            $(this).addClass('over');

            //ON MOUSEOUT REMOVE THE OVER CLASS
        }).mouseout(function () {
            $(this).removeClass('over');
        });

        $('.accordionButton').click(function () {


            //REMOVE THE ON CLASS FROM ALL BUTTONS
            $('.accordionButton').removeClass('on');
            $('.accordionButton').addClass('off');
            //NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
            $('.accordionContent').slideUp('fast');

            //IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
            if ($(this).next().is(':hidden') === true) {

                //ADD THE ON CLASS TO THE BUTTON
                $(this).addClass('on');
                $(this).removeClass('off');


                //OPEN THE SLIDE
                $(this).next().slideDown('fast');
            }

        });

        $('.accordionContent').hide();
    }

};

$(document).ready(function () {

    GINIT.initialize();


// idp/sp editform
    var latlngVal = /^-?([0-8]?[0-9]|90)\.[0-9]{1,6},-?((1?[0-7]?|[0-9]?)[0-9]|180)\.[0-9]{1,6}$/;
    var providerEditForm = $('#providereditform');
    if (providerEditForm.length) {

        var tabMap = $("#uihints");

        var addGeoBtn = $("#addlatlng");
        var markers = [];
        var gMarkers = [];

        var mapCanvas = providerEditForm.find("#map-canvas");
        if (mapCanvas.length) {
            google.maps.event.addDomListener(window, 'load', mapInitialize);


            var georows = $("#geogroup").first();


            markers = [];

            georows.find('input').each(function (e) {
                var geoLatLng = $(this).val().split(',');
                markers.push(geoLatLng);
            });


            var markersSet = false;
            tabMap.on('toggled', function (event, tab) {

                if (markersSet === false) {
                    var bounds = new google.maps.LatLngBounds();
                    for (var i = 0, tot = markers.length; i < tot; i++) {
                        var myLatlng = new google.maps.LatLng(markers[i][0], markers[i][1]);
                        var marker = new google.maps.Marker({
                            position: myLatlng,
                            map: map,
                            title: ''
                        });

                        marker.setMap(map);
                        gMarkers.push(marker);
                        bounds.extend(marker.position);
                    }
                    markersSet = true;

                    var center = map.getCenter();
                    google.maps.event.trigger(map, "resize");
                    map.setCenter(center);
                    if (markers.length > 0) {
                        map.fitBounds(bounds);
                    }
                }
                var listener = google.maps.event.addListener(map, "idle", function () {
                    if (map.getZoom() > 16) map.setZoom(16);
                    google.maps.event.removeListener(listener);
                });
            });

        }
        providerEditForm.on('click', 'a.rmgeo', function (e) {

            var inputtorm = $(this).closest('div.georow').find('input').first().val();
            var geoLatLngToRm = inputtorm.split(',');
            for (var i = 0, tot = gMarkers.length; i < tot; i++) {
                var gmark = gMarkers[i];
                console.log(gmark.position.lat() + ' ?? ' + geoLatLngToRm[0]);
                if (parseFloat(gmark.position.lat()) === parseFloat(geoLatLngToRm[0])) {
                    gmark.setMap(null);
                    gMarkers.splice(i, 1);
                }
            }
            $(this).closest('div.georow').remove();
            return false;
        });

        addGeoBtn.on('click', function (e) {

            e.preventDefault();
            var inputgeo = $("#latlng").val();
            if (!latlngVal.test(inputgeo)) {
                window.alert('incorrect value');
            }
            else {
                var bounds = new google.maps.LatLngBounds();
                var rname = 'z';
                var possible = "0123456789";
                for (var i = 0; i < 5; i++) {
                    rname += possible.charAt(Math.floor(Math.random() * possible.length));
                }
                var html = '<div class="small-12 column collapse georow"><div class="small-11 column"><input name="f[uii][idpsso][geo][' + rname + ']" type="text" value="' + inputgeo + '" readonly="readonly"></div><div class="small-1 column"><a href="#" class="rmgeo"><i class="fi-trash alert" style="color: red"></i></a></div></div>';
                $(html).appendTo($('#geogroup'));
                var geoLatLng = inputgeo.split(',');
                var myLatlng = new google.maps.LatLng(geoLatLng[0], geoLatLng[1]);

                var marker = new google.maps.Marker({
                    position: myLatlng,
                    map: map,
                    title: ''
                });
                marker.setMap(map);
                gMarkers.push(marker);

                bounds.extend(marker.position);


            }
        });

        var langinputrmval;
        providerEditForm.find('div.group').each(function () {
            var selectInside = $(this).find('select').first();
            $(this).find('button.langinputrm').each(function () {
                langinputrmval = $(this).attr('value');
                selectInside.find("option[value=" + langinputrmval + "]").each(function () {
                    $(this).toggleOption(true);
                    $(this).attr('disabled', true);
                });
            });
        });


        $("button#idpssoadddomainhint").click(function () {
            var rname = '';
            var possible = "0123456789";
            for (var i = 0; i < 5; i++) {

                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }

            var rmbtn = $("button#helperbutttonrm").html();
            var inputname = $(this).attr('value');

            var rowinputname = 'f[uii][idpsso][domainhint][n' + rname + ']';
            var row = createRowWithLangRm('Domain Hint', 'Domain Hint', rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });
        $("button#idpssoaddiphint").click(function () {
            var rname = '';
            var possible = "0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }

            var rmbtn = $("button#helperbutttonrm").html();
            var inputname = $(this).attr('value');

            var rowinputname = 'f[uii][idpsso][iphint][n' + rname + ']';
            var row = createRowWithLangRm('IP Hint', 'IP Hint', rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });

        $("button#addlhelpdesk").click(function () {
            var selected = $("span.lhelpdeskadd option:selected").first();
            var nf = selected.val();
            var rmbtn = $("button#helperbutttonrm").html();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[lhelpdesk][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });
        $("button#addldisplayname").click(function () {
            var selected = $("span.ldisplaynameadd option:selected").first();
            var nf = selected.val();
            var rmbtn = $("button#helperbutttonrm").html();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[ldisplayname][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });


        $("button#idpadduiidisplay").click(function () {
            var selected = $("span.idpuiidisplayadd option:selected").first();
            var nf = selected.val();
            var rmbtn = $("button#helperbutttonrm").html();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[uii][idpsso][displayname][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });
        $("button#idpadduiihelpdesk").click(function () {
            var selected = $("span.idpuiihelpdeskadd option:selected").first();
            var nf = selected.val();
            if (typeof nf === 'undefined') {
                return false;
            }
            var rmbtn = $("button#helperbutttonrm").html();
            var nfv = selected.text();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[uii][idpsso][helpdesk][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });
        $("button#spadduiidisplay").click(function () {
            var selected = $("span.spuiidisplayadd option:selected").first();
            var nf = selected.val();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var rmbtn = $("button#helperbutttonrm").html();
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[uii][spsso][displayname][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });
        $("button#spadduiihelpdesk").click(function () {
            var selected = $("span.spuiihelpdeskadd option:selected").first();
            var nf = selected.val();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var rmbtn = $("button#helperbutttonrm").html();
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[uii][spsso][helpdesk][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });
        $("button#spadduiidesc").click(function () {
            var selected = $("span.spuiidescadd option:selected").first();
            var nf = selected.val();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var rmbtn = $("button#helperbutttonrm").html();
            selected.attr('disabled', true).attr('selected', false);

            $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[uii][spsso][desc][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><textarea id=\"f[uii][spsso][desc][" + nf + "]\" name=\"f[uii][spsso][desc][" + nf + "]\" rows=\"5\" cols=\"40\"/></textarea></div><div class=\"small-3 large-2 columns\"><button type=\"button\" class=\"btn langinputrm button tiny left inline alert\" name=\"uiispdescrm\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        });


        $("button#addlname").click(function () {
            var selected = $("span.lnameadd option:selected").first();
            var nf = selected.val();
            if (typeof nf === 'undefined') {
                return false;
            }
            var rmbtn = $("button#helperbutttonrm").html();
            var nfv = selected.text();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[lname][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });


        var btnNewLang = $("button[name='addinnewlang']");
        btnNewLang.on('click', function (e) {
            var el = $(this);
            var group = el.closest('fieldset');

            var langDropdown = el.closest('span');
            if (langDropdown.length === 0) {


                return false;
            }
            var selected = langDropdown.find(':selected').first();
            if (selected.length === 0) {

                return false;
            }
            var isdisabled = selected.attr('disabled');
            if (isdisabled !== null && isdisabled === 'disabled') {


                return false;
            }
            var langselected = selected.val();
            var langselectedStr = selected.text();
            if (typeof langselected === 'undefined' || langselected === '') {

                return false;
            }
            var rmbtn = $("button#helperbutttonrm").html();
            var inputname = el.attr('value').replace('XXX', langselected);
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = inputname;
            var row = createRowWithLangRm(langselected, langselectedStr, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());


        });


        $("button#idpadduiidesc").click(function () {
            var selected = $("span.idpuiidescadd option:selected").first();
            var nf = selected.val();
            if (typeof nf === 'undefined') {
                return false;
            }
            var rmbtn = $("button#helperbutttonrm").html();
            var nfv = selected.text();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[uii][idpsso][desc][" + nf + "]\" class=\"right inline\">" + nfv + " </label></div><div class=\"small-6 large-7 columns\"><textarea id=\"f[uii][idpsso][desc][" + nf + "]\" name=\"f[uii][idpsso][desc][" + nf + "]\" rows=\"5\" cols=\"40\"/></textarea></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm button tiny left inline\" name=\"ldesc\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        });
        $("button#addlprivacyurlspsso").click(function () {
            var selected = $("span.addlprivacyurlspsso option:selected").first();
            var nf = selected.val();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var rmbtn = $("button#helperbutttonrm").html();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[prvurl][spsso][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });
        $("button#addlprivacyurlidpsso").click(function () {
            var selected = $("span.addlprivacyurlidpsso option:selected").first();
            var nf = selected.val();
            var rmbtn = $("button#helperbutttonrm").html();
            if (typeof nf === 'undefined') {
                return false;
            }
            var nfv = selected.text();
            var inputname = $(this).attr('value');
            selected.attr('disabled', true).attr('selected', false);
            var rowinputname = 'f[prvurl][idpsso][' + nf + ']';
            var row = createRowWithLangRm(nf, nfv, rowinputname, rmbtn);
            row.insertBefore($(this).closest('span').parent());
        });

        $("#ncontactbtn").click(function () {
            var rname = "";
            var btnvalues = $(this).attr('value').split('|');
            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            var newelement = '<div class="group"><div class="small-12 columns"><fieldset><legend>' + btnvalues[5] + '</legend><div><div class="small-12 columns"><div class="small-3 columns"><label for="f[contact][n_' + rname + '][type]" class="right inline">' + btnvalues[1] + '</label></div><div class="small-8 large-7 columns inline"><select name="f[contact][n_' + rname + '][type]"> <option value="administrative">Administrative</option> <option value="technical">Technical</option> <option value="support" selected="selected">Support</option> <option value="billing">Billing</option> <option value="other">Other</option> </select></div><div class="small-1 large-2 columns"></div></div> <div class="small-12 columns"><div class="small-3 columns"><label for="f[contact][n_' + rname + '][fname]" class="right inline">' + btnvalues[2] + '</label></div><div  class="small-8 large-7 columns"><input type="text" name="f[contact][n_' + rname + '][fname]" value="" id="f[contact][n_' + rname + '][fname]" class="right inline" /></div><div class="small-1 large-2 columns"></div></div> <div class="small-12 columns"><div  class="small-3 columns"><label for="f[contact][n_' + rname + '][sname]" class="right inline">' + btnvalues[3] + '</label></div><div class="small-8 large-7 columns"><input type="text" name="f[contact][n_' + rname + '][sname]" value="" id="f[contact][n_' + rname + '][sname]" class="right inline" /></div><div class="small-1 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[contact][n_' + rname + '][email]" class="right inline ">' + btnvalues[4] + '</label></div><div class="small-8 large-7 columns"><input type="text" name="f[contact][n_' + rname + '][email]" value="" id="f[contact][n_' + rname + '][email]" class="right inline" /></div><div class="small-1 large-2 columns"></div></div><div class="rmelbtn small-12 columns"><div class="small-9 large-10 columns"><button type="button" class="btn contactrm tiny alert button inline right" name="contact" value="' + rname + '">' + btnvalues[0] + '</button></div><div class="small-3 large-2 columns"></div></div></div></fieldset></div></div>';
            $(this).parent().before(newelement);

        });

        $("#nidpssocert").click(function () {
            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            rname = "newx" + rname;
            var newelement = '<div class="certgroup small-12 columns"><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][type]" class="inline right">Certificate type</label></div><div class="small-6 large-7 columns"><select name="f[crt][idpsso][' + rname + '][type]"> <option value="x509">x509</option> </select></div><div class="small-3 large-2 columns"></div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][usage]" class="inline right">Certificate use</label></div><div class="small-6 large-7 columns"><select name="f[crt][idpsso][' + rname + '][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][keyname]" class="inline right">KeyName</label></div><div class="small-6 large-7 columns"><input type="text" name="f[crt][idpsso][' + rname + '][keyname]" value="" id="f[crt][idpsso][' + rname + '][keyname]" class=""  /> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][certdata]" class="inline right">Certificate</label></div><div class="small-6 large-7 columns"><textarea name="f[crt][idpsso][' + rname + '][certdata]" cols="65" rows="20" id="f[crt][idpsso][' + rname + '][certdata]" class="certdata notice" ></textarea></div><div class="small-3 large-2 columns"></div></div>';
            $(this).parent().before(newelement);

        });

        $("#naacert").click(function () {

            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            rname = "newx" + rname;
            var newelement = '<div class="certgroup small-12 columns"><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][type]" class="inline right">Certificate type</label></div><div class="small-6 large-7 columns"><select name="f[crt][aa][' + rname + '][type]"> <option value="x509">x509</option> </select> </div><div class="small-3 large-2 columns"></div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][usage]" class="inline right">Usage</label></div><div class="small-6 large-7 columns"><select name="f[crt][aa][' + rname + '][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][keyname]" class="inline right">KeyName</label></div><div class="small-6 large-7 columns"><input type="text" name="f[crt][aa][' + rname + '][keyname]" value="" id="f[crt][aa][' + rname + '][keyname]" class=""  /> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][certdata]" class="inline right">Certificate</label></div><div class="small-6 large-7 columns"><textarea name="f[crt][aa][' + rname + '][certdata]" cols="65" rows="20" id="f[crt][aa][' + rname + '][certdata]" class="certdata notice" ></textarea> </div><div class="small-3 large-2 columns"></div></div> ';
            $(this).parent().before(newelement);
        });
        $("#nspssocert").click(function () {

            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            rname = "newx" + rname;
            var newelement = '<div class="certgroup small-12 columns"><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][type]" class="right inline">Certificate type</label></div><div class="small-8 large-7 columns"><select name="f[crt][spsso][' + rname + '][type]"><option value="x509">x509</option></select> </div><div class="small-1 large-2 columns end"></div></div><div class="small-12  columns"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][usage]" class="right inline">Certificate use</label></div><div class="small-8 large-7 columns"><select name="f[crt][spsso][' + rname + '][usage]"><option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select></div><div class="small-1 large-2 columns end"></div></div><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][keyname]" class="right inline">KeyName</label></div><div class="small-8 large-7 columns"><input type="text" name="f[crt][spsso][' + rname + '][keyname]" value="" id="f[crt][spsso][' + rname + '][keyname]" class=""  /></div><div class="small-1 large-2 columns end"></div> </div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][certdata]" class="right inline">Certificate</label></div><div class="small-8 large-7 columns"><textarea name="f[crt][spsso][' + rname + '][certdata]" cols="65" rows="20" id="f[crt][spsso][' + rname + '][certdata]" class="certdata" ></textarea></div><div class="small-1 large-2 columns end"></div></div><div class="small-12 columns"><div class="small-3 columns">&nbsp;</div><div class="small-6 large-7 columns"><button class="certificaterm button alert tiny right" value="' + rname + '" name="certificate" type="button">Remove certificate</button></div><div class="small-3 large-2 columns"></div></div></div>';
            $(this).parent().before(newelement);

        });

        $("#nacsbtn").click(function () {
            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            var newelement = '<div class=\"srvgroup\"><div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for="f[srv][AssertionConsumerService][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class=\"small-5 columns inline\"><select name="f[srv][AssertionConsumerService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:PAOS">urn:oasis:names:tc:SAML:2.0:bindings:PAOS</option> <option value="urn:oasis:names:tc:SAML:2.0:profiles:browser-post">urn:oasis:names:tc:SAML:2.0:profiles:browser-post</option> <option value="urn:oasis:names:tc:SAML:1.0:profiles:browser-post">urn:oasis:names:tc:SAML:1.0:profiles:browser-post</option> <option value="urn:oasis:names:tc:SAML:1.0:profiles:artifact-01">urn:oasis:names:tc:SAML:1.0:profiles:artifact-01</option> </select></div> <div class="small-4 columns"><div class="small-6 columns"><input type="text" name="f[srv][AssertionConsumerService][n_' + rname + '][order]" value="" id="f[srv][AssertionConsumerService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div><div class="small-6 columns"><label for="f[srv][AssertionConsumerService][n_' + rname + '][default]">Is default</label><input type="radio" name="f[srv][AssertionConsumerService][n_' + rname + '][default]" value="1" id="f[srv][AssertionConsumerService][n_' + rname + '][default]" class="acsdefault"/></div></div> </div>          <div class="small-12 columns"><div class="small-3 columns"><label for="f[srv][AssertionConsumerService][n_' + rname + '][url]" class=\"right inline\">URL</label></div><div class=\"small-8 large-7 columns inline\"><input name="f[srv][AssertionConsumerService][n_' + rname + '][url]" id="f[srv][AssertionConsumerService][n_' + rname + '][url]" type="text"></div><div class=\"small-3 large-2 columns\"><button class="inline left button tiny alert rmfield"  name="rmfield" type="button">Remove</button></div></div></div>';
            $(this).parent().before(newelement);

        });
        $("#nidpartifactbtn").click(function () {
            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            var newelement = '<div class="srvgroup"><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][IDPArtifactResolutionService][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class="small-6 large-7 columns inline"><select name="f[srv][IDPArtifactResolutionService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:SOAP</option> <option value="urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding">urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding</option></select></div> <div class="small-2 large-1 columns end"><input type="text" name="f[srv][IDPArtifactResolutionService][n_' + rname + '][order]" value="" id="f[srv][IDPArtifactResolutionService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div></div> <div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" class=\"right inline\">URL</label></div><div class="small-6 large-7 columns inline"><input name="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" id="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" type="text"></div><div class="small-3 large-2 columns"><button class="inline left button tiny alert rmfield" value="" name="rmfield" type="button">Remove</button></div></div>';
            $(this).parent().before(newelement);
        });


        $("#nspartifactbtn").click(function () {
            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            var newelement = '<div class="srvgroup"><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][SPArtifactResolutionService][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class=\"small-8 large-7 columns inline\"><select name="f[srv][SPArtifactResolutionService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:SOAP</option> <option value="urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding">urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding</option></select> </div> <div class=\"small-1  columns left\"><input type="text" name="f[srv][SPArtifactResolutionService][n_' + rname + '][order]" value="" id="f[srv][SPArtifactResolutionService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div></div>           <div class="small-12 columns"><div class="small-3 columns"><label for="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" class="right inline">URL</label></div><div class=\"small-6 large-7 columns inline\"><input name="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" id="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" type="text"> </div><div class=\"small-3 large-2 columns\"><button class="inline left button tiny alert rmfield"  name="rmfield" type="button">Remove</button></div></div>';
            $(this).parent().before(newelement);
        });
        $("#ndrbtn").click(function () {
            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            var newelement = '<div class="srvgroup"><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][DiscoveryResponse][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class="small-6 large-7 columns"><select name="f[srv][DiscoveryResponse][n_' + rname + '][bind]"><option value="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol">urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol</option></select> </div><div class="small-1 columns end"><input type="text" name="f[srv][DiscoveryResponse][n_' + rname + '][order]" value="" id="f[srv][DiscoveryResponse][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div></div><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][DiscoveryResponse][n_' + rname + '][url]" class="right inline">URL</label></div><div class="small-6 large-7 columns"><input name="f[srv][DiscoveryResponse][n_' + rname + '][url]" id="f[srv][DiscoveryResponse][n_' + rname + '][url]" type="text"></div><div class="small-1 columns end"><button class="rmfield button alert tiny left" name="rmfield">Remove</button></div></div></div>';
            $(this).parent().before(newelement);
        });
        $("#nribtn").click(function () {
            var rname = "";
            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            var newelement = '<div class="small-12 columns srvgroup"><div class="small-3 columns"><label for="f[srv][RequestInitiator][n_' + rname + '][url]" class="right inline">URL</label></div><div class="small-6 large-7 columns"><input name="f[srv][RequestInitiator][n_' + rname + '][url]" id="f[srv][RequestInitiator][n_' + rname + '][url]" type="text"></div><div class="small-3 large-2 columns"><button type="button" class="inline left button tiny alert rmfield" name="rmfield" value="">remove</button></div></div>';
            $(this).parent().before(newelement);
        });

        $('button.addnewlogo').click(function () {
            var logolangtxt;
            var f = $(this).closest('div.reviewlogo');
            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
            var ftype = f.attr('id');
            var type;
            var rname = '';
            if (ftype === 'idpreviewlogo') {
                type = 'idp';
            }
            else {
                type = 'sp';
            }
            for (var i = 0; i < 5; i++) {
                rname += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            var logourl = f.find("input[name='" + type + "inputurl']").attr('value');
            var logosize = f.find("input[name='" + type + "inputsize']").attr('value');
            var logolang = f.find("select[name='" + type + "logolang']").val();

            if (logolang === '0') {
                logolangtxt = 'unspec';
            }
            else {
                logolangtxt = logolang;
            }


            var hiddeninputurl = '<input type="hidden" name="f[uii][' + type + 'sso][logo][n' + rname + '][url]" value="' + logourl + '">';
            var hiddeninputsize = '<input type="hidden" name="f[uii][' + type + 'sso][logo][n' + rname + '][size]" value="' + logosize + '">';
            var hiddeninputlang = '<input type="hidden" name="f[uii][' + type + 'sso][logo][n' + rname + '][lang]" value="' + logolang + '">';
            var origblock = $('li#nlogo' + type + 'row');
            var newblock = origblock.clone(true);
            newblock.removeAttr('id');
            newblock.find('img').first().attr('src', logourl).append(hiddeninputurl).append(hiddeninputsize).append(hiddeninputlang);
            newblock.find('div.logoinfo').first().append('' + logolangtxt + '<br />').append(logourl + '<br />').append(logosize + '<br />');

            newblock.insertBefore(origblock).show();


        });

        $('button.getlogo').click(function () {

            var alertlogoretrieve, imgdiv;
            var btnname = $(this).attr('name');
            var logourl, logoreview;
            var link = $(this).attr("value");
            if (btnname === 'idpgetlogo') {
                logoreview = $('#idpreviewlogo');
                logoreview.hide();
                alertlogoretrieve = $("small.idplogoretrieve");
                alertlogoretrieve.empty().hide();
                logourl = $("[name='idplogoretrieve']").val();
                imgdiv = $("#idpreviewlogo").find("div.imgsource").first();
            }
            else {
                logoreview = $('#spreviewlogo');
                logoreview.hide();
                alertlogoretrieve = $("small.splogoretrieve");
                alertlogoretrieve.empty().hide();
                logourl = $("[name='splogoretrieve']").val();
                imgdiv = $("#spreviewlogo").find("div.imgsource").first();

            }

            var csrfname = $("[name='csrfname']").val();
            var csrfhash = $("[name='csrfhash']").val();
            var data = [{name: csrfname, value: csrfhash}, {name: 'logourl', value: logourl}];
            $.ajax({
                type: "POST",
                url: link,
                cache: false,
                data: data,
                dataType: "json",
                success: function (json) {
                    if (json) {
                        if (json.error) {
                            alertlogoretrieve.append(json.error).show();
                        }
                        else if (json.data) {

                            var jsondata = json.data;
                            var img = new Image();
                            img.onload = function () {

                            };
                            img.src = jsondata.url;
                            var sizeinfo = jsondata.width + 'x' + json.data.height;
                            var hiddeninputurl, hiddeninputsize, hiddeninputtype;
                            var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
                            var rname = '';
                            for (var i = 0; i < 5; i++) {
                                rname += possible.charAt(Math.floor(Math.random() * possible.length));
                            }
                            if (btnname === 'idpgetlogo') {
                                hiddeninputtype = '<input type="hidden" name="logotype" value="idp">';
                                hiddeninputurl = '<input type="hidden" name="idpinputurl" value="' + json.data.url + '">';
                                hiddeninputsize = '<input type="hidden" name="idpinputsize" value="' + sizeinfo + '">';

                                imgdiv.empty().append(img).append(hiddeninputurl).append(hiddeninputsize).append(hiddeninputtype);
                                $('div#idpreviewlogo div.logoinfo').empty().append(sizeinfo);
                                logoreview.show();
                            }
                            else if (btnname === 'spgetlogo') {
                                hiddeninputtype = '<input type="hidden" name="logotype" value="idp">';
                                hiddeninputurl = '<input type="hidden" name="spinputurl" value="' + json.data.url + '">';
                                hiddeninputsize = '<input type="hidden" name="spinputsize" value="' + sizeinfo + '">';
                                imgdiv.empty().append(img).append(hiddeninputurl).append(hiddeninputsize).append(hiddeninputtype);
                                $('div#spreviewlogo div.logoinfo').empty().append(sizeinfo);
                                logoreview.show();
                            }
                        }
                    }
                }
            });
        });


        providerEditForm.on("click", "button.contactrm", function (event) {
            var bval = $(this).attr('value');
            var bname = $(this).attr('name');
            var fieldset = $(this).closest('div.group');
            fieldset.remove();
        });

        providerEditForm.on("click", "button.certificaterm", function (event) {
            var bval = $(this).attr('value');
            var bname = $(this).attr('name');
            var fieldset = $(this).closest('div.certgroup');
            fieldset.remove();
        });

        providerEditForm.on("click", "input.acsdefault", function (event) {
            if ($(this).is(":checked")) {
                $(".acsdefault").not(this).removeAttr("checked");
            }
        });

        providerEditForm.on("click", "button.rmfield", function (event) {
            var lrow = $(this).closest('div.srvgroup');
            var bval = $(this).attr('value');
            var bname = $(this).attr('name');
            lrow.find("input").each(function () {
                $(this).attr('value', '');
            });
            lrow.find("textarea").each(function () {
                $(this).val("");
            });
            lrow.remove();
        });


        providerEditForm.on("click", "button.langinputrm", function (event) {
            event.preventDefault();
            var lrow = $(this).closest('div').parent();
            var bval = $(this).attr('value');
            var bname = $(this).attr('name');
            var select = $(this).closest('div.group').find('select').first();
            lrow.find("input").each(function () {
                $(this).attr('value', '');
            });
            lrow.find("textarea").each(function () {
                $(this).val("");
            });
            select.find("option[value=" + bval + "]").each(
                function () {
                    $(this).toggleOption(true);
                    $(this).attr('disabled', false);
                }
            );
            lrow.remove();

        });

    }

    ////////////// new idpmatrix
    if ($('#matrixloader').length > 0) {
        var formupdater = $('#policyupdater');
        var formupdaterUrl = formupdater.attr('data-jagger-link');
        var formupdaterAction = $(formupdater).find('form').first();
        var providerdetailurl = $('#matrixloader').attr('data-jagger-providerdetails');
        var mrequester = $(formupdater).find("span.mrequester").first();
        var mattribute = $(formupdater).find("span.mattribute").first();
        var updatebutton = $(formupdater).find("div.yes").first();
        var attrflow = $(formupdater).find("div.attrflow").first();
        var matrixdiv = $('#idpmatrixdiv');
        var clickedcell;
        formupdater.on('click', 'div.yes', function (event) {
            event.preventDefault();

            var actionUrl = formupdaterAction.attr('action');
            $.ajax({
                type: "POST",
                url: actionUrl,
                data: formupdaterAction.serializeArray(),
                success: function (data) {
                    formupdater.foundation('reveal', 'close');
                    if (!clickedcell.hasClass('dis')) {
                        var cell = $.trim(clickedcell.text());
                        if ((data === "2" && (cell === "R" || cell === "D")) || (data === "1" && cell === "R")) {
                            clickedcell.attr('class', 'perm');
                        }
                        else if ((data === "2c" && (cell === "R" || cell === "D")) || (data === "1c" && cell === "R")) {
                            clickedcell.attr('class', 'spec');
                        }
                        else if ((data === "1" && cell === "D") || (data === "0")) {
                            clickedcell.attr('class', 'den');
                        }
                        else if ((data === "1c" && cell === "D") || (data === "0c")) {
                            clickedcell.attr('class', 'den');
                        }

                    }
                }
            });
        });
        matrixdiv.on('click', 'td', function (event) {
            clickedcell = $(this);

            var splink = $(this).attr("data-jagger-entidlink");
            if (splink !== undefined) {
                document.location.href = providerdetailurl + '/' + splink;
                return false;
            }

            var spiddata = $(this).attr("data-jagger-spid");
            var attrdata = $(this).attr("data-jagger-attrid");
            //var attrclass = $(this).class();
            if (spiddata !== undefined && attrdata !== undefined) {
                //formupdater.foundation('reveal', 'open');
                //alert(spiddata + 'and '+attrdata);
                $.ajax({
                    type: "GET",
                    url: formupdaterUrl + '/' + spiddata + '/' + attrdata,
                    cache: false,
                    dataType: "json",
                    success: function (json) {
                        formupdaterAction.find("select[name='policy']").prop('selected', false).filter('[value=""]').prop('selected', true);
                        formupdaterAction.find("input[name='attribute']").first().val(json.attributename);
                        formupdaterAction.find("input[name='requester']").first().val(json.requester);

                        mrequester.html(json.requester);
                        mattribute.html(json.attributename);
                        var tbody_data = $('<tbody></tbody>');
                        var thdata = '<thead><tr><th colspan="2">Current attribute flow</th></tr></thead>';
                        $.each(json.details, function (i, v) {
                            var trdata = '<tr><td>' + v.name + '</td><td>' + v.value + '</td/></tr>';
                            tbody_data.append(trdata);
                        });
                        var tbl = $('<table/>').css({'font-size': 'smaller'}).addClass('detailsnosort').addClass('small-12').addClass('columns');
                        var pl = $('<div/>');
                        tbl.append(thdata);
                        tbl.append(tbody_data);
                        pl.append(tbl);
                        attrflow.html(pl.html());
                        formupdater.foundation('reveal', 'open');

                    }
                });
            }
        });
        var pid = $('#matrixloader').attr("data-jagger-link");
        if (typeof pid === "undefined") {
            return false;
        }
        $.ajax({
            type: "GET",
            url: pid,
            cache: false,
            dataType: "json",
            success: function (json) {
                $('#spinner').hide();

                if (json) {
                    var startTime = new Date();
                    var cl;
                    var mlegend = '<div><span class="den">&nbsp;&nbsp;&nbsp;</span> <span>denied</span></div>' +
                        '<div><span class="perm">&nbsp;&nbsp;&nbsp;</span> <span>permitted</span></div>' +
                        '<div><span class="dis">&nbsp;&nbsp;&nbsp;</span> <span>not supported</span></div>' +
                        '<div><span>R</span> <span>required</span></div>' +
                        '<div><span>D</span> <span>desired</span></div>';
                    var attrdefs = json.attributes;
                    var policies = json.policies;
                    var countpolicies = json.total;
                    var responsemsg = json.message;
                    if (countpolicies !== undefined && countpolicies === 0 && responsemsg !== undefined) {
                        var alerthtml = '<div class="small-12 medium-11 columns small-centered"><div data-alert class="alert-box warning">' + responsemsg + '</div></div>';
                        matrixdiv.html(alerthtml);
                        return false;
                    }
                    var countAttr = 0;
                    var tbl = '<table class="table table-header-rotated" id="idpmatrixresult"><thead><tr>';
                    tbl += '<th style="background: white">' + mlegend + '</th>';
                    $.each(attrdefs, function (a, p) {
                        tbl += '<th class="rotate"><div><span>' + a + '</span></div></th>';
                        countAttr++;
                    });
                    if (countAttr > 52) {
                        $("#container").css({"max-width": "100%"});
                    }

                    var cell, requiredAttr, pAttr;
                    tbl += '</tr></thead><tbody>';
                    $.each(policies, function (i, a) {

                        tbl += '<tr><td data-jagger-entidlink="' + a.spid + '" class="searchcol"><span data-tooltip aria-haspopup="true" class="has-tip" data-options="disable_for_touch:true" title="' + i + '" >' + a.name + '</span><span class="hidden">' + i + '</span></td>';
                        $.each(attrdefs, function (k, v) {
                            requiredAttr = null;
                            if (a['attributes'][k] !== undefined) {
                                pAttr = a['attributes'][k];
                            }
                            else {
                                pAttr = null;
                            }
                            if (a['req'][k] !== undefined) {
                                requiredAttr = a['req'][k];
                            }
                            if (requiredAttr !== null) {
                                cell = requiredAttr[0].toUpperCase();
                            }
                            else {
                                cell = '';
                            }
                            if (pAttr !== null) {
                                if (pAttr === 0) {
                                    cl = 'den';
                                }
                                else if (pAttr === 1) {
                                    if (a['custom'][k] !== undefined) {
                                        cl = 'spec';
                                    }
                                    else {
                                        cl = 'perm';
                                    }
                                }
                                else {
                                    cl = 'dis';
                                }
                            }
                            else {
                                cl = 'dis';
                            }


                            tbl += '<td data-jagger-spid="' + a.spid + '" data-jagger-attrid="' + v + '" class="' + cl + '" title="' + k + '">';
                            tbl += cell + '</td>';
                        });
                    });
                    tbl += '</tbody></table>';

                    var endTime = new Date();
                    var durationTime = endTime - startTime;
                    console.log('time of generating matrix: ' + durationTime);

                    matrixdiv.html(tbl);
                    var end2Time = new Date();
                    durationTime = end2Time - endTime;
                    console.log('time of input matrinx into DOM: ' + durationTime);
                    $("#idpmatrixresult").searcher({
                        inputSelector: "#tablesearchinput",
                        textSelector: ".searchcol"
                    });
                }
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function (xhr, status, error) {
                $('#spinner').hide();
                var alerthtml = '<div class="small-12 medium-11 columns small-centered"><div data-alert class="alert-box error">' + error + '</div></div>';
                matrixdiv.html(alerthtml);
                return false;
            }
        });
    }
///////////////////

    var helpactivity = $("#showhelps");
    if (helpactivity.length) {
        if (helpactivity.hasClass('helpactive')) {
            $(".dhelp").show();
        }
        else {
            $(".dhelp").hide();
        }
    }


    var fedlogin = $('#fedlogin').first();
    var fedloginurl = fedlogin.attr('href');
    var browsertime = new Date();
    var browsertimezone = -browsertime.getTimezoneOffset();
    fedlogin.attr('href', '' + fedloginurl + '/' + browsertimezone + '');

    if ($('#fedcategories dd.active').length) {
        var url = $('dd.active').find('a').first().attr('href');
        var value = $('table.fedistpercat');
        var data;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            dataType: "json",
            value: $('table.fedistpercat'),
            success: function (data) {
                $('#spinner').hide();

                if (!data) {
                    window.alert('no data in federation category');
                }
                else {
                    $("table.fedistpercat tbody tr").remove();
                    $.each(data, function (i, v) {
                        var tr_data = '<tr><td>' + v.name + '</td><td>' + v.urn + '</td><td>' + v.labels + '</td><td>' + v.desc + '</td><td>' + v.members + '</td></tr>';
                        value.append(tr_data);
                    });
                }
                //        GINIT.initialize();
            }
        });
    }


    $(".fedcategory").on('click', '', function (event) {

        $('dd').removeClass('active');
        $(this).closest('dd').addClass('active');
        var url = $(this).attr("href");
        var value = $('table.fedistpercat');
        var data;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            dataType: "json",
            value: value,
            success: function (data) {
                $('#spinner').hide();
                if (!data) {
                    window.alert('no data in federation category');
                }
                else {
                    $("table.fedistpercat tbody tr").remove();
                    $.each(data, function (i, v) {
                        var tr_data = '<tr><td>' + v.name + '</td><td>' + v.urn + '</td><td>' + v.labels + '</td><td>' + v.desc + '</td><td>' + v.members + '</td></tr>';
                        value.append(tr_data);
                    });
                }

            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                window.alert('problem with loading data');
            }
        }).done(function () {
            var nextrow = value.html();
            //$("table.fedistpercat").append(nextrow);
        });
        return false;
    });

});


$(function () {


    $('div.floating-menu').addClass('mobilehidden');
    $('table.idplist tr td:first-child').addClass('homeorg');
    $('table.idplist tr td:first-child span.alert').removeClass('alert').parent().addClass('alert');
    var theTable1 = $('table.filterlist');
    theTable1.find("tbody > tr").find("td:eq(1)").mousedown(function () {
    });
    $("#filter").keyup(function () {
        $.uiTableFilter(theTable1, this.value);
    });
    $('#filter-form').submit(function () {
        theTable1.find("tbody > tr:visible > td:eq(1)").mousedown();
        return false;
    }).focus();

    $('table.splist tr td:first-child span.alert').removeClass('alert').parent().addClass('alert');
    var theTable2 = $('table.splist');
    theTable2.find("tbody > tr").find("td:eq(1)").mousedown(function () {
    });
    $("#filter").keyup(function () {
        $.uiTableFilter(theTable2, this.value);
    });
    $('#filter-form').submit(function () {
        theTable2.find("tbody > tr:visible > td:eq(1)").mousedown();
        return false;
    }).focus();


    $(".datepicker").datepicker({
        dateFormat: 'yy-mm-dd'
    });

    var baseurl = $("[name='baseurl']").val();
    if (baseurl === undefined) {
        baseurl = '';
    }

    $("#responsecontainer").load(baseurl + "reports/awaiting/ajaxrefresh");
    setInterval(function () {
        $("#responsecontainer").load(baseurl + 'reports/awaiting/ajaxrefresh');
    }, 172000);
    $("#dashresponsecontainer").load(baseurl + "reports/awaiting/dashajaxrefresh");
    setInterval(function () {
        $("#dashresponsecontainer").load(baseurl + 'reports/awaiting/dashajaxrefresh');
    }, 172000);


    $.ajaxSetup({
        cache: false
    });
    $("#qcounter").load(baseurl + 'reports/awaiting/counterqueue');
    setInterval(function () {
        $("#qcounter").load(baseurl + 'reports/awaiting/counterqueue');
    }, 86000);


    $('#languageset').on('change', 'select', function (e) {
        var link = $("div#languageset form").attr('action');
        var url = link + this.value;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false
        }).done(function () {
            $('#languageset').foundation('reveal', 'close');

            setTimeout(function () {
                go_to_private_page();
            }, 1000);

        });
        return false;

    });

    $('select.nuseraccesstype').on('change', function () {
        var access = $(this).find("option:selected");
        var accessselected = access.val();
        if (accessselected === 'fed') {
            $('div.passwordrow').hide();
        }
        else {
            $('div.passwordrow').show();

        }
    });
    $("button#idpadduiiprvurl").click(function () {
        var nf = $("span.idpuiiprvurladd option:selected").val();
        var nfv = $("span.idpuiiprvurladd option:selected").text();
        $("span.idpuiiprvurladd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][idpsso][prvurl][" + nf + "]\">" + nfv + "</label><input id=\"f[uii][idpsso][prvurl][" + nf + "]\" name=\"f[uii][idpsso][prvurl][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#spadduiiprvurl").click(function () {
        var nf = $("span.spuiiprvurladd option:selected").val();
        var nfv = $("span.spuiiprvurladd option:selected").text();
        $("span.spuiiprvurladd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][spsso][prvurl][" + nf + "]\">" + nfv + "</label><input id=\"f[uii][spsso][prvurl][" + nf + "]\" name=\"f[uii][spsso][prvurl][" + nf + "]\" type=\"text\"/></li>");
    });

    $("button#addlprivacyurl").click(function () {
        var nf = $("li.addlprivacyurl option:selected").val();
        var nfv = $("li.addlprivacyurl option:selected").text();
        $("li.addlprivacyurl option[value=" + nf + "]").toggleOption(false);
        $(this).parent().append("<li class=\"localized\"><label for=\"lprivacyurl[" + nf + "]\">" + nfv + "</label><input id=\"lprivacyurl[" + nf + "]\" name=\"lprivacyurl[" + nf + "]\" type=\"text\"/></li>");
    });
    $("a#fedmetasigner").click(function () {
        var link = $(this), url = link.attr("href");
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function (data) {
                alert(data);
            }
        });
        return false;
    });
    $("a#providermetasigner").click(function () {
        var link = $(this), url = link.attr("href");
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function (data) {
                window.alert(data);
            }
        });
        return false;
    });


    $("a.downloadstat").click(function () {
        var link = $(this), url = link.attr("href");
        var data;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function (json) {
                data = $.parseJSON(json);
                if (!data) {
                    window.alert('no data');
                }
                else {
                    window.alert(data.status);
                }

            }
        });
        return false;
    });
    $("a.lateststat").click(function () {
        var link = $(this), url = link.attr("href");
        var value = $('<div id="#statisticdiag">');
        var i;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            success: function (json) {
                i = null;
                $('#spinner').hide();
                var data = $.parseJSON(json);
                if (!data) {
                    window.alert('no data');
                }
                else {
                    $("div#statisticdiag").replaceWith('<div id="statisticdiag"></div>');
                    $.each(data, function (i, v) {

                        i = new Image();
                        i.src = v.url;
                        $('#statisticdiag').append('<div style="text-align:center; font-weight: bold; width: 90%;">' + v.title + '</div>').append('<div style="font-weight: bolder; width: 90%; text-align: right;">' + v.subtitle + '</div>').append(i);

                    });
                }

            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                window.alert('problem with loading data');
            }

        });
        return false;
    });
    $("a.clearcache").click(function () {
        var link = $(this), url = link.attr("href");

        $.ajax({
            type: "GET",
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).remove()
        });
        return false;
    });


    $(".updatebookmark").on('click', function (e) {

        var link = $(this).attr("href");
        var thisobj = $(this);
        var action = $(this).attr("data-jagger-bookmark");
        var postsuccess = $(this).closest("[data-jagger-onsuccess]");
        var postaction = postsuccess.attr('data-jagger-onsuccess');
        if (link === undefined || action === undefined) {
            return false;
        }
        $.ajax({
            url: link + '/' + action,
            type: 'GET',
            success: function (data) {

                if (data && data === 'ok') {

                    if (postaction !== undefined && postaction === 'hide') {
                        postsuccess.hide();
                    }
                    else {
                        thisobj.remove();
                    }
                }

            }
        });
        return false;
    });


    $("a#synchsettings").click(function () {
        var link = $(this), url = link.attr("href");
        var value = $('<ul/>');
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function (json) {
                $('#spinner').hide();
                var data = $.parseJSON(json);
                if (!data) {
                    window.alert('no data');
                }
                else {
                    var nlist = $('<ul/>');
                    $.each(data, function (i, v) {
                        var div_data = '<li>' + v.result + '</li>';
                        nlist.append(div_data);
                    });
                    value.append(nlist);

                }
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                window.alert('problem with loading data');
            }

        }).done(function () {
            var nextrow = value.html();
            //$(nextrow).insertAfter(row);
            $("div#syncresult").replaceWith(nextrow);

        });
        return false;
    });

    $('table.reqattraddform').addClass('hidden');
    $('button.hideform').addClass('hidden');
    $('form.reqattraddform').addClass('hidden');

    $('button.showform').click(function () {
        $('table.reqattraddform').removeClass('hidden');
        $('form.reqattraddform').removeClass('hidden');
        $('button.showform').addClass('hidden');
        $('button.hideform').removeClass('hidden');
    });
    $('button.hideform').click(function () {
        $('table.reqattraddform').addClass('hidden');
        $('form.reqattraddform').addClass('hidden');
        $('button.showform').removeClass('hidden');
        $('button.hideform').addClass('hidden');
    });
    $("#sortable").sortable();
    $("div.nsortable").sortable();
    $("#sortable").disableSelection();


    //  var icons = $( ".accordionButton" ).accordion( "option", "icons" );
    //   $( ".accordionButton" ).accordion( "option", "icons", { "header": "ui-icon-plus", "headerSelected": "ui-icon-minus" } );
    //ACCORDION BUTTON ACTION (ON CLICK DO THE FOLLOWING)
    $('.accordionButton1').click(function () {


        //REMOVE THE ON CLASS FROM ALL BUTTONS
        $('.accordionButton1').removeClass('on');
        $('.accordionButton1').addClass('off');
        //NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
        $('.accordionContent1').slideUp('fast');

        //IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
        if ($(this).next().is(':hidden') === true) {

            //ADD THE ON CLASS TO THE BUTTON
            $(this).addClass('on');
            $(this).removeClass('off');


            //OPEN THE SLIDE
            $(this).next().slideDown('fast');
        }

    });


    /*** REMOVE IF MOUSEOVER IS NOT REQUIRED ***/

        //ADDS THE .OVER CLASS FROM THE STYLESHEET ON MOUSEOVER

    $('.accordionButton1').mouseover(function () {
        $(this).addClass('over');

        //ON MOUSEOUT REMOVE THE OVER CLASS
    }).mouseout(function () {
        $(this).removeClass('over');
    });

    /*** END REMOVE IF MOUSEOVER IS NOT REQUIRED ***/


    /********************************************************************************************************************
     CLOSES ALL S ON PAGE LOAD
     ********************************************************************************************************************/
//	$('.accordionContent1').toggle();

});


var ww = document.body.clientWidth;

$(document).ready(function () {
    $(".nav li a").each(function () {
        if ($(this).next().length > 0) {
            $(this).addClass("parent");
        }
    });

    $(".toggleMenu").click(function (e) {
        e.preventDefault();
        $(this).toggleClass("active");
        $(".nav").toggle();
    });
//    adjustMenu();
});

$(document).on('click', 'a.refreshurl', function (e) {
    e.preventDefault();
    var link = $(this).attr('data-jagger-getmoreajaxonclick');
    var targetresponseid = $(this).attr('data-jagger-response-msg');
    var refreshbtn = $(this);
    if (targetresponseid === undefined || targetresponseid === null) {
        console.log('attribute data-jagger-response-msg not found in element with data-jagger-getmoreajax="' + link + '" - exiting');
        return false;
    }
    else {
        console.log('attribute data-jagger-response-msg="' + targetresponseid + '" in element data-jagger-getmoreajax="' + link + '"');
    }
    var targetelement = $("div#" + targetresponseid);
    if (targetelement === undefined || targetelement === null || $(targetelement).length === 0) {
        console.log('div with id="' + targetresponseid + '" not found');

        return false;
    }
    else {
        console.log('div with id="' + targetresponseid + '" found');

    }
    $.ajax({
        dataType: "json",
        type: "GET",
        url: link,
        cache: true,
        success: function (data) {
            if (!data) {
                console.log('no json got from ' + link);
                return false;
            }
            var countresult = data.length;
            if (countresult < 1) {
                return false;
            }
            var result = $('<div/>');
            $.each(data, function (i, v) {
                result.append('<div>' + v.msg + '</div>');
            });

            targetelement.empty().append(refreshbtn).append(result).show();


        }
    });
});

$(window).bind('resize orientationchange', function () {
    ww = document.body.clientWidth;
    adjustMenu();
});

var adjustMenu = function () {
    if (ww < 768) {
        $("#filter-form").remove();
        $(".toggleMenu").css("display", "inline-block");
        if (!$(".toggleMenu").hasClass("active")) {
            $(".nav").hide();
        } else {
            $(".nav").show();
        }
        $(".nav li").unbind('mouseenter mouseleave');
        $(".nav li a.parent").unbind('click').bind('click', function (e) {
            // must be attached to anchor element to prevent bubbling
            e.preventDefault();
            $(this).parent("li").toggleClass("hover");
        });
    }
    else if (ww >= 768) {
        $(".toggleMenu").css("display", "none");
        $(".nav").show();
        $(".nav li").removeClass("hover");
        $(".nav li a").unbind('click');
        $(".nav li").unbind('mouseenter mouseleave').bind('mouseenter mouseleave', function () {
            // must be attached to li so that mouseleave is not triggered when hover over submenu
            $(this).toggleClass('hover');
        });
    }
};


$(function () {
    $("#details").tablesorter({sortList: [[0, 0]], widgets: ['zebra']});
    $(".userlist#details").tablesorter({sortList: [[3, 1], [0, 0]], widgets: ['zebra']});
    $("#options").tablesorter({sortList: [[0, 0]], headers: {3: {sorter: false}, 4: {sorter: false}}});

    $("#logotabs").tabs({
        load: function (event, ui) {
            $('#availablelogos').unbind();
            $('#assignedlogos').unbind();
            $('#uploadlogo').unbind();
            $("table#details").unbind();
            GINIT.initialize();
        }
    });

});


if ($('#usepredefined').attr('checked')) {
    $("fieldset#stadefext").hide();
}
$("#usepredefined").click(function () {
    if ($(this).is(":checked")) {
        $("#usepredefined").not(this).removeAttr("checked");
        $("fieldset#stadefext").hide();
    }
    else {
        $("fieldset#stadefext").show();
        $("#usepredefined").not(this).addAttr("checked");

    }

});


$("#nattrreqbtn").click(function (ev) {
    ev.preventDefault();
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++) {
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    var attrselect = $('select[name="nattrreq"]');
    var attrname = attrselect.find(":selected").text();
    var attrid = attrselect.find(":selected").val();

    var newelement = '<fieldset><legend>' + attrname + '</legend><div class="small-12 columns"><div class="medium-3 columns medium-text-right"><select name="f[reqattr][' + rname + '][status]"><option value="required">required</option><option value="desired">desired</option></select><input type="hidden" name="f[reqattr][' + rname + '][attrname]" value="' + attrname + '"><input type="hidden" name="f[reqattr][' + rname + '][attrid]" value="' + attrid + '"></div><div class="medium-6 collumns end"><textarea name="f[reqattr][' + rname + '][reason]"></textarea></div></div></fieldset>';
    $(this).parent().parent().before(newelement);


});

$(".pCookieAccept").on('click', function () {
    var link = $(this).attr("href");
    $.ajax({
        url: link,
        timeout: 2500
    });
    $('#cookiesinfo').hide();
    return false;
});
$("[id='f[entityid]']").change(function () {
    if ($(this).hasClass("alertonchange")) {
        var entalert = $("#entitychangealert").text();
        window.alert(entalert);
    }
});

// When DOM is ready
$(document).ready(function () {
    var baseurl = $("[name='baseurl']").val();
    if (baseurl === undefined) {
        baseurl = '';
    }
// Preload Images
    var img1 = new Image(16, 16);
    img1.src = baseurl + 'images/spinner.gif';

    var img2 = new Image(220, 19);
    img2.src = baseurl + 'images/ajax-loader.gif';

    if ($("#eds2").is('*')) {
        $("#idpSelect").modal(
            {
                Height: '500px',
                minHeight: '500px'
            }
        );
    }
    $("#vormversion").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vormversion',
            success: function (data) {
                $('#spinner').hide();
                $("#rvormversion").show();
                $("tr#rvormversion td:first-child").html(data);
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                window.alert('Error occurred');
            }
        });
        return false;
    });
    $("#vschema").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vschema',
            success: function (data) {
                $('#spinner').hide();
                $("#rvschema").show();
                $("tr#rvschema td:first-child").html(data);
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                window.alert('Error ocured');
            }
        });
        return false;
    });
    $("#vschemadb").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vschemadb',
            success: function (data) {
                $('#spinner').hide();
                $("#rvschemadb").show();
                $("tr#rvschemadb td:first-child").html(data);
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                window.alert('Error ocured');
            }
        });
        return false;
    });
    $("#vmigrate").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vmigrate',
            success: function (data) {
                $('#spinner').hide();
                $("#rvmigrate").show();
                $("tr#rvmigrate td:first-child").html(data);
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                window.alert('Error ocured');
            }
        });
        return false;
    });


    $("button#registernotification2").click(function (ev) {
        ev.preventDefault();
        var notiform = $("form#notificationaddform");
        notificationadd2('', function (ev) {
            var serializedData = notiform.serializeArray();
            $.ajax({
                type: "POST",
                url: notiform.attr('action'),
                data: $("form#notificationaddform").serializeArray(),
                success: function (data) {
                    $(".message").html(data);
                    if (data === 'OK') {
                        $(this).foundation('reveal', 'close');
                        location.reload();
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    window.alert('Error occured: ' + errorThrown);
                }
            });

        });
    });
    $('button[name="fedstatus"]').click(function (ev) {
        var btnVal = $(this).attr('value');
        var additionalMsg = $(this).attr('title');
        if (additionalMsg === undefined) {
            additionalMsg = '';
        }
        var csrfname = $("[name='csrfname']").val();
        var csrfhash = $("[name='csrfhash']").val();
        var baseurl = $("[name='baseurl']").val();
        var fedname = $("span#fednameencoded").text();
        var url = baseurl + 'federations/manage/changestatus';
        var data = [{name: 'status', value: btnVal}, {name: csrfname, value: csrfhash}, {
            name: 'fedname',
            value: fedname
        }];
        sconfirm('' + additionalMsg + '', function (ev) {
            $.ajax({
                type: "POST",
                url: url,
                data: data,
                success: function (data) {
                    if (data) {
                        if (data === 'deactivated') {
                            $('button[value="disablefed"]').hide();
                            $('button[value="enablefed"]').show();
                            $('button[value="delfed"]').show();
                            $('td.fedstatusinactive').show();
                            $('show.fedstatusinactive').show();
                        }
                        else if (data === 'activated') {
                            $('button[value="disablefed"]').show();
                            $('button[value="enablefed"]').hide();
                            $('button[value="delfed"]').hide();
                            $('td.fedstatusinactive').hide();
                            $('span.fedstatusinactive').hide();
                        }
                        else if (data === 'todelete') {
                            $('button[value="disablefed"]').hide();
                            $('button[value="enablefed"]').hide();
                            $('button[value="delfed"]').hide();
                        }

                    }
                },
                error: function (data) {
                    window.alert('Error  ocurred');
                }

            });
        });
    });
    $("#rmstatdef").on('click', 'button', function (ev) {
        var url = $("form#rmstatdef").attr('action');
        var serializedData = $(this).serialize();
        sconfirm('', function (ev) {
                $.ajax({
                    type: "POST",
                    url: url,
                    data: serializedData,
                    success: function (data) {
                        $('#resultdialog').modal({
                            position: ["20%",],
                            overlayId: 'simpledialog-overlay',
                            containerId: 'simpledialog-container',
                            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>"
                        });
                    },
                    error: function (data) {
                        window.alert('Error');
                    }
                });
            }
        );
        ev.preventDefault();
    });

    $("#rmfedcategory").click(function (ev) {
        var url = $(this).attr('action');
        var serializedData = $(this).serialize();
        sconfirm('', function (ev) {
            $('<input>').attr({
                type: 'hidden',
                id: 'formsubmit',
                name: 'formsubmit',
                value: 'remove'
            }).appendTo('form');
            $("form").submit();

        });
        ev.preventDefault();
    });
    $("#rmfedvalidator").click(function (ev) {
        var url = $(this).attr('action');
        var serializedData = $(this).serialize();
        sconfirm('', function (ev) {
            $('<input>').attr({
                type: 'hidden',
                id: 'formsubmit',
                name: 'formsubmit',
                value: 'remove'
            }).appendTo('form');
            $("form").submit();

        });
        ev.preventDefault();
    });

    function notificationadd2(message, callback) {
        $("#notificationaddmodal").foundation('reveal', 'open', {});
        $(document).on('opened', '#notificationaddmodal', function () {
            var modal = $(this);
            $('select#sfederation').parent().hide();
            $('select#sprovider').parent().hide();
            $('select#type').change(function () {
                $('select#sfederation').parent().hide();
                $('select#sprovider').parent().hide();
                var optionSelected = $(this).find("option:selected");
                var valueSelected = optionSelected.val();
                var textSelected = optionSelected.text();
                var selfed = $('#sfederation');
                var selprovider = $('#sprovider');
                selfed.find('option').remove();
                selprovider.find('option').remove();
                if (valueSelected === "joinfedreq" || valueSelected === "fedmemberschanged") {
                    $.ajax({
                        type: "GET",
                        url: baseurl + 'ajax/getfeds',
                        cache: true,
                        success: function (json) {
                            $.each(json, function (key, value) {
                                $('<option>').val(value.id).text(value.name).appendTo(selfed);
                            });
                            $('select#sfederation').parent().show();
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            $(".message").html(errorThrown);
                        }

                    });
                }
                else if (valueSelected === "requeststoproviders") {
                    $.ajax({
                        type: "GET",
                        url: baseurl + 'ajax/getproviders',
                        cache: false,
                        datatype: "json",
                        success: function (data) {
                            $.each(data, function (key, value) {
                                $('<option>').val(value.key).text(value.value).appendTo(selprovider);
                            });
                            $('select#sprovider').parent().show();
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            $(".message").html(errorThrown);
                        }

                    });
                }
            });//end change
            $(".no").click(function () {
                $("#notificationaddmodal").foundation('reveal', 'close');
            });
            $('.yes').click(function () {
                if ($.isFunction(callback)) {
                    callback.apply();
                }

                //     modal.close(); // or $.modal.close();
            });


        });

    }


    function notificationadd(message, callback) {
        $('#notificationaddform').modal({
            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
            position: ["20%",],
            overlayId: 'simpledialog-overlay',
            minHeight: '400px',
            minWidth: '500px',
            containerId: 'simpledialog-container',
            onOpen: function (dialog) {
                dialog.overlay.fadeIn('fast', function () {
                    dialog.container.slideDown('fast', function () {
                        dialog.data.fadeIn('fast');
                    });
                });
            },
            onShow: function (dialog) {
                $('select#sfederation').parent().hide();
                $('select#sprovider').parent().hide();
                $('select#type').change(function () {
                    $('select#sfederation').parent().hide();
                    $('select#sprovider').parent().hide();

                    var optionSelected = $(this).find("option:selected");
                    var valueSelected = optionSelected.val();
                    var textSelected = optionSelected.text();
                    var selfed = $('#sfederation');
                    var selprovider = $('#sprovider');
                    selfed.find('option').remove();
                    selprovider.find('option').remove();
                    if (valueSelected === "joinfedreq" || valueSelected === "fedmemberschanged") {
                        $.ajax({
                            type: "GET",
                            url: baseurl + 'ajax/getfeds',
                            cache: true,
                            success: function (json) {
                                $.each(json, function (key, value) {
                                    $('<option>').val(value.id).text(value.name).appendTo(selfed);
                                });
                                $('select#sfederation').parent().show();

                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                $(".message").html(errorThrown);
                            }

                        });
                    }
                    else if (valueSelected === "requeststoproviders") {
                        $.ajax({
                            type: "GET",
                            url: baseurl + 'ajax/getproviders',
                            cache: false,
                            //datatype: "json",
                            success: function (json) {
                                var data = $.parseJSON(json);
                                $.each(data, function (key, value) {
                                    $('<option>').val(value.key).text(value.value).appendTo(selprovider);
                                });
                                $('select#sprovider').parent().show();

                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                $(".message").html(errorThrown);
                            }

                        });

                    }
                }); // end change  function
                var modal = this;
                $('.message', dialog.data[0]).append(message);
                $('.yes', dialog.data[0]).click(function () {
                    if ($.isFunction(callback)) {
                        callback.apply();
                    }

                    //     modal.close(); // or $.modal.close();
                });
            }

        });
    }


    function sconfirm(message, callback) {
        $('#sconfirm').modal({
            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
            position: ["20%",],
            minHeight: '300px',
            minWidth: '300px',
            overlayId: 'simpledialog-overlay',
            containerId: 'simpledialog-container',
            onShow: function (dialog) {
                var modal = this;

                $('.message', dialog.data[0]).append('<br />' + message);

                // if the user clicks "yes"
                $('.yes', dialog.data[0]).click(function () {
                    // call the callback
                    if ($.isFunction(callback)) {
                        callback.apply();
                    }
                    // close the dialog
                    modal.close(); // or $.modal.close();
                });
            }
        });
    }


});

function go_to_private_page() {
    window.location.reload();
}


$(".submit").click(function () {
    return false;
});

$('#joinfed').on('change', '#fedid', function (e) {
    $("div.validaronotice").hide();
    $("ul.validatorbuttons").replaceWith('<ul class="button-group validatorbuttons"></ul>');
    var csrfname = $("[name='csrfname']").val();
    var csrfhash = $("[name='csrfhash']").val();
    if (csrfname === undefined) {
        csrfname = '';
    }
    if (csrfhash === undefined) {
        csrfhash = '';
    }
    var soption = $(this).find("option:selected").val();
    var sval = $(this).find("option:selected").text();
    var jsurl = $('div#retrfvalidatorjson').text();
    var postdata = {};
    postdata[csrfname] = csrfhash;
    postdata.fedid = soption;
    if (soption !== 0) {
        $.ajax({
            type: "POST",
            url: jsurl,
            timeout: 2500,
            cache: true,
            data: postdata,
            dataType: "json",
            success: function (data) {
                $('#spinner').hide();
                if (data) {
                    var validatorButtons = $("ul.validatorbuttons");
                    $.each(data, function (i, v) {
                        validatorButtons.append('<li><button  value="' + jsurl + '/' + v.fedid + '/' + v.id + '" class="small">' + v.name + '</button></li>');
                    });
                    $("div.validaronotice").show();
                }
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                $('#fvform').hide();
                $('#fvresult').hide();
            }
        }).done(function () {
            BINIT.initFvalidators();
        });
    }
});


$("#showhelps").click(function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    var param = "n";

    if ($("#showhelps").hasClass('helpactive')) {
        param = "n";
    }
    else {
        param = "y";
    }

    $.ajax({
        type: 'GET',
        url: url + '/' + param,
        success: function () {
            $("#showhelps").toggleClass('helpinactive').toggleClass('helpactive').toggleClass('active');
            $(".dhelp").toggle();

        }
    });
});

$("div.section").parent().addClass("section");


$("#notificationupdateform").on('submit', function (e) {

    e.preventDefault();
    var serializedData = $(this).serializeArray();
    var posturl = $(this).attr('action');
    var notid = parseInt($("input[name=noteid]").val());

    var buttonwithval = $('button[type="button"][value="' + notid + '"]');

    var ctr = $(buttonwithval).closest("tr");
    var subsriptionstatus = ctr.find('div.subscrstatus:first');
    $.ajax({
        type: "POST",
        url: posturl,
        data: serializedData,
        dataType: "json",
        success: function (data) {

            if (data) {


                var foundrecord = false;

                $.each(data, function (i, v) {

                    if (foundrecord === false && parseInt(v.id) === notid) {

                        foundrecord = true;

                        subsriptionstatus.text(v.langstatus);
                    }
                    if (foundrecord === false) {
                        ctr.hide();
                    }

                });

            }

            $('#notificationupdatemodal').foundation('reveal', 'close');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            window.alert('Error occured: ' + errorThrown);
        }


    });

});

$(document).on('submit', 'div#loginform form', function (e) {
    e.preventDefault();
    var loginform = $("#loginform");
    var submitbutton = $(loginform).find(":submit").first();
    var secondfactorrow = $(loginform).find("div.secondfactorrow").first();
    var link = $("div#loginform form").attr('action');
    var str = $(this).serializeArray();
    var browsertime = new Date();
    var browsertimezone = -browsertime.getTimezoneOffset();
    str.push({name: 'browsertimeoffset', value: '' + browsertimezone + ''});

    $.ajax({
        type: "POST",
        cache: false,
        timeout: 3500,
        url: link, // Send the login info to this page
        data: str,
        beforeSend: function () {
            $("#loginresponse").html("").hide();

        },
        success: function (data) {
            if (data) {
                if (data.success === true && data.result === 'OK') {
                    $('#loginform').foundation('reveal', 'close');
                    setTimeout(function () {
                        go_to_private_page();
                    }, 1000);

                }
                else if (data.result === 'secondfactor') {
                    $('#password').val('');
                    secondfactorrow.empty();
                    secondfactorrow.append(data.html).show();
                    submitbutton.prop('disabled', true);

                }
            }
            else {
                $("#loginresponse").html(data).show();

            }

        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("#loginresponse").html(jqXHR.responseText).show();

        }
    });
    return false;

});
$("button.advancedmode").click(function () {
    var metadata = $("textarea#metadatabody").val();
    if (metadata.length === 0) {
        window.alert("You did not inserted any metadata. You will have to fill in all the individual information manually.");
    }
    var thisB = $(this);
    var postUrl = thisB.val();
    var csrfname = $("[name='csrfname']").val();
    var csrfhash = $("[name='csrfhash']").val();
    $(this).closest("form").attr("action", postUrl);

});


// get list providers with dynamic list columns: in progress
$(".afilter").click(function () {
    var url = $(this).attr("href");

    var filter;
    if ($(this).hasClass('filterext')) {
        filter = 1;
    }
    else if ($(this).hasClass('filterlocal')) {
        filter = 2;
    }
    else {
        filter = 0;
    }
    $('a.initiated').removeClass('initiatied');
    $.ajax({
        type: "GET",
        url: url,
        timeout: 9500,
        cache: true,
        dataType: "json",
        success: function (result) {
            $('#spinner').hide();
            if (filter === 1) {
                $('dd.filterext').addClass('active');
            }
            else if (filter === 2) {
                $('dd.filterlocal').addClass('active');
            }
            else {
                $('dd.filterall').addClass('active');
            }

            if (result) {
                var table = $('<table/>');
                table.attr('id', 'details');
                table.addClass('filterlist');
                var thead = $('<thead/>');
                table.append(thead);
                var theadtr = $('<tr/>');
                thead.append(theadtr);

                var Columns = [];
                var tmpcolumns = result.columns;
                var colstatus;
                var counter = 0;
                $.each(tmpcolumns, function (i, v) {
                    colstatus = v.status;
                    if (colstatus) {
                        var nar = [];
                        $.each(v.cols, function (l, n) {
                            nar.push(n);
                        });
                        Columns.push(nar);
                        theadtr.append('<th>' + v.colname + '</th>');
                    }
                });
                var tbody = $('<tbody/>');
                table.append(tbody);
                var data = result.data;
                var startTime = new Date();
                var tbodyToInsert = [];
                var a = 0;
                $.each(data, function (j, w) {
                    if ((w.plocal === 1 && (filter === 2 || filter === 0)) || (w.plocal === 0 && filter < 2)) {
                        tbodyToInsert[a++] = '<tr>';
                        $.each(Columns, function (p, z) {
                            var cell = '';
                            $.each(z, function (r, s) {
                                if (w[s] !== null) {
                                    if (s === 'pname') {
                                        cell = cell + '<a href="' + result.baseurl + 'providers/detail/show/' + w.pid + '">' + w[s] + '</a><br />';

                                    }
                                    else if (s === 'phelpurl') {
                                        cell = cell + '<a href="' + w.phelpurl + '">' + w.phelpurl + '</a>';
                                    }
                                    else if (s === 'plocked' || s === 'pactive' || s === 'plocal' || s === 'pstatic' || s === 'pvisible' || s === 'pavailable') {
                                        if (result['statedefs'][s][w[s]] !== undefined) {
                                            cell = cell + ' <span class="lbl lbl-' + s + '-' + w[s] + '">' + result['statedefs'][s][w[s]] + '</span>';
                                        }
                                    }
                                    else {
                                        cell = cell + '  ' + w[s];
                                    }
                                }
                            });
                            tbodyToInsert[a++] = '<td>' + cell + '</td>';

                        });
                        counter++;
                        tbodyToInsert[a++] = '</tr>';

                    } //end filter condtion
                });
                tbody.append(tbodyToInsert.join(''));
                var endTime = new Date();
                var durationTime = endTime - startTime;
                console.log('Providerlist table gen time: ' + durationTime);
                var prefix = $('div.subtitleprefix').text();
                $('div.subtitle').empty().append(prefix + ': ' + counter);
                $('div#providerslistresult').append(table);
                if (counter > 1) {
                    table.tablesorter({sortList: [[0, 0]]});
                    $("#filter").keyup(function () {
                        $.uiTableFilter(table, this.value);
                    });
                    $('#filter-form').submit(function () {
                        table.find("tbody > tr:visible > td:eq(1)").mousedown();
                        return false;
                    }).focus();
                }
            }
        },
        beforeSend: function () {
            $('dd.afilter').removeClass('active');
            $('div#providerslistresult').empty();
            $('div.alert-box').empty().hide();
            $('#spinner').show();
        },
        error: function (xhr, status, error) {
            $('#spinner').hide();
            $('div.subtitle').empty();
            $('div.alert-box').append(error).show();

        }
    });
    return false;
});

$('a.initiated').trigger('click');


$('button[name="mrolebtn"]').click(function (e) {
    var link = $(this).attr('value');
    $.ajax({
        type: "GET",
        url: link,
        timeout: 2500,
        cache: false,
        dataType: "json",
        success: function (json) {
            var rarray = [];
            $.each(json, function (ig, vg) {
                rarray.push(vg);

            });
            $("input[name='checkrole[]']").each(function () {
                var val = $(this).attr('value');
                var cc = $(this).attr('checked');


                if ($.inArray(val, rarray) === -1) {

                    $(this).prop("checked", false);

                }
                else {
                    $(this).prop("checked", true);
                }
            });

        }
    });
});

$('button[name="updaterole"]').click(function (e) {
    e.preventDefault();
    var form = $(this).parents('form:first');
    var link = form.attr('action');
    $.ajax({
        type: 'POST',
        url: link,
        cache: false,
        data: form.serializeArray(),
        dataType: "json",
        success: function (json) {
            $('#mroles').foundation('reveal', 'close');
            if (json) {
                var txtToReplace = '';
                $.each(json, function (i, v) {
                    txtToReplace = txtToReplace + v + ',';
                });
                $('span#currentroles').empty().append(txtToReplace.substring(0, txtToReplace.length - 1));
            }
        }

    });

});
$('button[name="update2f"]').click(function (e) {
    e.preventDefault();
    var form = $(this).parents('form:first');
    var alertDiv = form.find('div.alert-box');
    var link = form.attr('action');
    $.ajax({
        type: 'POST',
        url: link,
        cache: false,
        data: form.serializeArray(),
        dataType: "json",
        success: function (json) {

            alertDiv.hide();

            if (json) {
                var txtToReplace = '';
                $.each(json, function (i, v) {
                    txtToReplace = txtToReplace + v + ',';
                });
                $('span#val2f').empty().append(txtToReplace.substring(0, txtToReplace.length - 1));
            }
            $('#m2f').foundation('reveal', 'close');

        },
        error: function (jqXHR, textStatus, errorThrown) {
            alertDiv.html(errorThrown).show();
        }


    });

});

var checkRegpol;

$('input[type="radio"].withuncheck').hover(function () {
    checkRegpol = $(this).is(':checked');
});

$('input[type="radio"].withuncheck').click(function () {
    checkRegpol = !checkRegpol;
    $(this).attr('checked', checkRegpol);
});


$(document).on('click', '#resetloginform', function (e) {
    e.preventDefault();
    var baseurl = $("[name='baseurl']").val();
    $.ajax({
        type: 'GET',
        url: baseurl + 'authenticate/resetloginform',
        cache: false,
        success: function (data) {
            $(".secondfactorrow").empty();
            $("#loginform").foundation('reveal', 'close');
            return false;
        }
    });
});
$(document).on('submit', "#duo_form", function (e) {
    e.preventDefault();
    var link = $(this).attr('action');
    var secondfactorrow = $('.secondfactorrow');
    $.ajax({
        type: 'POST',
        url: link,
        cache: false,
        data: $(this).serializeArray(),
        beforeSend: function () {
            $("#loginresponse").html("").hide();

        },
        success: function (data) {
            if (data) {
                if (data.success === true && data.result === 'OK') {
                    $('#loginform').foundation('reveal', 'close');

                    setTimeout(function () {
                        go_to_private_page();
                    }, 1000);

                }
                else if (data.result === 'secondfactor') {
                    secondfactorrow.append(data.html).show();

                }
            }
            else {
                $("#loginresponse").html(data).show();

            }

        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("#loginresponse").html(jqXHR.responseText).show();

        }
    });
});

$(document).ready(
    function () {
        var autoclick = $("a.autoclick");
        if (autoclick !== undefined) {
            autoclick.click();
        }
        //  $("a.autoclick")[0].click();
    }
);

$("#updateprefsmodal").on('submit', function (e) {
    e.preventDefault();
    var link = $(this).attr('data-jagger-link');
    var alertDiv = $(this).find('div.alert').first();
    var form = $(this).find('form').first();
    $.ajax({
        'url': link,
        'type': 'POST',
        'cache': false,
        'data': form.serializeArray(),
        success: function (data) {
            if (data) {
                if (data.result === 'OK') {
                    var sRecord = $(document).find('[data-jagger-record="' + data.confname + '"]').first();


                    var rowRecord = sRecord.closest('tr');
                    var type = data.type;
                    if (type === 'text') {

                        rowRecord.find('span[data-jagger-name="vtext"]').first().html(data.vtext);
                    }
                    var sStatus = rowRecord.find('span[data-jagger-name="status"]').first();
                    if (data.status) {
                        sStatus.removeClass('alert').html(data.statusstring);
                    }
                    else {
                        sStatus.addClass('alert').html(data.statusstring);
                    }

                    $(document).foundation('reveal', 'close');
                }
                else if (data.error) {
                    alertDiv.html(data.error).show();
                }
            }
            else {
                alertDiv.html('No data').show();
            }

        },
        error: function (jqXHR, textStatus, errorThrown) {
            alertDiv.html(errorThrown).show();
        }
    });
});

$(document).on('click', 'a.updateprefs', function (e) {
    e.preventDefault();
    var modal = $("#updateprefsmodal");
    var geturl = $(this).attr('href');
    if (modal === undefined || geturl === undefined) {
        return false;
    }
    var eDisplayname = modal.find('span[data-jagger-name="displayname"]').first();
    var eConfname = modal.find('input[name="confname"]').first();
    var eDescription = modal.find('div[data-jagger-name="desc"]').first();
    var eStatus = modal.find('input[name="status"]').first();
    var eText = modal.find('textarea[data-jagger-name="vtext"]').first();
    var alertDiv = modal.find('div.alert').first();
    alertDiv.html('').hide();


    if (eText === null) {
        window.alert("d");
    }
    $.ajax({
        'url': geturl,
        'type': 'GET',
        'cache': false,
        success: function (data) {
            if (data) {
                eDisplayname.html('"' + data.displayname + '"');
                eConfname.val(data.confname);
                eDescription.html('<b>' + data.displayname + ':</b> ' + data.desc);
                var type = data.type;
                if (type === 'text') {

                    eText.val(data.vtext);
                    eText.closest('div.row').show();

                }
                else {
                    eText.closest('div.row').hide();
                }

                if (data.status === true) {

                    eStatus.prop('checked', true);
                }
                else {
                    eStatus.prop('checked', false);
                }

            }
        }


    });


    modal.foundation('reveal', 'open');
    return false;

});






























