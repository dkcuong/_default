/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var pageTester;

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.tester = function () {

    runTests();  

    $('.resultDisplays').on('load', function () {

        var newHeight = this.contentWindow.document.body.offsetHeight + 20,
            index = $('.resultDisplays').index(this);

        this.style.height = newHeight + 'px';

        var $frameContents = $(this).contents(),
            $resultTitles = $('.resultTitles').eq(index);

        var $toggleErrorRows = $frameContents.find('.toggleErrorRows');

        var testPasses = $toggleErrorRows.length === 0;

        var result = testPasses ? 'OK' : 'Discrepant';

        var title = $resultTitles.html() + ': Test result - ' + result,
            resultClass = testPasses ? 'resultOK' : 'resultDiscrepant';

        $resultTitles
            .html(title)
            .addClass(resultClass);

        $frameContents.find('.switchIgnore').on('click', switchIgnore);
        $toggleErrorRows.on('click', toggleErrorRows);
        $frameContents.find('.collapseExpand').on('click', collapseExpands);
    });

    $('#testerTable').on('click', '.toggleResults', toggleTestResults);
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function runTests()
{
    if (! jsVars['runTests']) {
        return;
    }
    
    var $blockMessage = $('<div/>', {
        id: 'blockMessage',
        html: 'Running Tests. Do NOT Close This Window.'
    });

    $.blockUI({
        message: $blockMessage
    });

    pageTester.runNextTest();
}

/*
********************************************************************************
*/

function formatError(result)
{
    return '<pre>' +
            JSON.stringify(result)
                .replace(/\\n/g, '<br>')
                .replace(/\\"/g, '"') +
           '</pre>';
}

/*
********************************************************************************
*/

pageTester = {

    testURLs: jsVars['urls']['tests'],
    testPostVars: jsVars['postVars'],
    updateTestURL: jsVars.urls.updateRequestID,
    requests: jsVars['requests'],
    testIndex: 0,
    testPost: {},
    testURL: null,
    testMethod: null,

    //**************************************************************************

    runNextTest: function () {

        ! jsVars['requests'].length ? alert('Empty Series') : null;
        
        if (this.testIndex >= this.requests.length) {
            return this.unsetRequestID();
        }

        var currentTest = this.requests[this.testIndex];

        var requestID = currentTest.requestID;

        var blockMessage = 'Running ' + currentTest.request.description + '. ' +
                'Do NOT Close This Window.';

        $('#blockMessage').html(blockMessage);

        var isPost = typeof this.testPostVars[requestID] !== 'undefined';

        this.testPost = isPost ? $.parseJSON(this.testPostVars[requestID]) : {};

        this.testURL = this.testURLs[requestID];

        this.testMethod = isPost ? 'post' : 'get';

        this.testIndex++;

        $.ajax({
            type: 'post',
            url: pageTester.updateTestURL,
            data: {
                recordTest: false,
                requestID: requestID
            },
            success: pageTester.checkResults(requestID)
        });
    },

    //**************************************************************************

    unsetRequestID: function () {
        $.ajax({
            type: 'post',
            url: pageTester.updateTestURL,
            data: {
                requestID: 0
            },
            success: function () {
                $.unblockUI();
            }
        });
    },

    //**************************************************************************

    checkResults: function (requestID) {

        // Errors will be sent as a json property
        var isError = false,
            $resultTitles = $('#result-' + requestID + ' .resultTitles'),
            $resultDisplays = $('#result-' + requestID + ' .resultDisplays');

        $resultTitles.html('Post Completed');

        $resultDisplays.load(function () {
            if (isError) {

                pageTester.unsetRequestID();

                location.href = '#showResults' + requestID;
            } else {
                pageTester.runNextTest();
            }
        });

        $resultDisplays.prop('src', pageTester.testURL);
    }
};

/*
********************************************************************************
*/

function toggleTestResults()
{
    var $iFrame = $('.resultDisplays', $(this).parent().parent());

    $iFrame.slideToggle('slow');

    var caption = $(this).html() == 'Hide Results' ? 'Display' : 'Hide';

    $(this).html(caption + ' Results');
    // return false - is needed to prevent from jumping to the top of the page
    return false;
}

/*
********************************************************************************
*/

function toggleErrorRows()
{
    var $anchor = $(this);

    var caption = $anchor.html() == 'Full Output' ? 'Discrepancies only' :
        'Full Output';

    $anchor.html(caption);

    var $table = $anchor.siblings('.resultTable');

    $('.resultSubTable', $table).map(function () {

        var $headers = $('th', $(this)),
            $parentRow = $(this).parent().parent(),
            isError = $('tr.errorRow', $(this)).length;

        toggleErrorRowClasses($parentRow.add($headers), isError);
    });

    if (caption == 'Full Output') {

        $('.arrayTable', $table).map(function () {

            var $parentRow = $(this).parent().parent();
                isError = $('tr.errorRow', $(this)).length;

            toggleErrorRowClasses($parentRow, isError);
        });
    }


    $('.validRow', $table).toggle('slow');

    return false;
}

/*
********************************************************************************
*/

function switchIgnore()
{
    var isError = $(this).val() == 'Consider Error',
        $trTag = $(this).parent().parent();

    var title = isError ? 'Ignore Error' : 'Consider Error';

    $(this).val(title);

    toggleErrorRowClasses($trTag, isError);

    var background = isError ? '#FFE9E9' : '#FFF';

    $trTag.css('background-color', background);

    var seriesID = $trTag.parent().parent().attr('data-series-id'),
        ignoreField = $(this).attr('data-field');

    $.ajax({
        url: jsVars.urls.switchIgnoreField,
        type: 'post',
        data: {
            seriesID: seriesID,
            ignoreField: ignoreField,
            isError: isError
        }
    });
}

/*
********************************************************************************
*/

function toggleErrorRowClasses($selectorList, isError)
{
    var removeClass = isError ? 'validRow' : 'errorRow',
        addClass = isError ? 'errorRow' : 'validRow';

    $selectorList
        .removeClass(removeClass)
        .addClass(addClass);
}

/*
********************************************************************************
*/

function collapseExpands()
{
    var newValue = $(this).val() == '-' ? '+' : '-',
        $nextCell = $(this).parent().next();

    $('.resultTable', $nextCell).toggle(0);

    $(this).val(newValue);
}

/*
********************************************************************************
*/
