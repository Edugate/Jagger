$(function() {
    $( "#validfrom" ).datepicker({ dateFormat: 'yy-mm-dd' });
});
$(function() {
    $( "#validto" ).datepicker({ dateFormat: 'yy-mm-dd' });
});

$(document).ready(function() {
 	 $("#responsecontainer").load("awaiting/ajaxrefresh");
   var refreshId = setInterval(function() {
      $("#responsecontainer").load('awaiting/ajaxrefresh');
   }, 9000);
   $.ajaxSetup({ cache: false });
});

$(function() {
   $( "#sortable" ).sortable();
   $( "#sortable" ).disableSelection();
});

$(document).ready(function() {
	 	
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
