$(function(){
    //original field values
    var field_values = {
            //id        :  value
            'name'  : 'name',
            'entityname'  : 'entityname',
            'homeurl'  : 'homeurl',
            'helpdeskurl'  : 'helpdeskurl'
    };

    //inputfocus
    $('input#name').inputfocus({ value: field_values['name'] });
    $('input#entityname').inputfocus({ value: field_values['entityname'] });
    $('input#homeurl').inputfocus({ value: field_values['homeurl'] });
    $('input#helpdeskurl').inputfocus({ value: field_values['helpdeskurl'] });

    //reset progress bar
    $('#progress').css('width','0');
    $('#progress_text').html('0% Complete');

    //first_step
    $('form').submit(function(){ return false; });
    $('#submit_step_1').click(function(){
        //remove classes
        $('#step_1 input').removeClass('error').removeClass('valid');

        //ckeck if inputs aren't empty
        var fields = $('#step_1 input[type=text], #step_1 input[type=password]');
        var error = 0;
        fields.each(function(){
            var value = $(this).val();
            if( value.length<4 || value==field_values[$(this).attr('id')] ) {
                $(this).addClass('error');
                $(this).effect("shake", { times:3 }, 50);

                error++;
            } else {
                $(this).addClass('valid');
            }
        });

        if(!error) {
            if( $('#password').val() != $('#cpassword').val() ) {
                    $('#first_step input[type=password]').each(function(){
                        $(this).removeClass('valid').addClass('error');
                        $(this).effect("shake", { times:3 }, 50);
                    });

                    return false;
            } else {
                //update progress bar
                $('#progress_text').html('33% Complete');
                $('#progress').css('width','113px');

                //slide steps
                $('#step_1').slideUp();
                $('#step_2').slideDown();
            }
        } else return false;
    });

    $('#submit_step_2').click(function(){
        //remove classes
        $('#step_2 input').removeClass('error').removeClass('valid');

        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
        var fields = $('#step_2 input[type=text]');
        var error = 0;
        fields.each(function(){
            var value = $(this).val();
            if( value.length<1 || value==field_values[$(this).attr('id')] || ( $(this).attr('id')=='email' && !emailPattern.test(value) ) ) {
                $(this).addClass('error');
                $(this).effect("shake", { times:3 }, 50);

                error++;
            } else {
                $(this).addClass('valid');
            }
        });

        if(!error) {
                //update progress bar
                $('#progress_text').html('66% Complete');
                $('#progress').css('width','226px');

                //slide steps
                $('#step_2').slideUp();
                $('#step_3').slideDown();
        } else return false;

    });

    $('#submit_step_3').click(function(){
        //update progress bar
        $('#progress_text').html('100% Complete');
        $('#progress').css('width','339px');

        //prepare the fourth step
        var fields = new Array(
            $('#name').val(),
            $('#entityname').val(),
            $('#homeurl').val(),
            $('#helpdeskurl').val(),
            $('#country').val()
        );
        var tr = $('#step_4 tr');
        tr.each(function(){
            //alert( fields[$(this).index()] )
            $(this).children('td:nth-child(2)').html(fields[$(this).index()]);
        });

        //slide steps
        $('#step_3').slideUp();
        $('#step_4').slideDown();
    });

    $('#submit_step_4').click(function(){
        //send information to server
        alert('Data sent');
    });

});

