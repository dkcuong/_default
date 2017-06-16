var needCloseConfirm,
    checkErrorAjax = true;

window.onbeforeunload = function () {
    var message = 'You are about to leave this page - ' +
        'data you have entered may not be saved.';
    if (needCloseConfirm) {
        return message;
    }
};

window.needSetHeight = true;

// Call stored functions for autoloading
$(function () {
    var message = '';

    messageDialog = $('<div></div>').appendTo('body')
    .html('<span id="messageAlertDialg">' + message + '</span>').dialog({
        autoOpen: false,
        width: 500,
        modal: true,
        height: 'auto',
        title: 'WARNING',
        position: [200,10]

    });

    jsAutoload();

    $(document).ajaxError(function(e, xhr, settings) {

        // Don't create ajax error popup if the xhr is aborted
        if (! xhr.status) {
            return;
        }

        var classUrl = settings.url,
            message = '';

        if(! xhr.readyState && xhr.statusText == 'error') {
            message = 'You have lost your session due to lack of internet. '
            + 'Please try refreshing your browser.';

            defaultAlertDialog(message);
            return;
        }  else if (xhr.status === 404) {
            message = 'Requested URL not found.';
        } else if (xhr.status === 500) {
            message = xhr.responseText;
            try {
                message = JSON.parse(xhr.responseText);
            } catch (ex) {
                message = xhr.responseText;
            }
        } else if (xhr.statusText === 'parsererror') {
            message = 'Error.\nParsing JSON Request failed.';
        } else if (xhr.statusText === 'timeout') {
            message = 'Request timed out.\nPlease try later';
        } else {
            message = ('Unknown Error.' + xhr.responseText);
        }

        if (checkErrorAjax) {
            $.ajax({
                url: jsVars['urls']['ajaxErrorSubmit'],
                type: 'post',
                data: {
                    'message': message,
                    'classUrl': classUrl
                },
                dataType: 'json',
                success: function (data) {
                    message = 'Ajax request fails. Please view Log file';
                    if (data) {
                        defaultAlertDialog(message);
                    }
                },
                error: function () {
                    checkErrorAjax = false;
                }
            });
        }
    });

    $('#callReportIssue').click(function () {

        var reportIssueg = new reportIssuegModel();

        reportIssueg.open();
    });
});

/*
********************************************************************************
*/

function defaultAlertDialog(message, field, passIsVisible, passedFunc, param)
{
    var isVisible = (passIsVisible || typeof passIsVisible === 'undefined') ?
        true : false;

    if (! isVisible) {
        return;
    }

    $('#messageAlertDialg').html(message);
    messageDialog.dialog({
        buttons: {
            'Okay': function () {

                messageDialog.dialog('close');

                passedFunc = typeof passedFunc === 'undefined' ? null : passedFunc;
                param = typeof param === 'undefined' ? {} : param;

                var myfunction = window;

                if (passedFunc !== null) {

                    var funcNamePieces = passedFunc.split('.');

                    funcNamePieces.map(function (piece) {
                        myfunction = myfunction[piece];
                    });

                    myfunction(param);
                }

                typeof field === 'undefined' ? null : field.focus();

            }
        }
    });
    messageDialog.dialog('open');

}

/*
********************************************************************************
*/

function defaultConfirmDialog(message, passedFunc, param, field)
{
    message = message || 'Are you sure?';

    $('#messageAlertDialg').html(message);

    messageDialog.dialog({
        buttons: {
            'Okay': function () {

                messageDialog.dialog('close');

                passedFunc = typeof passedFunc === 'undefined' ? null : passedFunc;
                param = typeof param === 'undefined' ? {} : param;

                var myfunction = window;

                var funcNamePieces = passedFunc.split('.');

                funcNamePieces.map(function (piece) {
                    myfunction = myfunction[piece];
                });

                myfunction(param);

                typeof field === 'undefined' ? null : field.focus();
            },
            'Cancel': function () {

                messageDialog.dialog('close');

                typeof field === 'undefined' ? null : field.focus();
            }
        }
    });

    messageDialog.dialog('open');
}

/*
********************************************************************************
*/

function getHTMLLink(data)
{
    data.link = data.link || '';
    data.attributes = data.attributes || {};
    data.link = data.link || '#';

    var $anchor = $('<a>').attr('href', data.link).html(data.title);

    $.each(data.attributes, function (attribute, value) {
        $anchor.attr(attribute, value);
    });

    return data.getObject ? $anchor : $anchor.prop('outerHTML');
}

/*
********************************************************************************
*/

function httpBuildQuery(link, params, jsonLink)
{

    if (jsonLink) {
        return link + '&' + $.param(params);
    }

    $.each(params, function (parameter, value) {
        link += '/' + parameter + '/' + value;
    });

    return link;
}

/*
********************************************************************************
*/

function newTab(tabLink)
{
    var newWin = window.open(tabLink, '_blank');

    if (! newWin || newWin.closed || typeof newWin.closed == 'undefined') {

        var message = 'Your browser blocks pop ups. Go to "Setting" menu in the '
                    + 'top-right corner of Chrome, at the bottom of the page '
                    + 'click "Show advanced settings..." link. Then click "Content'
                    + ' settings..." button and refer to "Pop-ups" section.';

        defaultAlertDialog(message);
    }
}

/*
********************************************************************************
*/
