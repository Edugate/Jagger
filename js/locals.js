$(function() {
    $( "#validfrom" ).datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $( "#validto" ).datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $( "#registerdate" ).datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#details").tablesorter({
        sortList:[[0,0],[2,1]], 
        widgets: ['zebra']
    });
    $("#options").tablesorter({
        sortList: [[0,0]], 
        headers: {
            3:{
                sorter: false
            }, 
            4:{
                sorter: false
            }
        }
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
    $("a.bookentity").click(function(){
        var link = $(this), url = link.attr("href");
        
        $.ajax({
            url: url,
            timeout: 2500,
            cache: false
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
                        
                                $.each(data.idp , function(i,v){
                                    var div_data = '<li class="homeorg"><a href="'+v.url+'">'+ v.name + '</a> ('+v.entityid +') </li>';
                                    value.append(div_data);
                                });
                                value.append('<li/>');
                            }
                            if(data.sp)
                            {
                                $.each(data.sp , function(i,v){
                                    var div_data = '<li class="resource"><a href="'+v.url+'">'+ v.name +'</a> ('+v.entityid +') </li>';
                                    value.append(div_data);
                      
                                });
                                value.append('<li/>');
                            }
                            if(data.both)
                            {
                                $.each(data.both , function(i,v){
                                    var div_data = '<li class="both"><a href="'+v.url+'">'+ v.name +'</a> ('+v.entityid +') </li>';
                                    value.append(div_data);
                             
                                }); 
                                value.append('<li/>');
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
                var nextrow = '<tr class="feddetails"><td colspan="7"><p>'+value.html()+'</p></td></tr>';  
                $(nextrow).insertAfter(row);
            }
            )
        }
        
        return false;
        
        
        
        
         
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
