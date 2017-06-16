var loginDialog;

funcStack.loginDialog = function () {
    
    loginDialog.originalText = $('#loginDialogMessage').html();

    loginDialog.dialog = $('#loginDialog').dialog({
        autoOpen: false,
        width: 350,
        modal: true,
        close: function() {
            if (! loginDialog.loginSuccessful) {
               window.location.replace(jsVars['urls']['loginPage']);
            }
            // Reset the login flag
            loginDialog.loginSuccessful = false;
        },
        buttons: {
            Login: loginDialog.submit
        }
    });    

    loginDialog.resetTimer();

    $('#loginDialogUsername, #loginDialogPassword').keyup(loginDialog.submit);
};

loginDialog = {

    dialog: null,
    loginTimer: null,
    originalText: null,
    loginSuccessful: false,
    
    resetTimer: function (sentTime) {
        sentTime = sentTime || jsVars['sessionDuration'];
        clearTimeout(loginDialog.loginTimer);
        loginDialog.loginTimer = 
            setTimeout(loginDialog.openDialog, sentTime * 1000);    
    },

    openDialog: function () {
        $.ajax({
            type: 'get',
            url: jsVars['urls']['dialogLogin'],
            data: {sessionCheck: true},
            dataType: 'json',
            success: function (response) {
                // Update the timeout if there is time remaining
                response.remaining > 0 ? 
                    loginDialog.resetTimer(response.remaining) : 
                    loginDialog.dialog.dialog('open');
            }
        });
    },

    submit: function (event) {
        // Only go if enter was pressed or login button was clicked
        if (typeof event.keyCode !== 'undefined' && event.keyCode !== 13) {
            return;
        }

        var username = $('#loginDialogUsername').val(),
            password = $('#loginDialogPassword').val();

        if (! username || ! password) {
            return;
        }

        $.ajax({
            type: 'post',
            url: jsVars['urls']['dialogLogin'],
            data: {
                'username': username, 
                'password': CryptoJS.MD5(password).toString()
            },
            dataType: 'json',
            success: loginDialog.success
        });
    },
    
    success: function (response) {

        var customMessage = typeof response.message === 'undefined' ?
            'Invalid Credentials' : response.message;

        $message = $('#loginDialogMessage');

        if (response.success === true) {
            $message.css('color', 'green');
            $message.text('Login Successful');

            setTimeout(function () {
                loginDialog.resetTimer();
                // Set the login flag so the page won't redirect on close
                loginDialog.loginSuccessful = true;
                loginDialog.dialog.dialog('option', 'hide', 'fade').dialog('close');
            }, 1500);
            setTimeout(function () {
                $message.html(loginDialog.originalText);
                $('#loginDialogUsername, #loginDialogPassword').val('');
                $message.css('color', 'black');
            }, 2000);
        } else {
            $message.css('color', '#700');
            $message.text(customMessage);
        }
    }
};