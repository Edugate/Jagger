$('button[name="potwierdz"]').click(function(ev){
       ev.preventDefault();
       var message= "message";
        $('#potwierdzenie').foundation('reveal','open',{});
       potwierdzenie(message,function(ev){
             alert("call");
            // $(this).unbind("ev");
       });

    });

function potwierdzenie(message,callback){
          $('#confirmdialog').foundation('reveal','open',{});
          $(document).on('opened.fndtn.reveal', '#confirmdialog', function () {
             $(".yes").click(function(){
                alert(message);
                callback.call();
                $('[data-reveal]').foundation('reveal','close');
              //  $(this).foundation('close');

             });
             $(".no").click(function(){
                $('#confirmdialog').foundation('reveal','close');
               });
          });

};

$('button[name="fedstatus"]').click(function(ev){
       ev.preventDefault();
    alert("lkkkkkkkk");
    var btnVal = $(this).attr('value');
    var additionalMsg = $(this).attr('title');
    if( additionalMsg === undefined)
    {
        additionalMsg ='';
    }
    var csrfname = $("[name='csrfname']").val();
    var csrfhash = $("[name='csrfhash']").val();
    var baseurl = $("[name='baseurl']").val();
    var fedname = $("span#fednameencoded").text();
    var url = baseurl+'federations/manage/changestatus';
    var data = [{name: 'status', value: btnVal},{name: csrfname, value: csrfhash},{name: 'fedname', value: fedname }];
    potwierdzenie(''+additionalMsg+'', function(ev) {
         $.ajax({
            type: "POST",
            url: url,
            data: data,
            success: function(data){
                if(data){
                  if(data =='deactivated')
                  {
                      $('button[value="disablefed"]').hide();   
                      $('button[value="enablefed"]').show();   
                      $('button[value="delfed"]').show();   
                      $('td.fedstatusinactive').show();
                      $('show.fedstatusinactive').show();
                  }
                  else if(data =='activated')
                  {
                      $('button[value="disablefed"]').show();   
                      $('button[value="enablefed"]').hide();   
                      $('button[value="delfed"]').hide();   
                      $('td.fedstatusinactive').hide();
                      $('span.fedstatusinactive').hide();
                  }
                  else if(data =='todelete')
                  {
                      $('button[value="disablefed"]').hide();   
                      $('button[value="enablefed"]').hide();   
                      $('button[value="delfed"]').hide();   
                  }
                   
                }
             },
             error: function(data) {
                alert('Error  ocurred');
             }

         });
         });
    });
