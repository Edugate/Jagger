var GINIT = {
    initialize: function() {

    var baseurl = $("[name='baseurl']").val();
    if (baseurl === undefined)
    {
        baseurl = '';
    }
    function notificationupdate(message, callback) {
        $('#notificationupdateform').modal({
            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
            position: ["20%", ],
            overlayId: 'simpledialog-overlay',
            minHeight: '20px',
            containerId: 'simpledialog-container',
            onShow: function(dialog) {
                var modal = this;
                $('.message', dialog.data[0]).append(message);
                $('.yes', dialog.data[0]).click(function() {
                    if ($.isFunction(callback)) {
                        callback.apply();
                    }
                    modal.close(); // or $.modal.close();
                });
            }
        });
    }

    $("button.updatenotifactionstatus").click(function(ev) {
        var notid = $(this).attr('value');
        var ctbl = $(this).closest("tbody");
        var posturl = baseurl + 'notifications/subscriber/updatestatus/' + notid;
        $("form#notificationupdateform").attr('action', posturl);
        $("form#notificationupdateform #noteid").val(notid);
        notificationupdate('', function(ev) {
            var serializedData = $("form#notificationupdateform").serializeArray();
            $.ajax({
                type: "POST",
                url: posturl,
                data: serializedData,
                success: function(data) {
                    if (data)
                    {
                        ctbl.html("");
                        var trdata;
                        var number = 1;
                        $.each(data, function(i, v) {
                            if (v.federationid)
                            {
                                var related = v.langfederation + ': ' + v.federationname;
                            }
                            else if (v.providerid)
                            {
                                var related = v.langprovider + ': ' + v.providername;

                            }
                            else
                            {
                                var related = v.langany;
                            }
                            trdata = '<tr><td>' + number + '</td><td>' + v.langtype + '</td><td>' + related + '</td><td>' + v.delivery + '</td><td>' + v.rcptto + '</td><td>' + v.langstatus + '</td><td>' + v.updated + '</td><td><button class="updatenotifactionstatus editbutton" type="button" value="' + v.id + '">update</button></td></tr>';
                            ctbl.append(trdata);
                            number = number + 1;

                        });
                        GINIT.initialize(); 
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('Error occured: ' + errorThrown);
                }
            });
        });
    });

        $('form#fvform').submit(function(e) {
            e.preventDefault();
            var str = $(this).serializeArray();
            var url = $("form#fvform").attr('action');

            $.ajax({
                type: "POST",
                url: url,
                cache: false,
                data: str,
                timeout: 10000,
                success: function(json) {
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
                        if (data.message)
                        {
                            var msgdata;
                            $.each(data.message, function(i, v) {
                                msgdata = '<div>' + i + ': ' + v + '</div>';
                                $("div#fvmessages").append(msgdata);
                            });

                        }

                    }
                },
                beforeSend: function() {
                    $("span#fvreturncode").text('');
                    $("div#fvmessages").text('');
                    $('#spinner').show();
                },
                error: function(x, t, m) {
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

        $("a.fmembers").click(function() {

            var link = $(this), url = link.attr("href");
            var row = $(this).parent().parent();
            if ($(row).hasClass('opened') == true)
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
                    success: function(json) {
                        $('#spinner').hide();
                        var data = $.parseJSON(json);
                        $(row).addClass('opened').addClass('highlight');
                        if (!data)
                        {
                            alert('no data');
                        }
                        else
                        {
                            if (!data.idp && !data.sp && !data.both)
                            {
                                var div_data = '<li> no members</li>';
                                value.append(div_data);
                            }
                            else
                            {
                                if (data.idp)
                                {
                                    var stitle = $('<li>Identity Providers</li>');
                                    var nlist = $('<ol/>');
                                    $.each(data.idp, function(i, v) {
                                        var div_data = '<li class="homeorg"><a href="' + v.url + '">' + v.name + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                                if (data.sp)
                                {
                                    var stitle = $('<li>Service Providers</li>');
                                    var nlist = $('<ol/>');
                                    $.each(data.sp, function(i, v) {
                                        var div_data = '<li class="resource"><a href="' + v.url + '">' + v.name + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                                if (data.both)
                                {
                                    var stitle = $('<li>Services are both IdP and SP</li>');
                                    var nlist = $('<ol/>');
                                    $.each(data.both, function(i, v) {
                                        var div_data = '<li class="both"><a href="' + v.url + '">' + v.name + '</a> (' + v.entityid + ') </li>';
                                        nlist.append(div_data);
                                    });
                                    stitle.append(nlist);
                                    value.append(stitle);
                                }
                            }
                        }


                    },
                    beforeSend: function() {
                        $('#spinner').show();
                    },
                    error: function() {
                        $('#spinner').hide();
                        alert('problem with loading data');
                    }
                }).done(function() {
                    var nextrow = '<tr class="feddetails"><td colspan="7"><ul class="feddetails">' + value.html() + '</ul></td></tr>';
                    $(nextrow).insertAfter(row);
                }
                )
            }

            return false;





        });
        $("a#getmembers").click(function() {
            var link = $(this), url = link.attr("href");
            var value = $('<ul/>');
            $.ajax({
                url: url,
                timeout: 2500,
                cache: true,
                success: function(json) {
                    $('#spinner').hide();
                    var data = $.parseJSON(json);
                    if (!data)
                    {
                        alert('no data');
                    }
                    else
                    {
                        var nlist = $('<ul/>');
                        $.each(data, function(i, v) {
                            var div_data = '<li><a href="' + v.url + '">' + v.name + '</a><small><i> (' + v.entityid + ') <i></small></li>';
                            nlist.append(div_data);
                        });
                        value.append(nlist);

                    }
                },
                beforeSend: function() {
                    $('#spinner').show();
                },
                error: function() {
                    $('#spinner').hide();
                    alert('problem with loading data');
                }

            }).done(function() {
                var nextrow = value.html();
                //$(nextrow).insertAfter(row);
                $("div#membership").replaceWith(nextrow);

            });
            return false;
        });
        $('.accordionButton').addClass('off');
        $('.accordionButton1').addClass('on');

        $('.accordionButton').mouseover(function() {
            $(this).addClass('over');

            //ON MOUSEOUT REMOVE THE OVER CLASS
        }).mouseout(function() {
            $(this).removeClass('over');
        });

        $('.accordionButton').click(function() {


            //REMOVE THE ON CLASS FROM ALL BUTTONS
            $('.accordionButton').removeClass('on');
            $('.accordionButton').addClass('off');
            //NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
            $('.accordionContent').slideUp('fast');

            //IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
            if ($(this).next().is(':hidden') == true) {

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

$(document).ready(function() {
    GINIT.initialize();

    $('#idpmatrix tr th').each(function(i) {
        var tds = $(this).parents('table').find('tr td:nth-child(' + (i + 1) + ')');
        if (tds.length == tds.filter(':empty').length) {
            $(this).hide();
            tds.hide();
        }
    });



    var fedloginurl = $('a#fedlogin').attr('href');
    var browsertime = new Date();
    var browsertimezone = -browsertime.getTimezoneOffset();
    $('a#fedlogin').attr('href', '' + fedloginurl + '/' + browsertimezone + '');

    var bubbletheme = $("button#jquerybubblepopupthemes").val();
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
    $('.bubblepopup').mouseover(function() {
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
        }, false).FreezeBubblePopup()

    });
    if ($('button.activated').length) {
        var url = $('button.activated').attr('value');
        var value = $('table.fedistpercat');
        var data;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            value: $('table.fedistpercat'),
            success: function(json) {
                $('#spinner').hide();
                data = $.parseJSON(json);
                if (!data)
                {
                    alert('no data in federation category');
                }
                else
                {
                    $("table.fedistpercat tbody tr").remove();
                    $.each(data, function(i, v) {
                        var tr_data = '<tr><td>' + v.name + '</td><td>' + v.urn + '</td><td>' + v.labels + '</td><td>' + v.desc + '</td><td>' + v.members + '</td></tr>';
                        value.append(tr_data);
                    });
                }
                GINIT.initialize();
            },
        });
    }
    ;


    $(".fedcategory").on('click', '', function(event) {

        $('button.fedcategory').removeClass('activated');
        $(this).addClass('activated');
        var url = $(this).attr("value");
        var value = $('table.fedistpercat');
        var data;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            value: value,
            success: function(json) {
                $('#spinner').hide();
                data = $.parseJSON(json);
                if (!data)
                {
                    alert('no data in federation category');
                }
                else
                {
                    $("table.fedistpercat tbody tr").remove();
                    $.each(data, function(i, v) {
                        var tr_data = '<tr><td>' + v.name + '</td><td>' + v.urn + '</td><td>' + v.labels + '</td><td>' + v.desc + '</td><td>' + v.members + '</td></tr>';
                        value.append(tr_data);
                    });
                }
                GINIT.initialize();
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                alert('problem with loading data');
            }
        }).done(function() {
            var nextrow = value.html();
            //$("table.fedistpercat").append(nextrow);
        });
        return false;
    });

});
//$("#login").hide();
$("button#loginbtn").click(function() {
    parent = window;

    $("#login").css({
        'position': 'absolute',
        'top': ((($(parent).height() - $("#login").outerHeight()) / 4) + $(parent).scrollTop() + "px"),
        'left': ((($(parent).width() - $("#login").outerWidth()) / 2) + $(parent).scrollLeft() + "px"),
        'z-index': '10'
    }).show();
});
$(function() {


    /*************/


    $.widget("ui.combobox", {
        _create: function() {
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
                        source: function(request, response) {
                            var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
                            response(select.children("option").map(function() {
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
                        select: function(event, ui) {
                            ui.item.option.selected = true;
                            self._trigger("selected", event, {
                                item: ui.item.option
                            });
                        },
                        change: function(event, ui) {
                            if (!ui.item) {
                                var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex($(this).val()) + "$", "i"),
                                        valid = false;
                                select.children("option").each(function() {
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

            input.data("autocomplete")._renderItem = function(ul, item) {
                return $("<li></li>")
                        .data("item.autocomplete", item)
                        .append("<a>" + item.label + "</a>")
                        .appendTo(ul);
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
                    .click(function() {
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
        destroy: function() {
            this.input.remove();
            this.button.remove();
            this.element.show();
            $.Widget.prototype.destroy.call(this);
        }
    });

    $("#combobox").combobox();
    $("#toggle").click(function() {
        $("#combobox").toggle();
    });
    /****************/

    $('div.floating-menu').addClass('mobilehidden');
    $('table.idplist tr td:first-child').addClass('homeorg');
    $('table.idplist tr td:first-child span.alert').removeClass('alert').parent().addClass('alert');
    var theTable1 = $('table.idplist')
    theTable1.find("tbody > tr").find("td:eq(1)").mousedown(function() {
    });
    $("#filter").keyup(function() {
        $.uiTableFilter(theTable1, this.value);
    })
    $('#filter-form').submit(function() {
        theTable1.find("tbody > tr:visible > td:eq(1)").mousedown();
        return false;
    }).focus();

    $('table.splist tr td:first-child span.alert').removeClass('alert').parent().addClass('alert');
    var theTable2 = $('table.splist')
    theTable2.find("tbody > tr").find("td:eq(1)").mousedown(function() {
    });
    $("#filter").keyup(function() {
        $.uiTableFilter(theTable2, this.value);
    })
    $('#filter-form').submit(function() {
        theTable2.find("tbody > tr:visible > td:eq(1)").mousedown();
        return false;
    }).focus();



    $("#validfrom").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $(".validfrom").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#validto").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $(".validto").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#registerdate").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $(".registrationdate").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#registrationdate").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#responsecontainer").load("awaiting/ajaxrefresh");
    var refreshId = setInterval(function() {
        $("#responsecontainer").load('awaiting/ajaxrefresh');
    }, 72000);
    $("#dashresponsecontainer").load("reports/awaiting/dashajaxrefresh");
    var refreshId = setInterval(function() {
        $("#dashresponsecontainer").load('reports/awaiting/dashajaxrefresh');
    }, 72000);

    $.ajaxSetup({
        cache: false
    });

    $.ajaxSetup({
        cache: false
    });
    $('#langchange select').on('change', function() {
        var link = document.getElementById('langurl').innerHTML;
        var url = link + this.value;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false
        }).done(function() {
            setTimeout('go_to_private_page()', 1000);
        });
        return false;
    });
    $("button#addlname").click(function() {
        var nf = $("span.lnameadd option:selected").val();
        var nfv = $("span.lnameadd option:selected").text();
        $("span.lnameadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[lname][" + nf + "]\">Name in " + nfv + " </label><input id=\"f[lname][" + nf + "]\" name=\"f[lname][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#addldisplayname").click(function() {
        var nf = $("span.ldisplaynameadd option:selected").val();
        var nfv = $("span.ldisplaynameadd option:selected").text();
        $("span.ldisplaynameadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[ldisplayname][" + nf + "]\">DisplayName in " + nfv + " </label><input id=\"f[ldisplayname][" + nf + "]\" name=\"f[ldisplayname][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#addregpolicy").click(function() {
        var nf = $("span.regpolicyadd option:selected").val();
        var nfv = $("span.regpolicyadd option:selected").text();
        $("span.regpolicyadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[regpolicy][" + nf + "]\">RegistrationPolicy in " + nfv + " </label><input id=\"f[regpolicy][" + nf + "]\" name=\"f[regpolicy][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#idpadduiidisplay").click(function() {
        var nf = $("span.idpuiidisplayadd option:selected").val();
        var nfv = $("span.idpuiidisplayadd option:selected").text();
        $("span.idpuiidisplayadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][idpsso][displayname][" + nf + "]\">DisplayName in " + nfv + " </label><input id=\"f[uii][idpsso][displayname][" + nf + "]\" name=\"f[uii][idpsso][displayname][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#spadduiidisplay").click(function() {
        var nf = $("span.spuiidisplayadd option:selected").val();
        var nfv = $("span.spuiidisplayadd option:selected").text();
        $("span.spuiidisplayadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][spsso][displayname][" + nf + "]\">DisplayName in " + nfv + " </label><input id=\"f[uii][spsso][displayname][" + nf + "]\" name=\"f[uii][spsso][displayname][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#idpadduiihelpdesk").click(function() {
        var nf = $("span.idpuiihelpdeskadd option:selected").val();
        var nfv = $("span.idpuiihelpdeskadd option:selected").text();
        $("span.idpuiihelpdeskadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][idpsso][helpdesk][" + nf + "]\">InformationURL in " + nfv + " </label><input id=\"f[uii][idpsso][helpdesk][" + nf + "]\" name=\"f[uii][idpsso][helpdesk][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#spadduiihelpdesk").click(function() {
        var nf = $("span.spuiihelpdeskadd option:selected").val();
        var nfv = $("span.spuiihelpdeskadd option:selected").text();
        $("span.spuiihelpdeskadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][spsso][helpdesk][" + nf + "]\">InformationURL in " + nfv + " </label><input id=\"f[uii][spsso][helpdesk][" + nf + "]\" name=\"f[uii][spsso][helpdesk][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#idpadduiiprvurl").click(function() {
        var nf = $("span.idpuiiprvurladd option:selected").val();
        var nfv = $("span.idpuiiprvurladd option:selected").text();
        $("span.idpuiiprvurladd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][idpsso][prvurl][" + nf + "]\">PrivacyStatementURL in " + nfv + " </label><input id=\"f[uii][idpsso][prvurl][" + nf + "]\" name=\"f[uii][idpsso][prvurl][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#spadduiiprvurl").click(function() {
        var nf = $("span.spuiiprvurladd option:selected").val();
        var nfv = $("span.spuiiprvurladd option:selected").text();
        $("span.spuiiprvurladd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[[uii][spsso][prvurl][" + nf + "]\">PrivacyStatementURL in " + nfv + " </label><input id=\"f[uii][spsso][prvurl][" + nf + "]\" name=\"f[uii][spsso][prvurl][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#addlhelpdesk").click(function() {
        var nf = $("span.lhelpdeskadd option:selected").val();
        var nfv = $("span.lhelpdeskadd option:selected").text();
        $("li.addlhelpdesk option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[lhelpdesk][" + nf + "]\">HelpdeskURL in " + nfv + " </label><input id=\"f[lhelpdesk][" + nf + "]\" name=\"f[lhelpdesk][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#addlprivacyurl").click(function() {
        var nf = $("li.addlprivacyurl option:selected").val();
        var nfv = $("li.addlprivacyurl option:selected").text();
        $("li.addlprivacyurl option[value=" + nf + "]").remove();
        $(this).parent().append("<li class=\"localized\"><label for=\"lprivacyurl[" + nf + "]\">Privacy Statement URL in " + nfv + " </label><input id=\"lprivacyurl[" + nf + "]\" name=\"lprivacyurl[" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#addlprivacyurlspsso").click(function() {
        var nf = $("li.addlprivacyurlspsso option:selected").val();
        var nfv = $("li.addlprivacyurlspsso option:selected").text();
        $("li.addlprivacyurlspsso option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[prvurl][spsso][" + nf + "]\">Privacy Statement URL in " + nfv + " </label><input id=\"f[prvurl][spsso][" + nf + "]\" name=\"f[prvurl][spsso][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#addlprivacyurlidpsso").click(function() {
        var nf = $("li.addlprivacyurlidpsso option:selected").val();
        var nfv = $("li.addlprivacyurlidpsso option:selected").text();
        $("li.addlprivacyurlidpsso option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[prvurl][idpsso][" + nf + "]\">Privacy Statement URL in " + nfv + " </label><input id=\"f[prvurl][idpsso][" + nf + "]\" name=\"f[prvurl][idpsso][" + nf + "]\" type=\"text\"/></li>");
    });
    $("button#addldescription").click(function() {
        var nf = $("span.ldescadd option:selected").val();
        var nfv = $("span.ldescadd option:selected").text();
        $("span.ldescadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[ldesc][" + nf + "]\">Description in " + nfv + " </label><textarea id=\"f[ldesc][" + nf + "]\" name=\"f[ldesc][" + nf + "]\" rows=\"5\" cols=\"40\"/></textarea></li>");
    });
    $("button#idpadduiidesc").click(function() {
        var nf = $("span.idpuiidescadd option:selected").val();
        var nfv = $("span.idpuiidescadd option:selected").text();
        $("span.idpuiidescadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[uii][idpsso][desc][" + nf + "]\">Description in " + nfv + " </label><textarea id=\"f[uii][idpsso][desc][" + nf + "]\" name=\"f[uii][idpsso][desc][" + nf + "]\" rows=\"5\" cols=\"40\"/></textarea></li>");
    });
    $("button#spadduiidesc").click(function() {
        var nf = $("span.spuiidescadd option:selected").val();
        var nfv = $("span.spuiidescadd option:selected").text();
        $("span.spuiidescadd option[value=" + nf + "]").remove();
        $(this).parent().prepend("<li class=\"localized\"><label for=\"f[uii][spsso][desc][" + nf + "]\">Description in " + nfv + " </label><textarea id=\"f[uii][spsso][desc][" + nf + "]\" name=\"f[uii][spsso][desc][" + nf + "]\" rows=\"5\" cols=\"40\"/></textarea></li>");
    });
    $("a#fedmetasigner").click(function() {
        var link = $(this), url = link.attr("href");
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function(data) {
                alert(data);
            }
        });
        return false;
    });
    $("a#providermetasigner").click(function() {
        var link = $(this), url = link.attr("href");
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function(data) {
                alert(data);
            }
        });
        return false;
    });


    $("a.downloadstat").click(function() {
        var link = $(this), url = link.attr("href");
        var data;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function(json) {
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
    $("a.lateststat").click(function() {
        var link = $(this), url = link.attr("href");
        var value = $('<div id="#statisticdiag">');
        $.ajax({
            url: url,
            timeout: 2500,
            cache: true,
            success: function(json)
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
                    $.each(data, function(i, v) {

                        i = new Image();
                        i.src = v.url;
                        $('#statisticdiag').append('<div style="text-align:center; font-weight: bold; width: 90%;">' + v.title + '</div>').append('<div style="font-weight: bolder; width: 90%; text-align: right;">' + v.subtitle + '</div>').append(i);

                    });
                }
                i = null;
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                alert('problem with loading data');
            }

        });
        return false;
    });
    $("a.clearcache").click(function() {
        var link = $(this), url = link.attr("href");

        $.ajax({
            type: "GET",
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).parent().remove()
        });
        return false;
    });

    $("a.bookentity").click(function() {
        var link = $(this), url = link.attr("href");

        $.ajax({
            type: "GET",
            url: url,
            timeout: 2500,
            cache: false
        });
        return false;
    });
    $("a.delbookentity").click(function() {
        var link = $(this), url = link.attr("href");

        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).parent().remove()
        });
        return false;
    });
    $("a.delbookfed").click(function() {
        var link = $(this), url = link.attr("href");

        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).parent().remove()
        });
        return false;
    });


    $("a#synchsettings").click(function() {
        var link = $(this), url = link.attr("href");
        var value = $('<ul/>');
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: function(json) {
                $('#spinner').hide();
                var data = $.parseJSON(json);
                if (!data)
                {
                    alert('no data');
                }
                else
                {
                    var nlist = $('<ul/>');
                    $.each(data, function(i, v) {
                        var div_data = '<li>' + v.result + '</li>';
                        nlist.append(div_data);
                    });
                    value.append(nlist);

                }
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                alert('problem with loading data');
            }

        }).done(function() {
            var nextrow = value.html();
            //$(nextrow).insertAfter(row);
            $("div#syncresult").replaceWith(nextrow);

        });
        return false;
    });

    $('table.reqattraddform').addClass('hidden');
    $('button.hideform').addClass('hidden');
    $('form.reqattraddform').addClass('hidden');

    $('button.showform').click(function() {
        $('table.reqattraddform').removeClass('hidden');
        $('form.reqattraddform').removeClass('hidden');
        $('button.showform').addClass('hidden');
        $('button.hideform').removeClass('hidden');
    });
    $('button.hideform').click(function() {
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
    $('.accordionButton1').click(function() {


        //REMOVE THE ON CLASS FROM ALL BUTTONS
        $('.accordionButton1').removeClass('on');
        $('.accordionButton1').addClass('off');
        //NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
        $('.accordionContent1').slideUp('fast');

        //IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
        if ($(this).next().is(':hidden') == true) {

            //ADD THE ON CLASS TO THE BUTTON
            $(this).addClass('on');
            $(this).removeClass('off');


            //OPEN THE SLIDE
            $(this).next().slideDown('fast');
        }

    });


    /*** REMOVE IF MOUSEOVER IS NOT REQUIRED ***/

    //ADDS THE .OVER CLASS FROM THE STYLESHEET ON MOUSEOVER 

    $('.accordionButton1').mouseover(function() {
        $(this).addClass('over');

        //ON MOUSEOUT REMOVE THE OVER CLASS
    }).mouseout(function() {
        $(this).removeClass('over');
    });

    /*** END REMOVE IF MOUSEOVER IS NOT REQUIRED ***/


    /********************************************************************************************************************
     CLOSES ALL S ON PAGE LOAD
     ********************************************************************************************************************/
//	$('.accordionContent1').toggle();

});


var ww = document.body.clientWidth;

$(document).ready(function() {
    $(".nav li a").each(function() {
        if ($(this).next().length > 0) {
            $(this).addClass("parent");
        }
        ;
    })

    $(".toggleMenu").click(function(e) {
        e.preventDefault();
        $(this).toggleClass("active");
        $(".nav").toggle();
    });
    adjustMenu();
})

$(window).bind('resize orientationchange', function() {
    ww = document.body.clientWidth;
    adjustMenu();
});

var adjustMenu = function() {
    if (ww < 768) {
        $("#filter-form").remove();
        $(".toggleMenu").css("display", "inline-block");
        if (!$(".toggleMenu").hasClass("active")) {
            $(".nav").hide();
        } else {
            $(".nav").show();
        }
        $(".nav li").unbind('mouseenter mouseleave');
        $(".nav li a.parent").unbind('click').bind('click', function(e) {
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
        $(".nav li").unbind('mouseenter mouseleave').bind('mouseenter mouseleave', function() {
            // must be attached to li so that mouseleave is not triggered when hover over submenu
            $(this).toggleClass('hover');
        });
    }
}

$(function() {
    var sticky_navigation_offset_top = 0;
    try {
        sticky_navigation_offset_top = $('nav').offset().top;
    }
    catch (err)
    {
        return false;
    }
    if ($('#submenuProvider').length)
    {
        var sticky_submenu_offset_top = $('#submenuProvider').offset().top;
    }

    // our function that decides weather the navigation bar should have "fixed" css position or not.
    var sticky_navigation = function() {
        var scroll_top = $(window).scrollTop(); // our current vertical position from the top
        var widthelement = $('nav').width();
        // if we've scrolled more than the navigation, change its position to fixed to stick to top, otherwise change it back to relative
        if (scroll_top > sticky_navigation_offset_top) {
            //$('nav').css({ 'position': 'fixed', 'top':0, 'left':0, 'width': '100%','zIndex':9999});
            $('nav').css({'position': 'fixed', 'top': 0, 'width': widthelement, 'zIndex': 999});

        } else {
            $('nav').css({'position': 'relative', 'width': 'auto', });
        }
        if (sticky_submenu_offset_top != 'undefined')
        {
            if (scroll_top > sticky_submenu_offset_top) {
                $('#submenuProvider').css({'position': 'fixed', 'top': 50, 'left': 0, 'width': '100%', 'zIndex': 9999, 'clear': 'both'});
            } else {
                $('#submenuProvider').css({'position': 'relative'});
            }
        }
    };
    sticky_navigation();
    $(window).scroll(function() {
        sticky_navigation();
    });
});


$(function() {
    $("#details").tablesorter({sortList: [[0, 0], [1, 0]], widgets: ['zebra']});
    $(".userlist#details").tablesorter({sortList: [[3, 1], [0, 0]], widgets: ['zebra']});
    $("#options").tablesorter({sortList: [[0, 0]], headers: {3: {sorter: false}, 4: {sorter: false}}});
    $("#formtabs").tabs();
    $("#providertabs").tabs({
        cache: true,
        load: function(event, ui) {
            $('.accordionButton').unbind();
            GINIT.initialize();
        }

    });
    $("#fedtabs").tabs({
        cache: true,
        load: function(event, ui) {
            $('.accordionButton').unbind();
            GINIT.initialize();
        }

    });
    $("#arptabs").tabs({
        cache: true,
        load: function(event, ui) {
            $('.accordionButton').unbind();
            $('.tablesorter').unbind();
            GINIT.initialize();
        }

    });

});
if ($('#usepredefined').attr('checked')) {
    $("fieldset#stadefext").hide();
}
$("#usepredefined").click(function() {
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
$(".acsdefault").click(function() {
    if ($(this).is(":checked"))
    {
        $(".acsdefault").not(this).removeAttr("checked");
    }
});
$("#nacsbtn").click(function() {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<li><ol><li><label for="f[srv][AssertionConsumerService][n_' + rname + '][bind]">Binding Name</label><span class=""><select name="f[srv][AssertionConsumerService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign">urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign</option> <option value="urn:oasis:names:tc:SAML:2.0:bindings:PAOS">urn:oasis:names:tc:SAML:2.0:bindings:PAOS</option> <option value="urn:oasis:names:tc:SAML:2.0:profiles:browser-post">urn:oasis:names:tc:SAML:2.0:profiles:browser-post</option> <option value="urn:oasis:names:tc:SAML:1.0:profiles:browser-post">urn:oasis:names:tc:SAML:1.0:profiles:browser-post</option> <option value="urn:oasis:names:tc:SAML:1.0:profiles:artifact-01">urn:oasis:names:tc:SAML:1.0:profiles:artifact-01</option> </select> </li><li><label for="f[srv][AssertionConsumerService][n_' + rname + '][url]">URL</label><input name="f[srv][AssertionConsumerService][n_' + rname + '][url]" id="f[srv][AssertionConsumerService][n_' + rname + '][url]" type="text"> index <input type="text" name="f[srv][AssertionConsumerService][n_' + rname + '][order]" value="" id="f[srv][AssertionConsumerService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></li><li><label for="f[srv][AssertionConsumerService][n_' + rname + '][default]">Is default</label><input type="radio" name="f[srv][AssertionConsumerService][n_' + rname + '][default]" value="1" id="f[srv][AssertionConsumerService][n_' + rname + '][default]" class="acsdefault "  /></li></ol></li>';
    $(this).parent().before(newelement);

});
$("#nspartifactbtn").click(function() {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<li><ol><li><label for="f[srv][SPArtifactResolutionService][n_' + rname + '][bind]">Binding Name</label><span class=""><select name="f[srv][SPArtifactResolutionService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:SOAP</option> <option value="urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding">urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding</option></select> </li><li><label for="f[srv][SPArtifactResolutionService][n_' + rname + '][url]">URL</label><input name="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" id="f[srv][SPArtifactResolutionService][n_' + rname + '][url]" type="text"> index <input type="text" name="f[srv][SPArtifactResolutionService][n_' + rname + '][order]" value="" id="f[srv][SPArtifactResolutionService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></li></ol></li>';
    $(this).parent().before(newelement);
});
$("#nidpartifactbtn").click(function() {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<li><ol><li><label for="f[srv][IDPArtifactResolutionService][n_' + rname + '][bind]">Binding Name</label><span class=""><select name="f[srv][IDPArtifactResolutionService][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" selected="selected">urn:oasis:names:tc:SAML:2.0:bindings:SOAP</option> <option value="urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding">urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding</option></select> </li><li><label for="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]">URL</label><input name="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" id="f[srv][IDPArtifactResolutionService][n_' + rname + '][url]" type="text"> index <input type="text" name="f[srv][IDPArtifactResolutionService][n_' + rname + '][order]" value="" id="f[srv][IDPArtifactResolutionService][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></li></ol></li>';
    $(this).parent().before(newelement);
});
$("#ndrbtn").click(function() {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<li><ol><li><label for="f[srv][DiscoveryResponse][n_' + rname + '][bind]">Binding Name</label><span class=""><select name="f[srv][DiscoveryResponse][n_' + rname + '][bind]"> <option value="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol">urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol</option></select> </li><li><label for="f[srv][DiscoveryResponse][n_' + rname + '][url]">URL</label><input name="f[srv][DiscoveryResponse][n_' + rname + '][url]" id="f[srv][DiscoveryResponse][n_' + rname + '][url]" type="text"> index <input type="text" name="f[srv][DiscoveryResponse][n_' + rname + '][order]" value="" id="f[srv][DiscoveryResponse][n_' + rname + '][order]" size="2" maxlength="2" class="acsindex "  /></li></ol></li>';
    $(this).parent().before(newelement);

});
$("#ncontactbtn").click(function() {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<li><fieldset><ol><li><label for="f[contact][n_' + rname + '][type]">Contact type</label><span class=""><select name="f[contact][n_' + rname + '][type]"> <option value="administrative">Administrative</option> <option value="technical">Technical</option> <option value="support" selected="selected">Support</option> <option value="billing">Billing</option> <option value="other">Other</option> </select></span></li><li><label for="f[contact][n_' + rname + '][fname]">Contact first name</label><span class=""><input type="text" name="f[contact][n_' + rname + '][fname]" value="" id="f[contact][n_' + rname + '][fname]"  /></span></li><li><label for="f[contact][n_' + rname + '][sname]">Contact last name</label><span class=""><input type="text" name="f[contact][n_' + rname + '][sname]" value="" id="f[contact][n_' + rname + '][sname]"  /></span></li><li><label for="f[contact][n_' + rname + '][email]">Contact Email</label><span class=""><input type="text" name="f[contact][n_' + rname + '][email]" value="" id="f[contact][n_' + rname + '][email]"  /></span></li></ol></fieldset></li><li><fieldset></li>';
    $(this).parent().before(newelement);

});
$("#nribtn").click(function() {
    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));

    var newelement = '<li><label for="f[srv][RequestInitiator][n_' + rname + '][url]">URL</label><input name="f[srv][RequestInitiator][n_' + rname + '][url]" id="f[srv][RequestInitiator][n_' + rname + '][url]" type="text"></li>';
    $(this).parent().before(newelement);

});
$("#nidpssocert").click(function() {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    var newelement = '<li><label for="f[crt][idpsso][n' + rname + '][remove]">Please remove it</label><select name="f[crt][idpsso][n' + rname + '][remove]"> <option value="none">Keep it</option> <option value="yes">Yes, remove it</option> </select> </li><li><label for="f[crt][idpsso][n' + rname + '][type]">Certificate type</label><select name="f[crt][idpsso][n' + rname + '][type]"> <option value="x509">x509</option> </select> </li><li><label for="f[crt][idpsso][n' + rname + '][usage]">Certificate use</label><span class=""><select name="f[crt][idpsso][n' + rname + '][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </span></li><li><label for="f[crt][idpsso][n' + rname + '][keyname]">KeyName&nbsp;<span title="Multiple keynames separeated with coma(s)">?</span></label><input type="text" name="f[crt][idpsso][n' + rname + '][keyname]" value="" id="f[crt][idpsso][n' + rname + '][keyname]" class=""  /> </li><li><label for="f[crt][idpsso][n' + rname + '][certdata]">Certificate&nbsp;<span title="Paste your certificate here.">?</span></label><textarea name="f[crt][idpsso][n' + rname + '][certdata]" cols="65" rows="30" id="f[crt][idpsso][n' + rname + '][certdata]" class="certdata notice" ></textarea> </li>';
    $(this).parent().before(newelement);

});
$("#naacert").click(function() {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    var newelement = '<li><label for="f[crt][aa][n' + rname + '][remove]">Please remove it</label><select name="f[crt][aa][n' + rname + '][remove]"> <option value="none">Keep it</option> <option value="yes">Yes, remove it</option> </select> </li><li><label for="f[crt][aa][n' + rname + '][type]">Certificate type</label><select name="f[crt][aa][n' + rname + '][type]"> <option value="x509">x509</option> </select> </li><li><label for="f[crt][aa][n' + rname + '][usage]">Certificate use</label><span class=""><select name="f[crt][aa][n' + rname + '][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </span></li><li><label for="f[crt][aa][n' + rname + '][keyname]">KeyName&nbsp;<span title="Multiple keynames separeated with coma(s)">?</span></label><input type="text" name="f[crt][aa][n' + rname + '][keyname]" value="" id="f[crt][aa][n' + rname + '][keyname]" class=""  /> </li><li><label for="f[crt][aa][n' + rname + '][certdata]">Certificate&nbsp;<span title="Paste your certificate here.">?</span></label><textarea name="f[crt][aa][n' + rname + '][certdata]" cols="65" rows="30" id="f[crt][aa][n' + rname + '][certdata]" class="certdata notice" ></textarea> </li>';
    $(this).parent().before(newelement);

});
$("#nspssocert").click(function() {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    var newelement = '<li><label for="f[crt][spsso][n' + rname + '][remove]">Please remove it</label><select name="f[crt][spsso][n' + rname + '][remove]"> <option value="none">Keep it</option> <option value="yes">Yes, remove it</option> </select> </li><li><label for="f[crt][spsso][n' + rname + '][type]">Certificate type</label><select name="f[crt][spsso][n' + rname + '][type]"> <option value="x509">x509</option> </select> </li><li><label for="f[crt][spsso][n' + rname + '][usage]">Certificate use</label><span class=""><select name="f[crt][spsso][n' + rname + '][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </span></li><li><label for="f[crt][spsso][n' + rname + '][keyname]">KeyName&nbsp;<span title="Multiple keynames separeated with coma(s)">?</span></label><input type="text" name="f[crt][spsso][n' + rname + '][keyname]" value="" id="f[crt][spsso][n' + rname + '][keyname]" class=""  /> </li><li><label for="f[crt][spsso][n' + rname + '][certdata]">Certificate&nbsp;<span title="Paste your certificate here.">?</span></label><textarea name="f[crt][spsso][n' + rname + '][certdata]" cols="65" rows="30" id="f[crt][spsso][n' + rname + '][certdata]" class="certdata notice" ></textarea> </li>';
    $(this).parent().before(newelement);

});
$("a.pCookieAccept").click(function() {
    var link = $(this), url = link.attr("href");

    $.ajax({
        url: url,
        timeout: 2500,
        cache: false
    });
    $('#cookiesinfo').hide();

    return false;
});
$("[id='f[entityid]']").change(function() {
    var entalert = $("div#entitychangealert").text();
    alert(entalert);
});

// When DOM is ready
$(document).ready(function() {
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

// Launch MODAL BOX if the Login Link is clicked
    $("#login_link").click(function() {
        $('#login_form').modal();
    });
    if ($("#eds2").is('*')) {
        $("#idpSelect").modal();
    }
    $("button#vormversion").click(function() {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vormversion',
            timeout: 2500,
            success: function(data) {
                $('#spinner').hide();
                $("#rvormversion").show();
                $("tr#rvormversion td:first-child").html(data);
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                alert('Error occurred');
            },
        });
        return false;
    });
    $("button#vschema").click(function() {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vschema',
            timeout: 2500,
            success: function(data) {
                $('#spinner').hide();
                $("#rvschema").show();
                $("tr#rvschema td:first-child").html(data);
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                alert('Error ocured');
            },
        });
        return false;
    });
    $("button#vschemadb").click(function() {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vschemadb',
            timeout: 2500,
            success: function(data) {
                $('#spinner').hide();
                $("#rvschemadb").show();
                $("tr#rvschemadb td:first-child").html(data);
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                alert('Error ocured');
            },
        });
        return false;
    });
    $("button#vmigrate").click(function() {
        $.ajax({
            cache: false,
            type: "GET",
            url: baseurl + 'smanage/reports/vmigrate',
            timeout: 2500,
            success: function(data) {
                $('#spinner').hide();
                $("#rvmigrate").show();
                $("tr#rvmigrate td:first-child").html(data);
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                alert('Error ocured');
            },
        });
        return false;
    });

// When the form is submitted
    $("#status form").submit(function() {

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
            success: function(msg) {

                $("#status").ajaxComplete(function(event, request, settings) {

                    // Show 'Submit' Button
                    $('#submit').show();

                    // Hide Gif Spinning Rotator
                    $('#ajax_loading').hide();

                    if (msg == 'OK') // LOGIN OK?
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
            error: function() {
                $("#status").ajaxComplete(function(event, request, settings) {
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


    $("button#registernotification").click(function(ev) {
        ev.preventDefault();
        notificationadd('', function(ev) {
            var serializedData = $("form#notificationaddform").serializeArray();
            $.ajax({
                type: "POST",
                url: baseurl + 'notifications/subscriber/add',
                //data: serializedData,
                data: $("form#notificationaddform").serializeArray(),
                success: function(data) {
                    $(".message").html(data);
                    if (data == 'OK')
                    {
                        alert('refresh page to see updated table');

                        $.modal.close();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('Error occured: ' + errorThrown);
                    //  $(".message").html(errorThrown);
                }

            });
        });

    });

    // updatenotifactionstatus old place
    $("#idpmatrix tr td:not(:first-child)").click(function(ev) {
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
                success: function(json) {
                    if (!json)
                    {
                    }
                    else
                    {
                        var tbody_data = $('<tbody></tbody>');
                        var thdata = '<thead><th colspan="2">Current attribute flow</th></thead>';
                        $.each(json.details, function(i, v) {
                            var trdata = '<tr><td>' + v.name + '</td><td>' + v.value + '</td/></tr>';
                            tbody_data.append(trdata);
                        });
                        var tbl = $('<table/>').css({'font-size': 'smaller'}).css({'border': '1px solid'}).css({'width': '100%'}).addClass('detailsnosort');
                        ;
                        var pl = $('<div/>');
                        tbl.append(thdata);
                        tbl.append(tbody_data);
                        pl.append(tbl);
                        $("div.attrflow").replaceWith('<div class="attrflow">' + pl.html() + '</div>');

                    }  //end else
                }

            });
            idpmatrixform('', function(ev) {
                var serializedData = $("form#idpmatrixform").serializeArray();
                $.ajax({
                    type: "POST",
                    url: url,
                    data: serializedData,
                    success: function(data) {
                        if (!oko.hasClass('dis'))
                        {
                            if ((data == "2" && (cell == "R" || cell == "D")) || (data == "1" && cell == "R"))
                            {
                                oko.attr('class', 'perm');
                            }
                            else if ((data == "2c" && (cell == "R" || cell == "D")) || (data == "1c" && cell == "R"))
                            {
                                oko.attr('class', 'spec');
                            }
                            else if ((data == "1" && cell == "D") || (data == "0"))
                            {
                                oko.attr('class', 'den');
                            }
                            else if ((data == "1c" && cell == "D") || (data == "0c"))
                            {
                                oko.attr('class', 'den');
                            }

                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Error occured: ' + errorThrown);
                    }
                });
            }
            );
        }
        ;
        ev.preventDefault();
    });


    $("#rmstatdef button").click(function(ev) {
        var url = $(this).attr('action');
        var serializedData = $(this).serialize();
        sconfirm('', function(ev) {
            $.ajax({
                type: "POST",
                url: url,
                data: serializedData,
                success: function(data) {
                    $('#resultdialog').modal({
                        position: ["20%", ],
                        overlayId: 'simpledialog-overlay',
                        containerId: 'simpledialog-container',
                        closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
                    });
                },
                error: function(data) {
                    alert('Error');
                }
            });
        }
        );
        ev.preventDefault();
    });

    $("#rmfedcategory").click(function(ev) {
        var url = $(this).attr('action');
        var serializedData = $(this).serialize();
        sconfirm('', function(ev) {
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
    $("#rmfedvalidator").click(function(ev) {
        var url = $(this).attr('action');
        var serializedData = $(this).serialize();
        sconfirm('', function(ev) {
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

    function notificationadd(message, callback) {
        $('#notificationaddform').modal({
            closeHTML: "<a href='#' title='Close' class='modal-close'>x</a>",
            position: ["20%", ],
            overlayId: 'simpledialog-overlay',
            minHeight: '400px',
            minWidth: '500px',
            containerId: 'simpledialog-container',
            onOpen: function(dialog) {
                dialog.overlay.fadeIn('fast', function() {
                    dialog.container.slideDown('fast', function() {
                        dialog.data.fadeIn('fast');
                    });
                });
            },
            onShow: function(dialog) {
                $('select#sfederation').parent().hide();
                $('select#sprovider').parent().hide();
                $('select#type').change(function() {
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
                            success: function(json) {
                                $.each(json, function(key, value) {
                                    $('<option>').val(value.id).text(value.name).appendTo(selfed);
                                });
                                $('select#sfederation').parent().show();

                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                $(".message").html(errorThrown);
                            }

                        });
                    }
                    else if(valueSelected === "requeststoproviders" )
                    {
                        $.ajax({
                            type: "GET",
                            url: baseurl + 'ajax/getproviders',
                            cache: false,
                            //datatype: "json",
                            success: function(json) {
                                var data = $.parseJSON(json);
                                $.each(data, function(key, value) {
                                    $('<option>').val(value.key).text(value.value).appendTo(selprovider);
                                });
                                $('select#sprovider').parent().show();

                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                $(".message").html(errorThrown);
                            }

                        });

                   }
                }); // end change  function
                var modal = this;
                $('.message', dialog.data[0]).append(message);
                $('.yes', dialog.data[0]).click(function() {
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
            containerId: 'simpledialog-container',
            onShow: function(dialog) {
                var modal = this;
                $('.message', dialog.data[0]).append(message);
                $('.yes', dialog.data[0]).click(function() {
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
            overlayId: 'simpledialog-overlay',
            containerId: 'simpledialog-container',
            onShow: function(dialog) {
                var modal = this;

                $('.message', dialog.data[0]).append(message);

                // if the user clicks "yes"
                $('.yes', dialog.data[0]).click(function() {
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

$("button#parsemetadatasp").click(function() {
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

    xml.find("md\\:SPSSODescriptor,SPSSODescriptor").each(function() {
        if ($(this).attr("protocolSupportEnumeration"))
        {
            $entity = $(this).parent();
            return false;
        }
        return true;
    });
    if ($entity === null)
    {
        alert("SP not found");
        return false;
    }
    $("#entityid").val($entity.attr("entityID"));
    $orgname = $entity.find("md\\:OrganizationName,OrganizationName");
    $orgdisname = $entity.find("md\\:OrganizationDisplayName,OrganizationDisplayName");
    $helpdeskurl = $entity.find("md\\:OrganizationURL,OrganizationURL");
    $("#resource").val($orgname.text());
    $("#descresource").val($orgdisname.text());
    $("#helpdeskurl").val($helpdeskurl.text());
    $("#homeurl").val($helpdeskurl.text());
    $entity.find("md\\:AssertionConsumerService,AssertionConsumerService").each(function() {
        if ($(this).attr("isDefault"))
        {
            $("#acs_url").val($(this).attr("Location"));
            $("#acs_order").val($(this).attr("index"));
            $('#acs_bind').val($(this).attr('Binding'));
            return false;
        }
        else
        {
            if ($(this).attr('Binding') === "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST")
            {
                $("#acs_url").val($(this).attr("Location"));
                $("#acs_order").val($(this).attr("index"));
                $('#acs_bind').val($(this).attr('Binding'));
            }
        }
    });

    var nameids = '';
    $entity.find("md\\:NameIDFormat, NameIDFormat").each(function() {
        nameids = nameids + ' ' + $(this).text();
    });

    $("#nameids").val(nameids);

    var certsign = false;
    var certenc = false;
    $entity.find("md\\:KeyDescriptor, KeyDescriptor").each(function() {
        if (!certsign || !certenc)
        {
            if ($(this).attr("use") === "signing")
            {
                if (!certsign)
                {
                    var cert = $(this).find("ds\\:X509Certificate");
                    $("#sign_cert_body").val(cert.text());
                    certsign = true;
                }
            }
            else if ($(this).attr("use") === "encryption")
            {
                if (!certenc)
                {
                    var cert = $(this).find("ds\\:X509Certificate");
                    $("#encrypt_cert_body").val(cert.text());
                    certenc = true;
                }
            }
            else
            {
                var cert = $(this).find("ds\\:X509Certificate");
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
});

//  spregister

var current_fs, next_fs, previous_fs;
var left, opacity, scale;
var animating;
var index2;
var addheight = $("#progressbar").height() + 30;
var fieldsetheight = $("#multistepform fieldset").height() + addheight;
$("form#multistepform").css({'height': fieldsetheight});
$(".next").click(function() {
    var canproceed = true;
    $(this).parent().find('input.required').each(function() {
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
        step: function(now, mx) {
            scale = 1 - (1 - now) * 0.2;
            left = (now * 50) + "%";
            opacity = 1 - now;
            current_fs.css({'transform': 'scale(' + scale + ')'});
            next_fs.css({'left': left, 'opacity': opacity});
        },
        duration: 200,
        complete: function() {
            current_fs.hide();
            animating = false;
        },
    });
});

$(".previous").click(function() {
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
        step: function(now, mx) {
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
        complete: function() {
            current_fs.hide();
            animating = false;
        },
        //this comes from the custom easing plugin
        //easing: 'easeInOutBack'
    });
});

$(".submit").click(function() {
    return false;
})

$('#joinfed select#fedid').on('change', function() {
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
            success: function(json) {
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
                    // GINIT.initialize();
                }
            },
            beforeSend: function() {
                $('#spinner').show();
            },
            error: function() {
                $('#spinner').hide();
                $('#fvform').hide();
                $('#fvresult').hide();
            }
        }).done(function() {
        })
    }
});
