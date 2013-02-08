$(function() {


/*************/


		$.widget( "ui.combobox", {
			_create: function() {
				var self = this,
					select = this.element.hide(),
					selected = select.children( ":selected" ),
					value = selected.val() ? selected.text() : "";
				var input = this.input = $( "<input>" )
					.insertAfter( select )
					.val( value )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: function( request, response ) {
							var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
							response( select.children( "option" ).map(function() {
								var text = $( this ).text();
								if ( this.value && ( !request.term || matcher.test(text) ) )
									return {
										label: text.replace(
											new RegExp(
												"(?![^&;]+;)(?!<[^<>]*)(" +
												$.ui.autocomplete.escapeRegex(request.term) +
												")(?![^<>]*>)(?![^&;]+;)", "gi"
											), "<strong>$1</strong>" ),
										value: text,
										option: this
									};
							}) );
						},
						select: function( event, ui ) {
							ui.item.option.selected = true;
							self._trigger( "selected", event, {
								item: ui.item.option
							});
						},
						change: function( event, ui ) {
							if ( !ui.item ) {
								var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
									valid = false;
								select.children( "option" ).each(function() {
									if ( $( this ).text().match( matcher ) ) {
										this.selected = valid = true;
										return false;
									}
								});
								if ( !valid ) {
									// remove invalid value, as it didn't match anything
									$( this ).val( "" );
									select.val( "" );
									input.data( "autocomplete" ).term = "";
									return false;
								}
							}
						}
					})
					.addClass( "ui-widget ui-widget-content ui-corner-left" );

				input.data( "autocomplete" )._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>" + item.label + "</a>" )
						.appendTo( ul );
				};

				this.button = $( "<button type='button'>&nbsp;</button>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Show All Items" )
					.insertAfter( input )
					.button({
						icons: {
							primary: "ui-icon-triangle-1-s"
						},
						text: false
					})
					.removeClass( "ui-corner-all" )
					.addClass( "ui-corner-right ui-button-icon" )
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						$( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			},

			destroy: function() {
				this.input.remove();
				this.button.remove();
				this.element.show();
				$.Widget.prototype.destroy.call( this );
			}
		});

		$( "#combobox" ).combobox();
		$( "#toggle" ).click(function() {
			$( "#combobox" ).toggle();
		});
/****************/

        $('div.floating-menu').addClass('mobilehidden');
        $('table.idplist tr td:first-child').addClass('homeorg');      
        $('table.idplist tr td:first-child span.alert').removeClass('alert').parent().addClass('alert');
        var theTable1 = $('table.idplist')
        theTable1.find("tbody > tr").find("td:eq(1)").mousedown(function(){
        });
        $("#filter").keyup(function() {
            $.uiTableFilter( theTable1, this.value );
        })
        $('#filter-form').submit(function(){
            theTable1.find("tbody > tr:visible > td:eq(1)").mousedown();
            return false;
        }).focus(); 

        $('table.splist tr td:first-child span.alert').removeClass('alert').parent().addClass('alert');
        var theTable2 = $('table.splist')
        theTable2.find("tbody > tr").find("td:eq(1)").mousedown(function(){
        });
        $("#filter").keyup(function() {
            $.uiTableFilter( theTable2, this.value );
        })
        $('#filter-form').submit(function(){
            theTable2.find("tbody > tr:visible > td:eq(1)").mousedown();
            return false;
        }).focus(); 



    $( "#validfrom" ).datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $( "#validto" ).datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $( "#registerdate" ).datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#responsecontainer").load("awaiting/ajaxrefresh");
    var refreshId = setInterval(function() {
        $("#responsecontainer").load('awaiting/ajaxrefresh');
    }, 9000);
    $("#dashresponsecontainer").load("reports/awaiting/dashajaxrefresh");
    var refreshId = setInterval(function() {
        $("#dashresponsecontainer").load('reports/awaiting/dashajaxrefresh');
    }, 9000);

    $.ajaxSetup({
        cache: false
    });
   
    $.ajaxSetup ({
        cache: false
    });  	
    $("a.langset").click(function(){
        var link = $(this), url = link.attr("href");
        
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false
        });
        
        return false;   
    });
    $("button#addlname").click(function(){
        var nf = $("li.addlname option:selected").val();
        var nfv = $("li.addlname option:selected").text();
        $("li.addlname option[value="+nf+"]").remove();
        $(this).parent().append("<li class=\"localized\"><label for=\"lname["+nf+"]\">Name in "+nfv+" </label><input id=\"lname["+nf+"]\" name=\"lname["+nf+"]\" type=\"text\"/></li>");
    });
    $("button#addldisplayname").click(function(){
        var nf = $("li.addldisplayname option:selected").val();
        var nfv = $("li.addldisplayname option:selected").text();
        $("li.addldisplayname option[value="+nf+"]").remove();
        $(this).parent().append("<li class=\"localized\"><label for=\"ldisplayname["+nf+"]\">DisplayName in "+nfv+" </label><input id=\"ldisplayname["+nf+"]\" name=\"ldisplayname["+nf+"]\" type=\"text\"/></li>");
    });
    $("button#addlhelpdeskurl").click(function(){
        var nf = $("li.addlhelpdeskurl option:selected").val();
        var nfv = $("li.addlhelpdeskurl option:selected").text();
        $("li.addlhelpdeskurl option[value="+nf+"]").remove();
        $(this).parent().append("<li class=\"localized\"><label for=\"lhelpdeskurl["+nf+"]\">HelpdeskURL in "+nfv+" </label><input id=\"lhelpdeskurl["+nf+"]\" name=\"lhelpdeskurl["+nf+"]\" type=\"text\"/></li>");
    });
    $("button#addlprivacyurl").click(function(){
        var nf = $("li.addlprivacyurl option:selected").val();
        var nfv = $("li.addlprivacyurl option:selected").text();
        $("li.addlprivacyurl option[value="+nf+"]").remove();
        $(this).parent().append("<li class=\"localized\"><label for=\"lprivacyurl["+nf+"]\">Privacy Statement URL in "+nfv+" </label><input id=\"lprivacyurl["+nf+"]\" name=\"lprivacyurl["+nf+"]\" type=\"text\"/></li>");
    });
    $("button#addldescription").click(function(){
        var nf = $("li.addldescription option:selected").val();
        var nfv = $("li.addldescription option:selected").text();
        $("li.addldescription option[value="+nf+"]").remove();
        $(this).parent().append("<li class=\"localized\"><label for=\"ldescription["+nf+"]\">Description in "+nfv+" </label><textarea id=\"ldescription["+nf+"]\" name=\"ldescription["+nf+"]\" rows=\"5\" cols=\"40\"/></textarea></li>");
    });
    $("a.bookentity").click(function(){
        var link = $(this), url = link.attr("href");
        
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false
        });  
        return false;   
    });
    $("a.delbookentity").click(function(){
        var link = $(this), url = link.attr("href");
        
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).parent().remove()
        });  
        return false;   
    });
     $("a.delbookfed").click(function(){
        var link = $(this), url = link.attr("href");
        
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false,
            success: $(this).parent().remove()
        });  
        return false;   
    });
    $("a.fmembers").click(function(){
        var link = $(this), url = link.attr("href");
        var row = $(this).parent().parent();
        if($(row).hasClass('opened') == true)
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
                success: function(json){            
                    $('#spinner').hide();
                    var data = $.parseJSON(json);
                    $(row).addClass('opened').addClass('highlight');
                    if(!data)
                    {
                        alert('no data');
                    }
                    else
                    {
                        if(!data.idp && !data.sp && !data.both)
                        {
                            var div_data = '<li> no members</li>';
                            value.append(div_data);
                        }
                        else
                        {
                            if(data.idp)
                            {
                                var stitle = $('<li>Identity Providers</li>');
                                var nlist = $('<ol/>');
                                $.each(data.idp , function(i,v){
                                    var div_data = '<li class="homeorg"><a href="'+v.url+'">'+ v.name + '</a> ('+v.entityid +') </li>';
                                    nlist.append(div_data);
                                });
                                stitle.append(nlist);
                                value.append(stitle);
                            }
                            if(data.sp)
                            {
                                var stitle = $('<li>Service Providers</li>');
                                var nlist = $('<ol/>');
                                $.each(data.sp , function(i,v){
                                    var div_data = '<li class="resource"><a href="'+v.url+'">'+ v.name +'</a> ('+v.entityid +') </li>';
                                    nlist.append(div_data);
                                });
                                stitle.append(nlist);
                                value.append(stitle);
                            }
                            if(data.both)
                            {
                                var stitle = $('<li>Services are both IdP and SP</li>');
                                var nlist = $('<ol/>');
                                $.each(data.both , function(i,v){
                                    var div_data = '<li class="both"><a href="'+v.url+'">'+ v.name +'</a> ('+v.entityid +') </li>';
                                    nlist.append(div_data);
                                }); 
                                stitle.append(nlist);
                                value.append(stitle);
                            }
                        }
                    }
                    
               
                },
                beforeSend: function(){
                    $('#spinner').show();
                },
                error: function(){
                    $('#spinner').hide();
                    alert('problem with loading data');
                }
            }).done(function(){
                var nextrow = '<tr class="feddetails"><td colspan="7"><ul class="feddetails">'+value.html()+'</ul></td></tr>';  
                $(nextrow).insertAfter(row);
            }
            )
        }
        
        return false;
        
        
        
        
         
    });
    $( 'table.reqattraddform').addClass('hidden');
    $( 'button.hideform').addClass('hidden');
    $( 'form.reqattraddform').addClass('hidden');
    
    $('button.showform').click(function(){
        $( 'table.reqattraddform').removeClass('hidden');
        $( 'form.reqattraddform').removeClass('hidden');
        $( 'button.showform').addClass('hidden');
        $( 'button.hideform').removeClass('hidden');
     });
    $('button.hideform').click(function(){
        $( 'table.reqattraddform').addClass('hidden');
        $( 'form.reqattraddform').addClass('hidden');
        $( 'button.showform').removeClass('hidden');
        $( 'button.hideform').addClass('hidden');
     });
    $( "#sortable" ).sortable();
    $( "#sortable" ).disableSelection();
	 	
    $('.accordionButton').addClass('off');
    $('.accordionButton1').addClass('on');

    //  var icons = $( ".accordionButton" ).accordion( "option", "icons" );
    //   $( ".accordionButton" ).accordion( "option", "icons", { "header": "ui-icon-plus", "headerSelected": "ui-icon-minus" } );
    //ACCORDION BUTTON ACTION (ON CLICK DO THE FOLLOWING)
    $('.accordionButton').click(function() {
           

        //REMOVE THE ON CLASS FROM ALL BUTTONS
        $('.accordionButton').removeClass('on');
        $('.accordionButton').addClass('off');
        //NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
        $('.accordionContent').slideUp('fast');
   
        //IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
        if($(this).next().is(':hidden') == true) {
			
            //ADD THE ON CLASS TO THE BUTTON
            $(this).addClass('on');
            $(this).removeClass('off');
                  
			  
            //OPEN THE SLIDE
            $(this).next().slideDown('fast');
        } 
		  
    });
    $('.accordionButton1').click(function() {
           

        //REMOVE THE ON CLASS FROM ALL BUTTONS
        $('.accordionButton1').removeClass('on');
        $('.accordionButton1').addClass('off');
        //NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
        $('.accordionContent1').slideUp('fast');
   
        //IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
        if($(this).next().is(':hidden') == true) {
			
            //ADD THE ON CLASS TO THE BUTTON
            $(this).addClass('on');
            $(this).removeClass('off');
                  
			  
            //OPEN THE SLIDE
            $(this).next().slideDown('fast');
        } 
		  
    });
	  
	
    /*** REMOVE IF MOUSEOVER IS NOT REQUIRED ***/
	
    //ADDS THE .OVER CLASS FROM THE STYLESHEET ON MOUSEOVER 
    $('.accordionButton').mouseover(function() {
        $(this).addClass('over');
		
    //ON MOUSEOUT REMOVE THE OVER CLASS
    }).mouseout(function() {
        $(this).removeClass('over');										
    });

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
    $('.accordionContent').hide();
//	$('.accordionContent1').toggle();

});


var ww = document.body.clientWidth;

$(document).ready(function() {
	$(".nav li a").each(function() {
		if ($(this).next().length > 0) {
			$(this).addClass("parent");
		};
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
        $("#details").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
        $("#options").tablesorter({sortList: [[0,0]], headers: { 3:{sorter: false}, 4:{sorter: false}}});
    }); 

