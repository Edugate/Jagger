
// When DOM is ready
$(document).ready(function(){
var baseurl = $("[name='baseurl']").val()
if(baseurl == undefined)
{
   baseurl = '';
}
// Preload Images
img1 = new Image(16, 16);  
img1.src = baseurl + 'images/spinner.gif';

img2 = new Image(220, 19);  
img2.src = baseurl + 'images/ajax-loader.gif';

// Launch MODAL BOX if the Login Link is clicked
$("#login_link").click(function(){
   $('#login_form').modal();
});
if ($("#eds2").is('*')) {
   $("#idpSelect").modal();
}


// When the form is submitted
$("#status form").submit(function(){  

// Hide 'Submit' Button
$('#submit').hide();

// Show Gif Spinning Rotator
$('#ajax_loading').show();

// 'this' refers to the current submitted form  
var str = $(this).serialize();  

// -- Start AJAX Call --

$.ajax({  
    type: "POST",
    url: baseurl + 'authenticate/dologin',  // Send the login info to this page
    data: str,  
    success: function(msg){  
   
$("#status").ajaxComplete(function(event, request, settings){  
 
 // Show 'Submit' Button
$('#submit').show();

// Hide Gif Spinning Rotator
$('#ajax_loading').hide();  

 if(msg == 'OK') // LOGIN OK?
 {  
 var login_response = '<div id="logged_in">' +
	 '<div style="width: 350px; float: left; margin-left: 70px;">' + 
	 '<div style="width: 40px; float: left;">' +
	 '<img style="margin: 10px 0px 10px 0px;" align="absmiddle" src="'+baseurl+'images/ajax-loader.gif">' +
	 '</div>' +
	 '<div style="margin: 10px 0px 0px 10px; float: right; width: 300px;">'+ 
	 "You are successfully logged in! <br /> Please wait while you're redirected...</div></div>";  

$('a.modalCloseImg').hide();  

$('#simplemodal-container').css("width","auto").css("height","auto").css("background","transparent").css("box-shadow","none").css("text-align","center");
 
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
   
 }  
   
  });  
  
// -- End AJAX Call --

return false;

}); // end submit event

});

function go_to_private_page()
{
//window.location.assign(baseurl+'dashboard');
//window.location.replace(baseurl+'dashboard');
//var urltoredirect  = baseurl+ 'dashboard';
//var url2toredicter = urltoredirect.replace (/^[a-z]{4}\:\/{2}[a-z]{1,}\:[0-9]{1,4}.(.*)/, '$1');
//alert(url2toredicter);
window.location = '';
//window.location = 'dashboard'; // Members Area
}
