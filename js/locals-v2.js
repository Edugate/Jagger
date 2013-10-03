var GINIT = {
   initialize: function(){
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
 var bubbletheme = $("button#jquerybubblepopupthemes").val();
$('.bubblepopup').CreateBubblePopup({
  
   position : 'top',
   align    : 'center',
   innerHtml: 'click help',
   innerHtmlStyle: {
       color:'#FFFFFF',
       'text-align':'center'
       },
   themeName: 'all-black',
   themePath: bubbletheme

});
$('.bubblepopup').mouseover(function() {
     var some = $(this).val();
     $(this).ShowBubblePopup({
     closingDelay: 1000,
     position : 'top',
     align    : 'center',
     innerHtml: some,

    innerHtmlStyle: {
         color:'#000000', 
         'font-size': '110%',
        'text-align':'left'
                   },

  themeName: 'all-yellow',
  themePath: bubbletheme
   },false).FreezeBubblePopup()

  }); 

});
//$("#login").hide();
$("button#loginbtn").click(function(){
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
   $('#langchenge select').on('change', function() {
       var link = document.getElementById('langurl').innerHTML;
       var url = link+this.value;
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false
        }).done(function(){ setTimeout('go_to_private_page()', 1000);});
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
            success: function(data){
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
            success: function(data){
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
           success: function(json){
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
              $("div#statisticdiag").replaceWith('<div id="statisticdiag"></a>');  
                   $.each(data, function(i, v) {
                      
                        i = new Image();
                        i.src = v.url;
                  $('#statisticdiag').append('<div style="text-align:center; font-weight: bold; width: 90%;">'+v.title+'</div>').append('<div style="font-weight: bolder; width: 90%; text-align: right;">'+v.subtitle+'</div>').append(i);

                     });
                }

                //   i = new Image();
                //   i.src = url;
                 // $('#statisticdiag').html('<img src="'+url+'/'+Math.floor(Math.random()*1000)+'" />');
                  //$('#statisticdiag').replaceWith(i);
                   i = null;


           },
           beforeSend: function(){  $('#spinner').show(); },
           error: function() {
                       $('#spinner').hide();
                       alert('problem with loading data');
                 }

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
            beforeSend: function() { $('#spinner').show(); },
                error: function() {
                    $('#spinner').hide();
                    alert('problem with loading data');
                }
            
          }).done(function(){
                var nextrow =  value.html() ;
                //$(nextrow).insertAfter(row);
                $("div#membership").replaceWith(nextrow);
               
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
            beforeSend: function() { $('#spinner').show(); },
                error: function() {
                    $('#spinner').hide();
                    alert('problem with loading data');
                }
            
          }).done(function(){
                var nextrow =  value.html() ;
                //$(nextrow).insertAfter(row);
                $("div#syncresult").replaceWith(nextrow);
               
         });
          return false;
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
                cache: false,
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
        catch(err)
        {
            return false;
        }
        if($('#submenuProvider').length)
        {
            var sticky_submenu_offset_top = $('#submenuProvider').offset().top;
        }
	
	// our function that decides weather the navigation bar should have "fixed" css position or not.
	var sticky_navigation = function(){
		var scroll_top = $(window).scrollTop(); // our current vertical position from the top
		var widthelement = $('nav').width();
		// if we've scrolled more than the navigation, change its position to fixed to stick to top, otherwise change it back to relative
		if (scroll_top > sticky_navigation_offset_top) { 
			//$('nav').css({ 'position': 'fixed', 'top':0, 'left':0, 'width': '100%','zIndex':9999});
                          $('nav').css({ 'position': 'fixed', 'top':0,'width':widthelement ,'zIndex':9999});
                          
		} else {
			$('nav').css({ 'position': 'relative','width':'auto', }); 
		}   
                if(sticky_submenu_offset_top != 'undefined')
                {
		    if (scroll_top > sticky_submenu_offset_top) { 
		    	$('#submenuProvider').css({ 'position': 'fixed', 'top':50, 'left':0, 'width': '100%','zIndex':9999,'clear': 'both'});
		    } else {
			$('#submenuProvider').css({ 'position': 'relative' }); 
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
    $("#options").tablesorter({sortList: [[0, 0]], headers: {3: {sorter: false}, 4: {sorter: false}}});
    $("#formtabs").tabs();
    $("#providertabs").tabs({
        cache:true,
         load: function (event, ui) {
       $('.accordionButton').unbind();
       GINIT.initialize();
        }

    });
    $("#arptabs").tabs({
        cache:true,
         load: function (event, ui) {
       $('.accordionButton').unbind();
       $('.tablesorter').unbind();
       GINIT.initialize();
        }

    });
});
if($('#usepredefined').attr('checked')) {
     $("fieldset#stadefext").hide();
}
$("#usepredefined").click(function(){
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
    var newelement = '<li><label for="f[crt][idpsso][n'+rname+'][remove]">Please remove it</label><select name="f[crt][idpsso][n'+rname+'][remove]"> <option value="none">Keep it</option> <option value="yes">Yes, remove it</option> </select> </li><li><label for="f[crt][idpsso][n'+rname+'][type]">Certificate type</label><select name="f[crt][idpsso][n'+rname+'][type]"> <option value="x509">x509</option> </select> </li><li><label for="f[crt][idpsso][n'+rname+'][usage]">Certificate use</label><span class=""><select name="f[crt][idpsso][n'+rname+'][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </span></li><li><label for="f[crt][idpsso][n'+rname+'][keyname]">KeyName&nbsp;<span title="Multiple keynames separeated with coma(s)">?</span></label><input type="text" name="f[crt][idpsso][n'+rname+'][keyname]" value="" id="f[crt][idpsso][n'+rname+'][keyname]" class=""  /> </li><li><label for="f[crt][idpsso][n'+rname+'][certdata]">Certificate&nbsp;<span title="Paste your certificate here.">?</span></label><textarea name="f[crt][idpsso][n'+rname+'][certdata]" cols="65" rows="30" id="f[crt][idpsso][n'+rname+'][certdata]" class="certdata notice" ></textarea> </li>';
    $(this).parent().before(newelement);

});
$("#naacert").click(function() {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    var newelement = '<li><label for="f[crt][aa][n'+rname+'][remove]">Please remove it</label><select name="f[crt][aa][n'+rname+'][remove]"> <option value="none">Keep it</option> <option value="yes">Yes, remove it</option> </select> </li><li><label for="f[crt][aa][n'+rname+'][type]">Certificate type</label><select name="f[crt][aa][n'+rname+'][type]"> <option value="x509">x509</option> </select> </li><li><label for="f[crt][aa][n'+rname+'][usage]">Certificate use</label><span class=""><select name="f[crt][aa][n'+rname+'][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </span></li><li><label for="f[crt][aa][n'+rname+'][keyname]">KeyName&nbsp;<span title="Multiple keynames separeated with coma(s)">?</span></label><input type="text" name="f[crt][aa][n'+rname+'][keyname]" value="" id="f[crt][aa][n'+rname+'][keyname]" class=""  /> </li><li><label for="f[crt][aa][n'+rname+'][certdata]">Certificate&nbsp;<span title="Paste your certificate here.">?</span></label><textarea name="f[crt][aa][n'+rname+'][certdata]" cols="65" rows="30" id="f[crt][aa][n'+rname+'][certdata]" class="certdata notice" ></textarea> </li>';
    $(this).parent().before(newelement);

});
$("#nspssocert").click(function() {

    var rname = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++)
        rname += possible.charAt(Math.floor(Math.random() * possible.length));
    var newelement = '<li><label for="f[crt][spsso][n'+rname+'][remove]">Please remove it</label><select name="f[crt][spsso][n'+rname+'][remove]"> <option value="none">Keep it</option> <option value="yes">Yes, remove it</option> </select> </li><li><label for="f[crt][spsso][n'+rname+'][type]">Certificate type</label><select name="f[crt][spsso][n'+rname+'][type]"> <option value="x509">x509</option> </select> </li><li><label for="f[crt][spsso][n'+rname+'][usage]">Certificate use</label><span class=""><select name="f[crt][spsso][n'+rname+'][usage]"> <option value="signing">signing</option> <option value="encryption">encryption</option> <option value="both" selected="selected">signing and encryption</option> </select> </span></li><li><label for="f[crt][spsso][n'+rname+'][keyname]">KeyName&nbsp;<span title="Multiple keynames separeated with coma(s)">?</span></label><input type="text" name="f[crt][spsso][n'+rname+'][keyname]" value="" id="f[crt][spsso][n'+rname+'][keyname]" class=""  /> </li><li><label for="f[crt][spsso][n'+rname+'][certdata]">Certificate&nbsp;<span title="Paste your certificate here.">?</span></label><textarea name="f[crt][spsso][n'+rname+'][certdata]" cols="65" rows="30" id="f[crt][spsso][n'+rname+'][certdata]" class="certdata notice" ></textarea> </li>';
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
$("#eds").click(function() {
        $(this).parent().append('<iframe src="https://oliwa.heanet.ie/rr3/eds?entityID=https%3A%2F%2Foliwa.heanet.ie%2Fshibboleth&return=https%3A%2F%2Foliwa.heanet.ie%2FShibboleth.sso%2FLogin%3FSAMLDS%3D1%26target%3Dss%253Amem%253A838381405f4006a7c8db7aea545d696400f5ed55" width="160" height="90" frameborder="0"></iframe>');
        });
 
