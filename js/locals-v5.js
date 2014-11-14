jQuery.fn.autoWidth = function(options)
{
    var settings = {
        limitWidth: false
    };

    if (options) {
        jQuery.extend(settings, options);
    }

    var maxWidth = 0;

    this.each(function() {
        if ($(this).width() > maxWidth) {
            if (settings.limitWidth && maxWidth >= settings.limitWidth) {
                maxWidth = settings.limitWidth;
            } else {
                maxWidth = $(this).width();
            }
        }
    });

    this.width(maxWidth);
};
jQuery.fn.toggleOption = function(show) {
    jQuery(this).toggle(show);
    if (show) {
        while (jQuery(this).parent('span.toggleOption').length)
            jQuery(this).unwrap( );
    } else {
        jQuery(this).wrap('<span class="toggleOption" style="display: none;" />');
    }
};

var BINIT = {
    initFvalidators: function() {

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
                    if (data)
                    {
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
        $("table.sortable").tablesorter();

        var baseurl = $("[name='baseurl']").val();
        if (baseurl === undefined)
        {
            baseurl = '';
        }


        $("a.bookentity").click(function () {
            var link = $(this), url = link.attr("href");

            $.ajax({
                type: "GET",
                url: url,
                timeout: 2500,
                cache: false,
                success: function (data) {
                    $("a.bookentity").show();
                    $(this).hide();
                    GINIT.initialize();
                }
            });
            return false;
        });


        $('#providerlogtab').on('toggled', function (event, tab) {
            var domElement = tab;//.get(0);
            var oko = domElement.find("[data-reveal-ajax-tab]");
            var link = oko.attr("data-reveal-ajax-tab");
            if (link !== undefined)
            {
                //$('#providerlogtab').empty();
                $.ajax({
                    cache: true,
                    type: 'GET',
                    url: link,
                    success: function (data) {
                        $('#providerlogtab').empty().append(data);
                        $(document).foundation('reflow');
                        $('.accordionButton').unbind();
                        $('#editprovider').unbind();

                        GINIT.initialize();

                    }

                });
            }



        });



        function notificationupdateOld(message, callback) {
            $('#notificationupdateform').modal({
                closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                position: ["20%", ],
                overlayId: 'simpledialog-overlay',
                minHeight: '200px',
                containerId: 'simpledialog-container',
                onShow: function (dialog) {
                    var modal = this;
                    $('.message', dialog.data[0]).append(message);
                    $('.yes', dialog.data[0]).click(function () {
                        if ($.isFunction(callback)) {
                            callback.apply();
                        }
                        modal.close(); // or $.modal.close();
                    });
                }
            });
        }
        function notificationupdate(message, callback) {
            $("#notificationupdatemodal").foundation('reveal', 'open');
            $("#notificationupdatemodal").on('opened', function () {

                alert("o");
                //var modal = this;
                $(".no").click(function () {
                    $("#notificationupdatemodal").foundation('reveal', 'close');
                });

                $('.message').append(message);
                $('.yes').click(function () {
                    if ($.isFunction(callback)) {
                        callback.apply();
                        $("#notificationupdatemodal").foundation('reveal', 'close');
                    }
                });

            });


        }

        $(".langinputrm").addClass("alert");
        $(".dhelp").click(function () {
            var curSize = parseInt($(this).css('font-size'));
            if (curSize <= 10)
            {
                $(this).css('font-size', curSize + 5).removeClass('zoomin').addClass('zoomout');
            }
            else
            {
                $(this).css('font-size', curSize - 5).removeClass('zoomout').addClass('zoomin');
            }
        });
        $("form#availablelogos input[name='filename']").click(function () {
            $(this).after($("form#availablelogos div.buttons").show());

        });
        $("button.langinputrm").click(function () {
            var lrow = $(this).closest('div').parent();
            var bval = $(this).attr('value');
            var bname = $(this).attr('name');
            lrow.find("input").each(function () {
                $(this).attr('value', '');
            });
            lrow.find("textarea").each(function () {
                $(this).val("");
            });
            $(this).parent().parent().find("option[value=" + bval + "]").each(
                    function () {
                        $(this).toggleOption(true);
                        $(this).attr('disabled', false);

                    }
            );
            //$(this).parent().remove();
            lrow.remove();
            GINIT.initialize();


        });
        $("button.rmfield").click(function () {
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
            GINIT.initialize();

        });
        $("button.contactrm").click(function () {
            var bval = $(this).attr('value');
            var bname = $(this).attr('name');
            var fieldset = $(this).closest('div.group');
            fieldset.remove();
            GINIT.initialize();
        });
        $("button.certificaterm").click(function () {
            var bval = $(this).attr('value');
            var bname = $(this).attr('name');
            var fieldset = $(this).closest('div.certgroup');
            fieldset.remove();
            GINIT.initialize();
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
                            $("div#t1").empty();
                            $("div#t1").append(data);
                            $("#assignedlogos").unbind();
                            $("#availablelogos").unbind();
                            GINIT.initialize();
                        },
                    });
                    $('#spinner').hide();
                    result.html(data).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%", ],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '200px',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }

                    });

                },
                error: function (qXHR, textStatus, errorThrown) {
                    $('#spinner').hide();
                    result.css('color', 'red');
                    result.html(jqXHR.responseText).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%", ],
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
                        position: ["20%", ],
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
                                position: ["20%", ],
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
                        position: ["20%", ],
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
                    result.html(data).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%", ],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '200px',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }
                    });
                    checkedObj.parent().remove();

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#spinner').hide();
                    result.css('color', 'red');
                    result.html(jqXHR.responseText).append('<p><input type="button" value="Close" class="simplemodal-close" /></p>').modal({
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                        position: ["20%", ],
                        overlayId: 'simpledialog-overlay',
                        minHeight: '200px',
                        containerId: 'simpledialog-container',
                        onShow: function (dialog) {
                            var modal = this;
                        }
                    });
                }
            }).done(function () {
                $("form#assignedlogos").unbind();
                $("form#availablelogos").unbind();
                $("#uploadlogo").unbind();
                GINIT.initialize();
            });

        }));
        $("button.updatenotifactionstatus").click(function () {
            var related;
            var notid = $(this).attr('value');
            var ctbl = $(this).closest("tbody");
            var ctr = $(this).closest("tr");
            var subsriptionstatus = ctr.find('div.subscrstatus:first');
            var posturl = baseurl + 'notifications/subscriber/updatestatus/' + notid;
            $("form#notificationupdateform").attr('action', posturl);
            $("form#notificationupdateform #noteid").val(notid);
            // $('#notificationupdateform').foundation('reveal', 'open'); 
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
                    alert(jqXHR.responseText);
                },
            });
            return false;
        });
        $('a#editprovider').click(function (e) {
            //alert(window.location);
            var curTabID = $('#providertabs .ui-tabs-panel[aria-hidden="false"]').prop('id');
            var url = $(this).attr('href');
            if (curTabID == "attributes" || curTabID == "attrs")
            {
                var nurl = $('a#editattributesbutton').attr('href');
                $(this).attr("href", nurl);

            }
            else
            {
                $(this).attr("href", url + "#" + curTabID);
            }

        });

        $('form#fvform').submit(function (e) {
            e.preventDefault();
            var str = $(this).serializeArray();
            var url = $("form#fvform").attr('action');
            var fvid = $(this).find("button:focus").attr('id');

            $.ajax({
                type: "POST",
                url: url,
                cache: false,
                data: str,
                timeout: 120000,
                success: function (json) {
                    $('#spinner').hide();
                    var data = $.parseJSON(json);
                    if (!data)
                    {
                        alert('no data received from upstream server');
                    }
                    else
                    {
                        if (data.returncode)
                        {
                            $("span#fvreturncode").append(data.returncode);
                            $("div#fvresult").show();
                        }
                        if (data.returncode == "success")
                        {
                            document.getElementById(fvid).style.backgroundColor = "#00aa00";
                            document.getElementById(fvid).style.borderColor = "#00aa00";
                            document.getElementById(fvid).disabled = true;
                        } else if (data.returncode == "error")
                        {
                            document.getElementById(fvid).style.backgroundColor = "#aa0000";
                            document.getElementById(fvid).style.borderColor = "#aa0000";
                        }
                        if (data.message)
                        {
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
                    if (t === 'timeout')
                    {
                        alert('got timeout from validation server');
                    }
                    else
                    {
                        alert("unknown problem with receiving data");
                    }
                }
            });

            //return false; 
        });

        $("a.fmembers").click(function () {

            var link = $(this), url = link.attr("href");
            var row = $(this).parent().parent();
            if ($(row).hasClass('opened') === true)
            {
                $(row).next().remove();
                $(row).removeClass('opened').removeClass('highlight');

            }
            else
            {
                var value = $('<ul/>');
                $.ajax({
                    url: url,
                    timeout: 2500,
                    cache: true,
                    success: function (json) {
                        $('#spinner').hide();
                        var data = $.parseJSON(json);
                        var stitle;
                        var nlist;
                        var div_data;
                        $(row).addClass('opened').addClass('highlight');
                        if (!data)
                        {
                            alert('no data');
                        }
                        else
                        {
                            if (!data.idp && !data.sp && !data.both)
                            {
                                div_data = '<div>' + data.definitions.nomembers + '</div>';
                                value.append(div_data);
                            }
                            else
                            {
                                var preurl = data.definitions.preurl;
                                if (data.idp)
                                {
                                    stitle = $('<div>' + data.definitions.idps + '</div>');
                                    nlist = $('<ol/>');
                                    $.each(data.idp, function (i, v) {
                                        div_data = '<li class="homeorg"><a href="' + preurl + v.pid + '">' + v.pname + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                                if (data.sp)
                                {
                                    stitle = $('<div>' + data.definitions.sps + '</div>');
                                    nlist = $('<ol/>');
                                    $.each(data.sp, function (i, v) {
                                        div_data = '<li class="resource"><a href="' + preurl + v.pid + '">' + v.pname + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                                if (data.both)
                                {
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
                        alert('problem with loading data');
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
                timeout: 2500,
                cache: true,
                success: function (json) {
                    $('#spinner').hide();
                    var data = $.parseJSON(json);
                    if (!data)
                    {
                        alert('no data');
                    }
                    else
                    {
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
                    alert('problem with loading data');
                }

            }).done(function () {
                var nextrow = value.html();
                //$(nextrow).insertAfter(row);
                $("div#membership").replaceWith(nextrow);

            });
            return false;
        });
        $('.accordionButton').addClass('off');
        $('.accordionButton1').addClass('on');

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

    $('#idpmatrix tr th').each(function (i) {
        var tds = $(this).parents('table').find('tr td:nth-child(' + (i + 1) + ')');
        if (tds.length == tds.filter(':empty').length) {
            $(this).hide();
            tds.hide();
        }
    });


    var helpactivity = $("#showhelps");
    if (helpactivity.length)
    {
        if (helpactivity.hasClass('helpactive'))
        {
            $(".dhelp").show();
        }
        else
        {
            $(".dhelp").hide();
        }
    }


    var fedloginurl = $('a#fedlogin').attr('href');
    var browsertime = new Date();
    var browsertimezone = -browsertime.getTimezoneOffset();
    $('a#fedlogin').attr('href', '' + fedloginurl + '/' + browsertimezone + '');

    var bubbletheme = $("a#jquerybubblepopupthemes").val();
    $('.bubblepopup').CreateBubblePopup({
        position: 'top',
        align: 'center',
        innerHtml: 'click help',
        innerHtmlStyle: {
            color: '#FFFFFF',
            'text-align': 'center'
        },
        themeName: 'all-black',
        themePath: bubbletheme

    });
    $('.bubblepopup').mouseover(function () {
        var some = $(this).val();
        $(this).ShowBubblePopup({
            closingDelay: 1000,
            position: 'top',
            align: 'center',
            innerHtml: some,
            innerHtmlStyle: {
                color: '#000000',
                'font-size': '110%',
                'text-align': 'left'
            },
            themeName: 'all-yellow',
            themePath: bubbletheme
        }, false).FreezeBubblePopup();

    });
    if ($('#fedcategories dd.active').length) {
        var url = $('dd.active').find('a').first().attr('href');
        var value = $('table.fedistpercat');
        var data;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            value: $('table.fedistpercat'),
            success: function (json) {
                $('#spinner').hide();
                data = $.parseJSON(json);
                if (!data)
                {
                    alert('no data in federation category');
                }
                else
                {
                    $("table.fedistpercat tbody tr").remove();
                    $.each(data, function (i, v) {
                        var tr_data = '<tr><td>' + v.name + '</td><td>' + v.urn + '</td><td>' + v.labels + '</td><td>' + v.desc + '</td><td>' + v.members + '</td></tr>';
                        value.append(tr_data);
                    });
                }
                GINIT.initialize();
            },
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
            value: value,
            success: function (json) {
                $('#spinner').hide();
                data = $.parseJSON(json);
                if (!data)
                {
                    alert('no data in federation category');
                }
                else
                {
                    $("table.fedistpercat tbody tr").remove();
                    $.each(data, function (i, v) {
                        var tr_data = '<tr><td>' + v.name + '</td><td>' + v.urn + '</td><td>' + v.labels + '</td><td>' + v.desc + '</td><td>' + v.members + '</td></tr>';
                        value.append(tr_data);
                    });
                }
                GINIT.initialize();
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                alert('problem with loading data');
            }
        }).done(function () {
            var nextrow = value.html();
            //$("table.fedistpercat").append(nextrow);
        });
        return false;
    });

});
$("#testcombo").autocomplete();
$(function () {


    /*************/


    $.widget("ui.combobox", {
        _create: function () {
            var self = this,
                    select = this.element.hide(),
                    selected = select.children(":selected"),
                    value = selected.val() ? selected.text() : "";
            var input = this.input = $("<input>")
                    .insertAfter(select)
                    .val(value)
                    .autocomplete({
                        delay: 0,
                        minLength: 0,
                        source: function (request, response) {
                            var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
                            response(select.children("option").map(function () {
                                var text = $(this).text();
                                if (this.value && (!request.term || matcher.test(text)))
                                    return {
                                        label: text.replace(
                                                new RegExp(
                                                        "(?![^&;]+;)(?!<[^<>]*)(" +
                                                        $.ui.autocomplete.escapeRegex(request.term) +
                                                        ")(?![^<>]*>)(?![^&;]+;)", "gi"
                                                        ), "<strong>$1</strong>"),
                                        value: text,
                                        option: this
                                    };
                            }));
                        },
                        select: function (event, ui) {
                            ui.item.option.selected = true;
                            self._trigger("selected", event, {
                                item: ui.item.option
                            });
                        },
                        change: function (event, ui) {
                            if (!ui.item) {
                                var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex($(this).val()) + "$", "i"),
                                        valid = false;
                                select.children("option").each(function () {
                                    if ($(this).text().match(matcher)) {
                                        this.selected = valid = true;
                                        return false;
                                    }
                                });
                                if (!valid) {
                                    // remove invalid value, as it didn't match anything
                                    $(this).val("");
                                    select.val("");
                                    input.data("autocomplete").term = "";
                                    return false;
                                }
                            }
                        }
                    })
                    .addClass("ui-widget ui-widget-content ui-corner-left");

            input.data("uiAutocomplete")._renderItem = function (ul, item) {
                if (!_.include(self.idArr, item.id)) {
                    return $("<li></li>")
                            .data("item.autocomplete", item)
                            .append("<a>" + item.label + "</a>")
                            .appendTo(ul);
                }
            };

            this.button = $("<button type='button'>&nbsp;</button>")
                    .attr("tabIndex", -1)
                    .attr("title", "Show All Items")
                    .insertAfter(input)
                    .button({
                        icons: {
                            primary: "ui-icon-triangle-1-s"
                        },
                        text: false
                    })
                    .removeClass("ui-corner-all")
                    .addClass("ui-corner-right ui-button-icon")
                    .click(function () {
                        // close if already visible
                        if (input.autocomplete("widget").is(":visible")) {
                            input.autocomplete("close");
                            return;
                        }

                        // work around a bug (likely same cause as #5265)
                        $(this).blur();

                        // pass empty string as value to search for, displaying all results
                        input.autocomplete("search", "");
                        input.focus();
                    });
        },
        destroy: function () {
            this.input.remove();
            this.button.remove();
            this.element.show();
            $.Widget.prototype.destroy.call(this);
        }
    });

    $("#combobox").combobox();
    $("#toggle").click(function () {
        $("#combobox").toggle();
    });
    /****************/

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
    if (baseurl === undefined)
    {
        baseurl = '';
    }
    var refreshId;
    $("#responsecontainer").load(baseurl + "reports/awaiting/ajaxrefresh");
    refreshId = setInterval(function () {
        $("#responsecontainer").load(baseurl + 'reports/awaiting/ajaxrefresh');
    }, 172000);
    $("#dashresponsecontainer").load(baseurl + "reports/awaiting/dashajaxrefresh");
    refreshId = setInterval(function () {
        $("#dashresponsecontainer").load(baseurl + 'reports/awaiting/dashajaxrefresh');
    }, 172000);


    $.ajaxSetup({
        cache: false
    });
    $("#qcounter").load(baseurl + 'reports/awaiting/counterqueue');
    refreshId = setInterval(function () {
        $("#qcounter").load(baseurl + 'reports/awaiting/counterqueue');
    }, 86000);


    $('#languageset select').on('change', function () {
        var link = $("div#languageset form").attr('action');
        var url = link + this.value;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false
        }).done(function () {
            $('#languageset').foundation('reveal', 'close');
            setTimeout('go_to_private_page()', 1000);
        });
        return false;

    });

    $('select.nuseraccesstype').on('change', function () {
        var access = $(this).find("option:selected");
        var accessselected = access.val();
        if (accessselected === 'fed')
        {
            $('div.passwordrow').hide();
        }
        else
        {
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
                alert(data);
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
                if (!data)
                {
                    alert('no data');
                }
                else
                {
                    alert(data.status);
                }

            }
        });
        return false;
    });
    $("a.lateststat").click(function () {
        var link = $(this), url = link.attr("href");
        var value = $('<div id="#statisticdiag">');
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            success: function (json)
            {
                $('#spinner').hide();
                var data = $.parseJSON(json);
                if (!data)
                {
                    alert('no data');
                }
                else
                {
                    $("div#statisticdiag").replaceWith('<div id="statisticdiag"></div>');
                    $.each(data, function (i, v) {

                        i = new Image();
                        i.src = v.url;
                        $('#statisticdiag').append('<div style="text-align:center; font-weight: bold; width: 90%;">' + v.title + '</div>').append('<div style="font-weight: bolder; width: 90%; text-align: right;">' + v.subtitle + '</div>').append(i);

                    });
                }
                i = null;
            },
            beforeSend: function () {
                $('#spinner').show();
            },
            error: function () {
                $('#spinner').hide();
                alert('problem with loading data');
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

    $("a.delbookentity").click(function () {
        var link = $(this), url = link.attr("href");

        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).parent().remove()
        });
        return false;
    });
    $("a.delbookfed").click(function () {
        var link = $(this), url = link.attr("href");

        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).parent().remove()
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
                if (!data)
                {
                    alert('no data');
                }
                else
                {
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
                alert('problem with loading data');
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
    $("#details").tablesorter({sortList: [[0, 0], [1, 0]], widgets: ['zebra']});
    $(".userlist#details").tablesorter({sortList: [[3, 1], [0, 0]], widgets: ['zebra']});
    $("#options").tablesorter({sortList: [[0, 0]], headers: {3: {sorter: false}, 4: {sorter: false}}});

    $("#formtabs").tabs({
        cache: false,
        activate: function (event, ui) {
            GINIT.initialize();
        },
    });

    $(".mytabs").tabs({
        cache: false,
        activate: function (event, ui) {
            GINIT.initialize();
        },
    });


    $("#providertabs").tabs({
        cache: true,
        //   data-theme: "none",
        load: function (event, ui) {
            $('.accordionButton').unbind();
            $('#editprovider').unbind();

            $(".ui-widget").removeClass("ui-widget");
            GINIT.initialize();
        },
    });
    $("#fedtabs").tabs({
        cache: true,
        load: function (event, ui) {
            $('.accordionButton').unbind();
            GINIT.initialize();
        }

    });
    $("#arptabs").tabs({
        cache: true,
        load: function (event, ui) {
            $('.accordionButton').unbind();
            $('.tablesorter').unbind();
            GINIT.initialize();
        }

    });
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
    if ($(this).is(":checked"))
    {
        $("#usepredefined").not(this).removeAttr("checked");
        $("fieldset#stadefext").hide();
    }
    else
    {
        $("fieldset#stadefext").show();
        $("#usepredefined").not(this).addAttr("checked");

    }

});
$(".acsdefault").click(function () {
    if ($(this).is(":checked"))
    {
        $(".acsdefault").not(this).removeAttr("checked");
    }
});
$("#nacsbtn").click(function () {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<div class=\"srvgroup\"><div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for="f[srv][AssertionConsumerService][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class=\"small-5 columns inline\"><select name="f[srv][AssertionConsumerService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:PAOS">urn:oasis:names:tc:SAML:2.0:bindings:PAOS</option> <option value="urn:oasis:names:tc:SAML:2.0:profiles:browser-post">urn:oasis:names:tc:SAML:2.0:profiles:browser-post</option> <option value="urn:oasis:names:tc:SAML:1.0:profiles:browser-post">urn:oasis:names:tc:SAML:1.0:profiles:browser-post</option> <option value="urn:oasis:names:tc:SAML:1.0:profiles:artifact-01">urn:oasis:names:tc:SAML:1.0:profiles:artifact-01</option> </select></div> <div class="small-4 columns"><div class="small-6 columns"><input type="text" name="f[srv][AssertionConsumerService][n_' + rname + '][order]" value="" id="f[srv][AssertionConsumerService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div><div class="small-6 columns"><label for="f[srv][AssertionConsumerService][n_' + rname + '][default]">Is default</label><input type="radio" name="f[srv][AssertionConsumerService][n_' + rname + '][default]" value="1" id="f[srv][AssertionConsumerService][n_' + rname + '][default]" class="acsdefault"/></div></div> </div>          <div class="small-12 columns"><div class="small-3 columns"><label for="f[srv][AssertionConsumerService][n_' + rname + '][url]" class=\"right inline\">URL</label></div><div class=\"small-8 large-7 columns inline\"><input name="f[srv][AssertionConsumerService][n_' + rname + '][url]" id="f[srv][AssertionConsumerService][n_' + rname + '][url]" type="text"></div><div class=\"small-3 large-2 columns\"><button class="inline left button tiny alert rmfield"  name="rmfield" type="button">Remove</button></div></div></div>';
    $(this).parent().before(newelement);
    GINIT.initialize();


});
$("#nspartifactbtn").click(function () {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<div class="srvgroup"><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][SPArtifactResolutionService][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class=\"small-8 large-7 columns inline\"><select name="f[srv][SPArtifactResolutionService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:SOAP</option> <option value="urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding">urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding</option></select> </div> <div class=\"small-1  columns left\"><input type="text" name="f[srv][SPArtifactResolutionService][n_' + rname + '][order]" value="" id="f[srv][SPArtifactResolutionService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div></div>           <div class="small-12 columns"><div class="small-3 columns"><label for="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" class="right inline">URL</label></div><div class=\"small-6 large-7 columns inline\"><input name="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" id="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" type="text"> </div><div class=\"small-3 large-2 columns\"><button class="inline left button tiny alert rmfield"  name="rmfield" type="button">Remove</button></div></div>';
    $(this).parent().before(newelement);
    GINIT.initialize();

});

$("#nattrreqbtn").click(function (ev) {
    ev.preventDefault();
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    var attrselect = $('select[name="nattrreq"]');
    var attrname = attrselect.find(":selected").text();
    var attrid = attrselect.find(":selected").val();

    var newelement = '<fieldset><legend>' + attrname + '</legend><div class="small-12 columns"><div class="medium-3 columns medium-text-right"><select name="f[reqattr][' + rname + '][status]"><option value="required">required</option><option value="desired">desired</option></select><input type="hidden" name="f[reqattr][' + rname + '][attrname]" value="' + attrname + '"><input type="hidden" name="f[reqattr][' + rname + '][attrid]" value="' + attrid + '"></div><div class="medium-6 collumns end"><textarea name="f[reqattr][' + rname + '][reason]"></textarea></div></div></fieldset>';
    $(this).parent().parent().before(newelement);

    GINIT.initialize();


});
$("#nidpartifactbtn").click(function () {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<div class="srvgroup"><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][IDPArtifactResolutionService][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class="small-6 large-7 columns inline"><select name="f[srv][IDPArtifactResolutionService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:SOAP</option> <option value="urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding">urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding</option></select></div> <div class="small-2 large-1 columns end"><input type="text" name="f[srv][IDPArtifactResolutionService][n_' + rname + '][order]" value="" id="f[srv][IDPArtifactResolutionService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div></div> <div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" class=\"right inline\">URL</label></div><div class="small-6 large-7 columns inline"><input name="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" id="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" type="text"></div><div class="small-3 large-2 columns"><button class="inline left button tiny alert rmfield" value="" name="rmfield" type="button">Remove</button></div></div>';
    $(this).parent().before(newelement);
    GINIT.initialize();
});
$("#ndrbtn").click(function () {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<div class="srvgroup"><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][DiscoveryResponse][n_' + rname + '][bind]" class=\"right inline\">Binding Name</label></div><div class="small-6 large-7 columns"><select name="f[srv][DiscoveryResponse][n_' + rname + '][bind]"><option value="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol">urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol</option></select> </div><div class="small-1 columns end"><input type="text" name="f[srv][DiscoveryResponse][n_' + rname + '][order]" value="" id="f[srv][DiscoveryResponse][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></div></div><div class="small-12 columns"><div class=\"small-3 columns\"><label for="f[srv][DiscoveryResponse][n_' + rname + '][url]" class="right inline">URL</label></div><div class="small-6 large-7 columns"><input name="f[srv][DiscoveryResponse][n_' + rname + '][url]" id="f[srv][DiscoveryResponse][n_' + rname + '][url]" type="text"></div><div class="small-1 columns end"><button class="rmfield button alert tiny left" name="rmfield">Remove</button></div></div></div>';
    $(this).parent().before(newelement);
    GINIT.initialize();

});
$("#nribtn").click(function () {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<div class="small-12 columns srvgroup"><div class="small-3 columns"><label for="f[srv][RequestInitiator][n_' + rname + '][url]" class="right inline">URL</label></div><div class="small-6 large-7 columns"><input name="f[srv][RequestInitiator][n_' + rname + '][url]" id="f[srv][RequestInitiator][n_' + rname + '][url]" type="text"></div><div class="small-3 large-2 columns"><button type="button" class="inline left button tiny alert rmfield" name="rmfield" value="">remove</button></div></div>';
    $(this).parent().before(newelement);
    GINIT.initialize();


});
$("#nidpssocert").click(function () {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    rname = "newx" + rname;
    var newelement = '<div class="certgroup small-12 columns"><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][type]" class="inline right">Certificate type</label></div><div class="small-6 large-7 columns"><select name="f[crt][idpsso][' + rname + '][type]"> <option value="x509">x509</option> </select></div><div class="small-3 large-2 columns"></div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][usage]" class="inline right">Certificate use</label></div><div class="small-6 large-7 columns"><select name="f[crt][idpsso][' + rname + '][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][keyname]" class="inline right">KeyName</label></div><div class="small-6 large-7 columns"><input type="text" name="f[crt][idpsso][' + rname + '][keyname]" value="" id="f[crt][idpsso][' + rname + '][keyname]" class=""  /> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][idpsso][' + rname + '][certdata]" class="inline right">Certificate</label></div><div class="small-6 large-7 columns"><textarea name="f[crt][idpsso][' + rname + '][certdata]" cols="65" rows="20" id="f[crt][idpsso][' + rname + '][certdata]" class="certdata notice" ></textarea></div><div class="small-3 large-2 columns"></div></div>';
    $(this).parent().before(newelement);

});
$("#naacert").click(function () {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    rname = "newx" + rname;
    var newelement = '<div class="certgroup small-12 columns"><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][type]" class="inline right">Certificate type</label></div><div class="small-6 large-7 columns"><select name="f[crt][aa][' + rname + '][type]"> <option value="x509">x509</option> </select> </div><div class="small-3 large-2 columns"></div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][usage]" class="inline right">Usage</label></div><div class="small-6 large-7 columns"><select name="f[crt][aa][' + rname + '][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][keyname]" class="inline right">KeyName</label></div><div class="small-6 large-7 columns"><input type="text" name="f[crt][aa][' + rname + '][keyname]" value="" id="f[crt][aa][' + rname + '][keyname]" class=""  /> </div><div class="small-3 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][aa][' + rname + '][certdata]" class="inline right">Certificate</label></div><div class="small-6 large-7 columns"><textarea name="f[crt][aa][' + rname + '][certdata]" cols="65" rows="20" id="f[crt][aa][' + rname + '][certdata]" class="certdata notice" ></textarea> </div><div class="small-3 large-2 columns"></div></div> ';
    $(this).parent().before(newelement);

});
$("#nspssocert").click(function () {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    rname = "newx" + rname;
    var newelement = '<div class="certgroup small-12 columns"><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][type]" class="right inline">Certificate type</label></div><div class="small-8 large-7 columns"><select name="f[crt][spsso][' + rname + '][type]"><option value="x509">x509</option></select> </div><div class="small-1 large-2 columns end"></div></div><div class="small-12  columns"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][usage]" class="right inline">Certificate use</label></div><div class="small-8 large-7 columns"><select name="f[crt][spsso][' + rname + '][usage]"><option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select></div><div class="small-1 large-2 columns end"></div></div><div class="small-12 columns hidden"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][keyname]" class="right inline">KeyName</label></div><div class="small-8 large-7 columns"><input type="text" name="f[crt][spsso][' + rname + '][keyname]" value="" id="f[crt][spsso][' + rname + '][keyname]" class=""  /></div><div class="small-1 large-2 columns end"></div> </div><div class="small-12 columns"><div class="small-3 columns"><label for="f[crt][spsso][' + rname + '][certdata]" class="right inline">Certificate</label></div><div class="small-8 large-7 columns"><textarea name="f[crt][spsso][' + rname + '][certdata]" cols="65" rows="20" id="f[crt][spsso][' + rname + '][certdata]" class="certdata" ></textarea></div><div class="small-1 large-2 columns end"></div></div><div class="small-12 columns"><div class="small-3 columns">&nbsp;</div><div class="small-6 large-7 columns"><button class="certificaterm button alert tiny right" value="' + rname + '" name="certificate" type="button">Remove certificate</button></div><div class="small-3 large-2 columns"></div></div></div>';
    $(this).parent().before(newelement);
    GINIT.initialize();

});
$("a.pCookieAccept").click(function () {
    var link = $(this), url = link.attr("href");

    $.ajax({
        url: url,
        timeout: 2500,
        cache: false
    });
    $('#cookiesinfo').hide();

    return false;
});
$("[id='f[entityid]']").change(function () {
    if ($(this).hasClass("alertonchange"))
    {
        var entalert = $("div#entitychangealert").text();
        alert(entalert);
    }
});

// When DOM is ready
$(document).ready(function () {
    var baseurl = $("[name='baseurl']").val();
    if (baseurl === undefined)
    {
        baseurl = '';
    }
// Preload Images
    img1 = new Image(16, 16);
    img1.src = baseurl + 'images/spinner.gif';

    img2 = new Image(220, 19);
    img2.src = baseurl + 'images/ajax-loader.gif';

    if ($("#eds2").is('*')) {
        $("#idpSelect").modal(
                {
                    Height: '500px',
                    minHeight: '500px'
                }
        );
    }
    $("button#vormversion").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vormversion',
            timeout: 2500,
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
                alert('Error occurred');
            }
        });
        return false;
    });
    $("button#vschema").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vschema',
            timeout: 2500,
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
                alert('Error ocured');
            }
        });
        return false;
    });
    $("button#vschemadb").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vschemadb',
            timeout: 2500,
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
                alert('Error ocured');
            }
        });
        return false;
    });
    $("button#vmigrate").click(function () {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vmigrate',
            timeout: 2500,
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
                alert('Error ocured');
            },
        });
        return false;
    });


// When the form is submitted
    $("#status form").submit(function () {

// Hide 'Submit' Button
        $('#submit').hide();

// Show Gif Spinning Rotator
        $('#ajax_loading').show();

// get timeoffset
        var browsertime = new Date();
        var browsertimezone = -browsertime.getTimezoneOffset();
// 'this' refers to the current submitted form  
        var str = $(this).serializeArray();
        str.push({name: 'browsertimeoffset', value: '' + browsertimezone + ''});

// -- Start AJAX Call --

        $.ajax({
            type: "POST",
            url: baseurl + 'authenticate/dologin', // Send the login info to this page
            data: str,
            success: function (msg) {

                $("#status").ajaxComplete(function (event, request, settings) {

                    // Show 'Submit' Button
                    $('#submit').show();

                    // Hide Gif Spinning Rotator
                    $('#ajax_loading').hide();

                    if (msg === 'OK') // LOGIN OK?
                    {
                        var login_response = '<div id="logged_in">' +
                                '<div style="width: 350px; float: left; margin-left: 70px;">' +
                                '<div style="width: 40px; float: left;">' +
                                '<img style="margin: 10px 0px 10px 0px;" align="absmiddle" src="' + baseurl + 'images/ajax-loader.gif">' +
                                '</div>' +
                                '<div style="margin: 10px 0px 0px 10px; float: right; width: 300px;">' +
                                "You are successfully logged in! <br /> Please wait while you're redirected...</div></div>";
                        $('a.modalCloseImg').hide();
                        $('#simplemodal-container').css("width", "auto").css("height", "auto").css("background", "transparent").css("box-shadow", "none").css("text-align", "center");
                        $(this).html(login_response); // Refers to 'status'

                        // After 3 seconds redirect the 
                        setTimeout('go_to_private_page()', 1000);
                    }
                    else // ERROR?
                    {
                        var login_response = msg;
                        $('#login_response').html(login_response);
                    }

                });

            },
            error: function () {
                $("#status").ajaxComplete(function (event, request, settings) {
                    $('#submit').show();
                    $('#ajax_loading').hide();
                    var login_response = "Invalid token, please refresh page and try again";
                    $('#login_response').html(login_response).css("color", "red").css("font-weight", "bold");
                });
            }


        });

// -- End AJAX Call --

        return false;

    }); // end submit event

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
                    if (data === 'OK')
                    {
                        $(this).foundation('reveal', 'close');
                        location.reload();
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('Error occured: ' + errorThrown);
                }
            });

        });
    });


    // updatenotifactionstatus old place
    $("#idpmatrix tr td:not(:first-child)").click(function (ev) {
        var col = $(this).parent().children().index($(this));
        var cell = $.trim($(this).text());
        var oko = $('div', this);
        var lpath = window.location.pathname;
        var lastsegment = lpath.substring(lpath.lastIndexOf('/') + 1);
        if (col > 0 && cell.length > 0)
        {
            var row = $(this).parent().parent().children().index($(this).parent());
            var attrname = $("#idpmatrix th:eq(" + col + ") text").text();
            var spname = $("#idpmatrix tbody tr:eq(" + row + ") td:first a").attr('title');
            $("form#idpmatrixform #attribute").val(attrname);
            $("form#idpmatrixform #requester").val(spname);
            $("form#idpmatrixform span.mrequester").html(spname);
            $("form#idpmatrixform span.mattribute").html(attrname);
            var url = $("form#idpmatrixform").attr('action');
            $.ajax({
                type: "POST",
                url: baseurl + "manage/attribute_policyajax/retrieveattrpath/" + lastsegment,
                data: $("form#idpmatrixform").serializeArray(),
                success: function (json) {
                    if (!json)
                    {
                    }
                    else
                    {
                        var tbody_data = $('<tbody></tbody>');
                        var thdata = '<thead><th colspan="2">Current attribute flow</th></thead>';
                        $.each(json.details, function (i, v) {
                            var trdata = '<tr><td>' + v.name + '</td><td>' + v.value + '</td/></tr>';
                            tbody_data.append(trdata);
                        });
                        var tbl = $('<table/>').css({'font-size': 'smaller'}).css({'border': '1px solid'}).addClass('detailsnosort').addClass('small-12').addClass('columns');
                        var pl = $('<div/>');
                        tbl.append(thdata);
                        tbl.append(tbody_data);
                        pl.append(tbl);
                        $("div.attrflow").replaceWith('<div class="attrflow">' + pl.html() + '</div>');

                    }  //end else
                }

            });
            idpmatrixform('', function (ev) {
                var serializedData = $("form#idpmatrixform").serializeArray();
                $.ajax({
                    type: "POST",
                    url: url,
                    data: serializedData,
                    success: function (data) {
                        if (!oko.hasClass('dis'))
                        {
                            if ((data === "2" && (cell === "R" || cell === "D")) || (data === "1" && cell === "R"))
                            {
                                oko.attr('class', 'perm');
                            }
                            else if ((data === "2c" && (cell === "R" || cell === "D")) || (data === "1c" && cell === "R"))
                            {
                                oko.attr('class', 'spec');
                            }
                            else if ((data === "1" && cell === "D") || (data === "0"))
                            {
                                oko.attr('class', 'den');
                            }
                            else if ((data === "1c" && cell === "D") || (data === "0c"))
                            {
                                oko.attr('class', 'den');
                            }

                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('Error occured: ' + errorThrown);
                    }
                });
            }
            );
        }
        ev.preventDefault();
    });

    $('button[name="fedstatus"]').click(function (ev) {
        var btnVal = $(this).attr('value');
        var additionalMsg = $(this).attr('title');
        if (additionalMsg === undefined)
        {
            additionalMsg = '';
        }
        var csrfname = $("[name='csrfname']").val();
        var csrfhash = $("[name='csrfhash']").val();
        var baseurl = $("[name='baseurl']").val();
        var fedname = $("span#fednameencoded").text();
        var url = baseurl + 'federations/manage/changestatus';
        var data = [{name: 'status', value: btnVal}, {name: csrfname, value: csrfhash}, {name: 'fedname', value: fedname}];
        sconfirm('' + additionalMsg + '', function (ev) {
            $.ajax({
                type: "POST",
                url: url,
                data: data,
                success: function (data) {
                    if (data) {
                        if (data === 'deactivated')
                        {
                            $('button[value="disablefed"]').hide();
                            $('button[value="enablefed"]').show();
                            $('button[value="delfed"]').show();
                            $('td.fedstatusinactive').show();
                            $('show.fedstatusinactive').show();
                        }
                        else if (data === 'activated')
                        {
                            $('button[value="disablefed"]').show();
                            $('button[value="enablefed"]').hide();
                            $('button[value="delfed"]').hide();
                            $('td.fedstatusinactive').hide();
                            $('span.fedstatusinactive').hide();
                        }
                        else if (data === 'todelete')
                        {
                            $('button[value="disablefed"]').hide();
                            $('button[value="enablefed"]').hide();
                            $('button[value="delfed"]').hide();
                        }

                    }
                },
                error: function (data) {
                    alert('Error  ocurred');
                }

            });
        });
    });
    $("#rmstatdef button").click(function (ev) {
        var url = $("form#rmstatdef").attr('action');
        var serializedData = $("form#rmstatdef").serialize();
        sconfirm('', function (ev) {
            $.ajax({
                type: "POST",
                url: url,
                data: serializedData,
                success: function (data) {
                    $('#resultdialog').modal({
                        position: ["20%", ],
                        overlayId: 'simpledialog-overlay',
                        containerId: 'simpledialog-container',
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                    });
                },
                error: function (data) {
                    alert('Error');
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
                value: 'remove',
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
                value: 'remove',
            }).appendTo('form');
            $("form").submit();

        });
        ev.preventDefault();
    });

    function  notificationadd2(message, callback) {
        $("#notificationaddmodal").foundation('reveal', 'open', {});
        $(document).on('opened', '#notificationaddmodal', function () {
            var modal = $(this);
            $('select#sfederation').parent().hide();
            $('select#sprovider').parent().hide();
            $('select#type').change(function ()
            {
                $('select#sfederation').parent().hide();
                $('select#sprovider').parent().hide();
                var optionSelected = $(this).find("option:selected");
                var valueSelected = optionSelected.val();
                var textSelected = optionSelected.text();
                var selfed = $('#sfederation');
                var selprovider = $('#sprovider');
                selfed.find('option').remove();
                selprovider.find('option').remove();
                if (valueSelected === "joinfedreq" || valueSelected === "fedmemberschanged")
                {
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
                else if (valueSelected === "requeststoproviders")
                {
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
    ;

    function notificationadd(message, callback) {
        $('#notificationaddform').modal({
            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
            position: ["20%", ],
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
                    if (valueSelected === "joinfedreq" || valueSelected === "fedmemberschanged")
                    {
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
                    else if (valueSelected === "requeststoproviders")
                    {
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

    function idpmatrixform(message, callback) {
        $('#idpmatrixform').modal({
            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
            position: ["20%", ],
            overlayId: 'simpledialog-overlay',
            minHeight: '500px',
            Height: '500px',
            minWidth: '600px',
            containerId: 'simpledialog-container',
            onShow: function (dialog) {
                var modal = this;
                $('.message', dialog.data[0]).append(message);
                $('.yes', dialog.data[0]).click(function () {
                    if ($.isFunction(callback)) {
                        callback.apply();
                    }
                    modal.close(); // or $.modal.close();
                });
            }
        });
    }




    function sconfirm(message, callback) {
        $('#sconfirm').modal({
            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
            position: ["20%", ],
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

function go_to_private_page()
{
    window.location.reload();
}

// parsemetadata
$("button#parsemetadataidp").click(function () {
    var xmlsource = $('textarea#metadatabody').val();
    try {
        var xmlDoc = $.parseXML(xmlsource);
    }
    catch (err)
    {
        alert(err);
        return false;
    }

    var xml = $(xmlDoc);
    $entity = null;
    $spssodescriptor = null;


    xml.find("md\\:IDPSSODescriptor,IDPSSODescriptor").each(function () {
        if ($(this).attr("protocolSupportEnumeration"))
        {
            $entity = $(this).parent();
            $idpssodescriptor = $(this);
            return false;
        }
        return true;
    });
    if ($entity === null)
    {
        alert("IDPSSODescriptor element not found");
        return false;
    }
    $("#entityid").val($entity.attr("entityID"));
    $orgname = null;
    $entity.find("md\\:OrganizationName,OrganizationName").each(function () {
        $orgname = $(this);
        $langname = $orgname.attr("xml:lang");
        if ($langname === "en")
        {
            return false;
        }
    });
    $orgdisname = null;
    $entity.find("md\\:OrganizationDisplayName,OrganizationDisplayName").each(function () {
        $orgdisname = $(this);
        $langname = $orgdisname.attr("xml:lang");
        if ($langname === "en")
        {
            return false;
        }

    });
    $helpdeskurl = null;
    $entity.find("md\\:OrganizationURL,OrganizationURL").each(function () {
        $helpdeskurl = $(this);
        $langname = $helpdeskurl.attr("xml:lang");
        if ($langname === "en")
        {
            return false;
        }
    });
    $contact = null;
    $entity.find("md\\:ContactPerson,ContactPerson").each(function () {
        $contact = $(this);
        $contacttype = $contact.attr("contactType");
        if ($contacttype === "administrative")
        {
            return false;
        }
    });
    if ($contact != null)
    {
        $contactname = '';
        $contact.find("md\\:GivenName,GivenName").each(function () {
            $contactname = $(this).text();
        });
        $contact.find("md\\:SurName,SurName").each(function () {
            $contactname = $contactname + ' ' + $(this).text();
        });
        $contactemail = '';
        $contact.find("md\\:EmailAddress,EmailAddress").each(function () {
            $contactemail = $(this).text();
        });
        $("#contact\_name").val($contactname);
        $("#contact\_mail").val($contactemail);
    }
    $nameids = '';
    $idpssodescriptor.find("md\\:NameIDFormat,NameIDFormat").each(function () {
        $nameids = $nameids + ' ' + $(this).text();
    });
    $scopes = '';
    $idpssodescriptor.find("shibmd\\:Scope,Scope").each(function () {
        if ($(this).attr("regexp") && $(this).attr("regexp") === 'false')
        {
            if ($scopes != '')
            {
                $scopes = $scopes + ',' + $(this).text();
            }
            else
            {
                $scopes = $(this).text();
            }
        }
    });
    $("#idpssoscope").val($.trim($scopes));

    $idpssodescriptor.find("md\\:SingleSignOnService,SingleSignOnService").each(function () {
        $binprot = $(this).attr("Binding");
        $ssourl = $(this).attr("Location");
        if ($binprot === "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect")
        {
            $("#sso\\[saml2httpredirect\\]").val($.trim($ssourl));
        }
        else if ($binprot === "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST")
        {
            $("#sso\\[saml2httppost\\]").val($.trim($ssourl));
        }
        else if ($binprot === "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign")
        {
            $("#sso\\[saml2httppostsimplesign\\]").val($.trim($ssourl));

        }
        else
        {
            $("#sso\\[" + $binprot + "\\]").val($.trim($ssourl));
        }
    });

    var certsign = false;
    var certenc = false;
    $idpssodescriptor.find("md\\:KeyDescriptor, KeyDescriptor").each(function () {
        if (!certsign || !certenc)
        {
            if ($(this).attr("use") === "signing")
            {
                if (!certsign)
                {
                    var cert = $(this).find("ds\\:X509Certificate,X509Certificate");
                    $("#sign_cert_body").val($.trim(cert.text()));
                    certsign = true;
                }
            }
            else if ($(this).attr("use") === "encryption")
            {
                if (!certenc)
                {
                    var cert = $(this).find("ds\\:X509Certificate,X509Certificate");
                    $("#encrypt_cert_body").val($.trim(cert.text()));
                    certenc = true;
                }
            }
            else
            {
                var cert = $(this).find("ds\\:X509Certificate,X509Certificate");
                if (!certenc)
                {
                    $("#encrypt_cert_body").val($.trim(cert.text()));
                    certenc = true;
                }
                if (!certsign)
                {
                    $("#sign_cert_body").val($.trim(cert.text()));
                    certsign = true;
                }
            }

        }
        else
        {
            return false;
        }

    });

    if ($orgname === null)
    {
        $("#homeorg").val("");
    }
    else
    {
        $("#homeorg").val($orgname.text());
    }
    if ($orgdisname === null)
    {
        $("#deschomeorg").val("");
    }
    else
    {
        $("#deschomeorg").val($orgdisname.text());
    }
    if ($helpdeskurl === null)
    {
        $("#helpdeskurl").val("");
    }
    else
    {
        $("#helpdeskurl").val($helpdeskurl.text());
    }

    $("#nameids").val($.trim($nameids));

    alert("Success");
    GINIT.initialize();

});
$("button#parsemetadatasp").click(function () {
    $("div.spregacsopt").remove();
    var xmlsource = $('textarea#metadatabody').val();
    try {
        var xmlDoc = $.parseXML(xmlsource);
    }
    catch (err)
    {
        alert(err);
        return false;
    }

    var xml = $(xmlDoc);
    $entity = null;

    xml.find("md\\:SPSSODescriptor,SPSSODescriptor").each(function () {
        if ($(this).attr("protocolSupportEnumeration"))
        {
            $entity = $(this).parent();
            return false;
        }
        return true;
    });
    if ($entity === null)
    {
        alert("SPSSODescriptor not found");
        return false;
    }
    $("div.optspregacs").remove();
    $("#entityid").val($entity.attr("entityID"));
    $orgname = $entity.find("md\\:OrganizationName,OrganizationName").first();
    $orgdisname = $entity.find("md\\:OrganizationDisplayName,OrganizationDisplayName").first();
    $helpdeskurl = $entity.find("md\\:OrganizationURL,OrganizationURL").first();
    $("#resource").val($orgname.text());
    $("#descresource").val($orgdisname.text());
    $("#helpdeskurl").val($helpdeskurl.text());
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    var defaultacs = false;

    $entity.find("md\\:AssertionConsumerService,AssertionConsumerService").each(function () {
        rname = "";
        for (var i = 0; i < 5; i++)
        {
            rname += possible.charAt(Math.floor(Math.random() * possible.length));
        }
        if (defaultacs != true)
        {
            $("#acs_url\\[0\\]").val($(this).attr("Location"));
            $("#acs_order\\[0\\]").val($(this).attr("index"));
            $('#acs_bind\\[0\\]').val($(this).attr('Binding'));
            defaultacs = true;
        }
        else
        {
            var nelement = $("div.spregacs").first().clone().removeAttr("class").addClass("spregacsopt");

            $("#acs_url\\[0\\]", nelement).removeAttr("name").attr("name", "acs_url\[" + rname + "\]").attr("id", "acs_url\[" + rname + "\]").val($(this).attr("Location"));
            $("#acs_order\\[0\\]", nelement).removeAttr("name").attr("name", "acs_order\[" + rname + "\]").attr("id", "acs_order\[" + rname + "\]").val($(this).attr("index"));
            var acsbind = $("#acs_bind\\[0\\]", nelement).removeAttr("name").attr("name", "acs_bind\[" + rname + "\]").attr("id", "acs_bind\[" + rname + "\]");
            $('option', acsbind).removeAttr('selected').filter('[value="' + $(this).attr('Binding') + '"]').attr('selected', true);
            $("div.spregacs").after(nelement);
        }
    });

    var nameids = '';
    $entity.find("md\\:NameIDFormat, NameIDFormat").each(function () {
        nameids = nameids + ' ' + $(this).text();
    });

    $("#nameids").val(nameids);

    var certsign = false;
    var certenc = false;
    $entity.find("md\\:KeyDescriptor, KeyDescriptor").each(function () {
        if (!certsign || !certenc)
        {
            if ($(this).attr("use") === "signing")
            {
                if (!certsign)
                {
                    var cert = $(this).find("ds\\:X509Certificate,X509Certificate");
                    $("#sign_cert_body").val(cert.text());
                    certsign = true;
                }
            }
            else if ($(this).attr("use") === "encryption")
            {
                if (!certenc)
                {
                    var cert = $(this).find("ds\\:X509Certificate,X509Certificate");
                    $("#encrypt_cert_body").val(cert.text());
                    certenc = true;
                }
            }
            else
            {
                var cert = $(this).find("ds\\:X509Certificate,X509Certificate");
                if (!certenc)
                {
                    $("#encrypt_cert_body").val(cert.text());
                    certenc = true;
                }
                if (!certsign)
                {
                    $("#sign_cert_body").val(cert.text());
                    certsign = true;
                }
            }
        }
        else
        {
            return false;
        }
    });
    alert("Success");
    GINIT.initialize();

});

//  spregister

var current_fs, next_fs, previous_fs;
var left, opacity, scale;
var animating;
var index2;
var addheight = $("#progressbar").height() + 30;
var fieldsetheight = $("#multistepform fieldset").height() + addheight;
$("form#multistepform").css({'height': fieldsetheight});
$(".next").click(function () {
    var canproceed = true;
    $(this).parent().find('input.required').each(function () {
        if (!$.trim($(this).val()))
        {
            alert("Missing input");
            canproceed = false;
            return false;
        }
    });
    if (!canproceed)
    {
        return false;
    }
    if (animating)
        return false;
    animating = true;
    current_fs = $(this).parent();
    next_fs = $(this).parent().next();
    fieldsetheight = next_fs.height() + addheight;
    $("form#multistepform").css({'height': fieldsetheight});
    $("#progressbar li").eq($("#multistepform fieldset").index(next_fs)).addClass("active");
    next_fs.show();
    current_fs.animate({opacity: 0}, {
        step: function (now, mx) {
            scale = 1 - (1 - now) * 0.2;
            left = (now * 50) + "%";
            opacity = 1 - now;
            current_fs.css({'transform': 'scale(' + scale + ')'});
            next_fs.css({'left': left, 'opacity': opacity});
        },
        duration: 200,
        complete: function () {
            current_fs.hide();
            animating = false;
        },
    });
});

$(".previous").click(function () {
    if (animating)
        return false;
    animating = true;

    current_fs = $(this).parent();
    previous_fs = $(this).parent().prev();
    fieldsetheight = previous_fs.height() + addheight;
    $("form#multistepform").css({'height': fieldsetheight});

    //de-activate current step on progressbar
    $("#progressbar li").eq($("#multistepform fieldset").index(current_fs)).removeClass("active");

    //show the previous fieldset
    previous_fs.show();
    //hide the current fieldset with style
    current_fs.animate({opacity: 0}, {
        step: function (now, mx) {
            //as the opacity of current_fs reduces to 0 - stored in "now"
            //1. scale previous_fs from 80% to 100%
            scale = 0.8 + (1 - now) * 0.2;
            //2. take current_fs to the right(50%) - from 0%
            left = ((1 - now) * 50) + "%";
            //3. increase opacity of previous_fs to 1 as it moves in
            opacity = 1 - now;
            current_fs.css({'left': left});
            previous_fs.css({'transform': 'scale(' + scale + ')', 'opacity': opacity});
        },
        duration: 200,
        complete: function () {
            current_fs.hide();
            animating = false;
        },
        //this comes from the custom easing plugin
        //easing: 'easeInOutBack'
    });
});

$(".submit").click(function () {
    return false;
})

$('#joinfed select#fedid').on('change', function () {
    $("div.validaronotice").hide();
    $("ul.validatorbuttons").replaceWith('<ul class="button-group validatorbuttons"></ul>');
    var csrfname = $("[name='csrfname']").val();
    var csrfhash = $("[name='csrfhash']").val();
    if (csrfname === undefined)
    {
        csrfname = '';
    }
    if (csrfhash === undefined)
    {
        csrfhash = '';
    }
    var soption = $(this).find("option:selected").val();
    var sval = $(this).find("option:selected").text();
    var jsurl = $('div#retrfvalidatorjson').text();
    var postdata = {};
    postdata[csrfname] = csrfhash;
    postdata['fedid'] = soption;
    if (soption != 0)
    {
        $.ajax({
            type: "POST",
            url: jsurl,
            timeout: 2500,
            cache: true,
            data: postdata,
            success: function (json) {
                $('#spinner').hide();
                var data = $.parseJSON(json);
                if (data)
                {
                    $.each(data, function (i, v) {
                        $("ul.validatorbuttons").append('<li><button  value="' + jsurl + '/' + v.fedid + '/' + v.id + '" class="small button">' + v.name + '</button></li>');
                    })
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
        })
    }
});


// experimental: forcing scroll to top page # urls

$(document).ready(function () {
    $("button#addlhelpdesk").click(function () {
        var selected = $("span.lhelpdeskadd option:selected").first();
        var nf = selected.val();
        var rmbtn = $("button#helperbutttonrm").html();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"large-12 small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[lhelpdesk][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[lhelpdesk][" + nf + "]\" name=\"f[lhelpdesk][" + nf + "]\" type=\"text\" class=\"validurl\"/></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm ui-icon-trash button inline tiny left\" name=\"lhelpdesk\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        ;
        GINIT.initialize();
    });
    $("button#addldisplayname").click(function () {
        var selected = $("span.ldisplaynameadd option:selected").first();
        var nf = selected.val();
        var rmbtn = $("button#helperbutttonrm").html();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"large-12 small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[ldisplayname][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[ldisplayname][" + nf + "]\" name=\"f[ldisplayname][" + nf + "]\" type=\"text\"/></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm button tiny left inline\" name=\"ldisplayname\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });


    $("button#idpadduiidisplay").click(function () {
        var selected = $("span.idpuiidisplayadd option:selected").first();
        var nf = selected.val();
        var rmbtn = $("button#helperbutttonrm").html();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[[uii][idpsso][displayname][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[uii][idpsso][displayname][" + nf + "]\" name=\"f[uii][idpsso][displayname][" + nf + "]\" type=\"text\"/></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm button tiny left inline alert\" name=\"uiidisplayname\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });
    $("button#idpadduiihelpdesk").click(function () {
        var selected = $("span.idpuiihelpdeskadd option:selected").first();
        var nf = selected.val();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var rmbtn = $("button#helperbutttonrm").html();
        var nfv = selected.text();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[[uii][idpsso][helpdesk][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[uii][idpsso][helpdesk][" + nf + "]\" name=\"f[uii][idpsso][helpdesk][" + nf + "]\" type=\"text\"/></div><div class=\"small-3 large-2 columns\"><button type=\"button\" class=\"btn langinputrm button tiny left inline alert\" name=\"uiihelpdesk\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });
    $("button#spadduiidisplay").click(function () {
        var selected = $("span.spuiidisplayadd option:selected").first();
        var nf = selected.val();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var rmbtn = $("button#helperbutttonrm").html();
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[[uii][spsso][displayname][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[uii][spsso][displayname][" + nf + "]\" name=\"f[uii][spsso][displayname][" + nf + "]\" type=\"text\"/></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm inline left tiny button alert\" name=\"uiispnamerm\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });
    $("button#spadduiihelpdesk").click(function () {
        var selected = $("span.spuiihelpdeskadd option:selected").first();
        var nf = selected.val();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var rmbtn = $("button#helperbutttonrm").html();
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[[uii][spsso][helpdesk][" + nf + "]\" class=\"right inline\">" + nfv + " </label></div><div class=\"small-6 large-7 columns\"><input id=\"f[uii][spsso][helpdesk][" + nf + "]\" name=\"f[uii][spsso][helpdesk][" + nf + "]\" type=\"text\"/> </div><div class=\"small-3 large-2 columns\"><button type=\"button\" class=\"btn langinputrm inline tiny left button alert\" name=\"uiisphelpdeskrm\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });
    $("button#spadduiidesc").click(function () {
        var selected = $("span.spuiidescadd option:selected").first();
        var nf = selected.val();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var rmbtn = $("button#helperbutttonrm").html();
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[uii][spsso][desc][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><textarea id=\"f[uii][spsso][desc][" + nf + "]\" name=\"f[uii][spsso][desc][" + nf + "]\" rows=\"5\" cols=\"40\"/></textarea></div><div class=\"small-3 large-2 columns\"><button type=\"button\" class=\"btn langinputrm button tiny left inline alert\" name=\"uiispdescrm\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });

    $("button#addlname").click(function () {
        var selected = $("span.lnameadd option:selected").first();
        var nf = selected.val();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var rmbtn = $("button#helperbutttonrm").html();
        var nfv = selected.text();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"large-12 small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[lname][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[lname][" + nf + "]\" name=\"f[lname][" + nf + "]\" type=\"text\"/></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm button tiny left inline alert\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });
    $("button#idpadduiidesc").click(function () {
        var selected = $("span.idpuiidescadd option:selected").first();
        var nf = selected.val();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var rmbtn = $("button#helperbutttonrm").html();
        var nfv = selected.text();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[uii][idpsso][desc][" + nf + "]\" class=\"right inline\">" + nfv + " </label></div><div class=\"small-6 large-7 columns\"><textarea id=\"f[uii][idpsso][desc][" + nf + "]\" name=\"f[uii][idpsso][desc][" + nf + "]\" rows=\"5\" cols=\"40\"/></textarea></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm button tiny left inline\" name=\"ldesc\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });
    $("button#addregpolicy").click(function () {
        var selected = $("span.regpolicyadd option:selected").first();
        var nf = selected.val();
        var rmbtn = $("button#helperbutttonrm").html();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var rmbtn = $("button#helperbutttonrm").html();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"large-12 small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[regpolicy][" + nf + "]\" class=\"right inline\">" + nfv + " </label></div><div class=\"small-6 large-7 columns\"><input id=\"f[regpolicy][" + nf + "]\" name=\"f[regpolicy][" + nf + "]\" type=\"text\"/></div><div class=\"small-3 large-2 columns\"> <button type=\"button\" class=\"btn langinputrm button tiny left inline\" name=\"regpolicy\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        ;
        GINIT.initialize();
    });
    $("button#addlprivacyurlidpsso").click(function () {
        var selected = $("span.addlprivacyurlidpsso option:selected").first();
        var nf = selected.val();
        var rmbtn = $("button#helperbutttonrm").html();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[prvurl][idpsso][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[prvurl][idpsso][" + nf + "]\" name=\"f[prvurl][idpsso][" + nf + "]\" type=\"text\"/></div><div class=\"small-3 large-2 columns\">  <button type=\"button\" class=\"btn langinputrm button tiny left inline\" name=\"regpolicy\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });
    $("button#addlprivacyurlspsso").click(function () {
        var selected = $("span.addlprivacyurlspsso option:selected").first();
        var nf = selected.val();
        if (typeof nf === 'undefined')
        {
            return false;
        }
        var nfv = selected.text();
        var rmbtn = $("button#helperbutttonrm").html();
        var inputname = $(this).attr('value');
        selected.attr('disabled', true).attr('selected', false);
        $("<div class=\"small-12 columns\"><div class=\"small-3 columns\"><label for=\"f[prvurl][spsso][" + nf + "]\" class=\"right inline\">" + nfv + "</label></div><div class=\"small-6 large-7 columns\"><input id=\"f[prvurl][spsso][" + nf + "]\" name=\"f[prvurl][spsso][" + nf + "]\" type=\"text\"/> </div><div class=\"small-3 large-2 columns\"><button type=\"button\" class=\"btn langinputrm button tiny left inline alert\" name=\"regpolicy\" value=\"" + nf + "\">" + rmbtn + "</button></div></div>").insertBefore($(this).closest('span').parent());
        GINIT.initialize();
    });


    $("#ncontactbtn").click(function () {
        var rname = "";
        var btnvalues = $(this).attr('value').split('|');
        var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
        for (var i = 0; i < 5; i++)
            rname += possible.charAt(Math.floor(Math.random() * possible.length));

        var newelement = '<div class="group"><div class="small-12 columns"><fieldset><legend>' + btnvalues[5] + '</legend><div><div class="small-12 columns"><div class="small-3 columns"><label for="f[contact][n_' + rname + '][type]" class="right inline">' + btnvalues[1] + '</label></div><div class="small-8 large-7 columns inline"><select name="f[contact][n_' + rname + '][type]"> <option value="administrative">Administrative</option> <option value="technical">Technical</option> <option value="support" selected="selected">Support</option> <option value="billing">Billing</option> <option value="other">Other</option> </select></div><div class="small-1 large-2 columns"></div></div> <div class="small-12 columns"><div class="small-3 columns"><label for="f[contact][n_' + rname + '][fname]" class="right inline">' + btnvalues[2] + '</label></div><div  class="small-8 large-7 columns"><input type="text" name="f[contact][n_' + rname + '][fname]" value="" id="f[contact][n_' + rname + '][fname]" class="right inline" /></div><div class="small-1 large-2 columns"></div></div> <div class="small-12 columns"><div  class="small-3 columns"><label for="f[contact][n_' + rname + '][sname]" class="right inline">' + btnvalues[3] + '</label></div><div class="small-8 large-7 columns"><input type="text" name="f[contact][n_' + rname + '][sname]" value="" id="f[contact][n_' + rname + '][sname]" class="right inline" /></div><div class="small-1 large-2 columns"></div></div><div class="small-12 columns"><div class="small-3 columns"><label for="f[contact][n_' + rname + '][email]" class="right inline ">' + btnvalues[4] + '</label></div><div class="small-8 large-7 columns"><input type="text" name="f[contact][n_' + rname + '][email]" value="" id="f[contact][n_' + rname + '][email]" class="right inline" /></div><div class="small-1 large-2 columns"></div></div><div class="rmelbtn small-12 columns"><div class="small-9 large-10 columns"><button type="button" class="btn contactrm tiny alert button inline right" name="contact" value="' + rname + '">' + btnvalues[0] + '</button></div><div class="small-3 large-2 columns"></div></div></div></fieldset></div></div>';
        $(this).parent().before(newelement);
        GINIT.initialize();

    });

});
$("#showhelps").click(function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    var param = "n";

    if ($("#showhelps").hasClass('helpactive'))
    {
        param = "n";
    }
    else
    {
        param = "y";
    }

    $.ajax({
        type: 'GET',
        url: url + '/' + param,
        success: function () {
            $("#showhelps").toggleClass('helpinactive').toggleClass('helpactive');
            $(".dhelp").toggle();
            $("img.iconhelpshow").toggle();
            $("img.iconhelpcross").toggle();
        }
    });
});

$("div.section").parent().addClass("section");


$("form#notificationupdateform").submit(function (e) {

    e.preventDefault();
    var serializedData = $(this).serializeArray();
    var posturl = $(this).attr('action');
    var notid = $("input[name=noteid]").val();
    var buttonwithval = $('button[type="button"][value="' + notid + '"]');
    var ctr = $(buttonwithval).closest("tr");
    var subsriptionstatus = ctr.find('div.subscrstatus:first');
    $.ajax({
        type: "POST",
        url: posturl,
        data: serializedData,
        success: function (data) {
            if (data)
            {
                var foundrecord = false;

                $.each(data, function (i, v) {
                    if (v.id == notid)
                    {
                        foundrecord = true;

                        subsriptionstatus.text(v.langstatus);
                    }

                });
                if (!foundrecord)
                {
                    ctr.hide();
                }
            }
            else
            {

            }
            $('#notificationupdatemodal').foundation('reveal', 'close');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert('Error occured: ' + errorThrown);
        }


    });

});



$("div#loginform form").submit(function () {
    //e.preventDefault;
    var link = $("div#loginform form").attr('action');
    var str = $(this).serializeArray();
    var browsertime = new Date();
    var browsertimezone = -browsertime.getTimezoneOffset();
    str.push({name: 'browsertimeoffset', value: '' + browsertimezone + ''});

    $.ajax({
        type: "POST",
        cache: false,
        timeout: 2500,
        url: link, // Send the login info to this page
        data: str,
        beforeSend: function () {
            $("#loginresponse").html("").hide();

        },
        success: function (data) {
            if (data == 'OK')
            {
                $('#loginform').foundation('reveal', 'close');
                setTimeout('go_to_private_page()', 1000);
            }
            else
            {
                $("#loginresponse").html(data).show();

            }

        },
        error: function (jqXHR, textStatus, errorThrown) {
            ("#loginresponse").html("Error").show();

        },
    });
    return false;

});
$("button.advancedmode").click(function () {
    var thisB = $(this);
    var postUrl = thisB.val();
    var csrfname = $("[name='csrfname']").val();
    var csrfhash = $("[name='csrfhash']").val();
    $(this).closest("form").attr("action", postUrl);

});


// get list providers with dynamic list columns: in progress
$("a.afilter").click(function () {
    var url = $(this).attr("href");
    $('a.initiated').removeClass('initiatied');
    var filter;
    if ($(this).hasClass('filterext'))
    {
        filter = 1;
    }
    else if ($(this).hasClass('filterlocal'))
    {
        filter = 2;
    }
    else
    {
        filter = 0;
    }
    $.ajax({
        type: "GET",
        url: url,
        timeout: 9500,
        cache: true,
        dataType: "json",
        success: function (json) {
            $('#spinner').hide();
            if (filter == 1)
            {
                $('dd.filterext').addClass('active');
            }
            else if (filter == 2)
            {
                $('dd.filterlocal').addClass('active');
            }
            else
            {
                $('dd.filterall').addClass('active');
            }
            var result = json;
            if (result)
            {
                var table = $('<table/>');
                table.attr('id', 'details');
                table.addClass('filterlist');
                var thead = $('<thead/>');
                table.append(thead);
                var theadtr = $('<tr/>');
                thead.append(theadtr);

                var Columns = new Array();
                var tmpcolumns = result.columns;
                var colstatus;
                var counter = 0;
                $.each(tmpcolumns, function (i, v) {
                    colstatus = v.status;
                    if (colstatus)
                    {
                        nar = new Array();
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
                    if ((w.plocal == 1 && (filter == 2 || filter == 0)) || (w.plocal == 0 && filter < 2))
                    {
                        tbodyToInsert[a++] = '<tr>';
                        $.each(Columns, function (p, z) {
                            var cell = '';
                            $.each(z, function (r, s) {
                                if(w[s] != null)
                                {
                                   if (s === 'pname')
                                   {
                                      cell = cell + '<a href="' + result.baseurl + 'providers/detail/show/' + w.pid + '">' + w[s] + '</a><br />';

                                   }
                                   else if ( s === 'phelpurl')
                                   {
                                      cell = cell + '<a href="' + w.phelpurl + '">' + w.phelpurl + '</a>';
                                   }
                                   else if (s === 'plocked' || s === 'pactive' || s === 'plocal' || s === 'pstatic' || s === 'pvisible' || s === 'pavailable')
                                   {
                                      if (result['statedefs'][s][w[s]] != undefined)
                                      {
                                          cell = cell + ' <span class="lbl lbl-' + s + '-' + w[s] + '">' + result['statedefs'][s][w[s]] + '</span>';
                                      }
                                   }
                                   else 
                                   {
                                      cell = cell + '  ' + w[s];
                                   }
                                }
                            });
                            tbodyToInsert[a++] = '<td>' + cell + '</td>';

                        })
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
                if (counter > 1)
                {
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
            var rarray = new Array();
            $.each(json, function (ig, vg) {
                rarray.push(vg);

            });
            $("input[name='checkrole[]']").each(function () {
                var val = $(this).attr('value');
                var cc = $(this).attr('checked')


                if ($.inArray(val, rarray) === -1) {

                    $(this).prop("checked", false);

                }
                else
                {
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
            if (json)
            {
                var txtToReplace = '';
                $.each(json, function (i, v)
                {
                    txtToReplace = txtToReplace + v + ',';
                });
                $('span#currentroles').empty().append(txtToReplace.substring(0, txtToReplace.length - 1));
            }
        }

    });

});
$('button.addnewlogo').click(function () {
    var f = $(this).closest('div.reviewlogo');
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    var ftype = f.attr('id');
    var type;
    var rname = '';
    if (ftype === 'idpreviewlogo')
    {
        type = 'idp';
    }
    else
    {
        type = 'sp';
    }
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var logourl = f.find("input[name='" + type + "inputurl']").attr('value');
    var logosize = f.find("input[name='" + type + "inputsize']").attr('value');
    var logolang = f.find("select[name='" + type + "logolang']").val();
    if (logolang === '0')
    {
        var logolangtxt = 'unspec';
    }
    else
    {
        var logolangtxt = logolang;
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
    var btnname = $(this).attr('name');
    var logourl, logoreview;
    var link = $(this).attr("value");
    if (btnname === 'idpgetlogo')
    {
        logoreview = $('div#idpreviewlogo');
        logoreview.hide();
        var alertlogoretrieve = $("small.idplogoretrieve");
        alertlogoretrieve.empty().hide();
        logourl = $("[name='idplogoretrieve']").val();
        var imgdiv = $("div#idpreviewlogo div.imgsource");
    }
    else
    {
        logoreview = $('div#spreviewlogo');
        logoreview.hide();
        var alertlogoretrieve = $("small.splogoretrieve");
        alertlogoretrieve.empty().hide();
        logourl = $("[name='splogoretrieve']").val();
        var imgdiv = $("div#spreviewlogo div.imgsource");

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
            if (json)
            {
                if (json['error'])
                {
                    alertlogoretrieve.append(json['error']).show();
                }
                else if (json['data'])
                {

                    var img = new Image()
                    img.onload = function () {

                    };
                    img.src = json.data.url;
                    var sizeinfo = json.data.width + 'x' + json.data.height;
                    var hiddeninputurl, hiddeninputsize, hiddeninputtype;
                    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
                    var rname = '';
                    for (var i = 0; i < 5; i++)
                        rname += possible.charAt(Math.floor(Math.random() * possible.length));

                    if (btnname === 'idpgetlogo') {
                        hiddeninputtype = '<input type="hidden" name="logotype" value="idp">';
                        hiddeninputurl = '<input type="hidden" name="idpinputurl" value="' + json.data.url + '">';
                        hiddeninputsize = '<input type="hidden" name="idpinputsize" value="' + sizeinfo + '">';

                        $('div#idpreviewlogo div.imgsource').empty().append(img).append(hiddeninputurl).append(hiddeninputsize).append(hiddeninputtype);
                        $('div#idpreviewlogo div.logoinfo').empty().append(sizeinfo);
                        logoreview.show();
                    }
                    else if (btnname === 'spgetlogo') {
                        hiddeninputtype = '<input type="hidden" name="logotype" value="idp">';
                        hiddeninputurl = '<input type="hidden" name="spinputurl" value="' + json.data.url + '">';
                        hiddeninputsize = '<input type="hidden" name="spinputsize" value="' + sizeinfo + '">';
                        $('div#spreviewlogo div.imgsource').empty().append(img).append(hiddeninputurl).append(hiddeninputsize).append(hiddeninputtype);
                        $('div#spreviewlogo div.logoinfo').empty().append(sizeinfo);

                        logoreview.show();
                    }
                }

            }
        }

    });



});

var checkRegpol;

$('input[type="radio"].withuncheck').hover(function() {
    checkRegpol = $(this).is(':checked');
});

$('input[type="radio"].withuncheck').click(function() {
    checkRegpol = !checkRegpol;
    $(this).attr('checked', checkRegpol);
});

