/*
********************************************************************************
* MAIN MENU JS
********************************************************************************
*/

var testDropDowns = {},
    testsDisplay = {};

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/
function getOptions()
{
    $option = $('#testingOptions option:selected');

    var type = $option.attr('data-type');
    var mode = $option.attr('data-mode');
    var isTest = type === 'test';
    var running = mode === 'run';
    var compare = mode === 'compare';

    return {
        type: type,
        mode: mode,
        target: isTest ? $('#testID') : $('#seriesID'),
        display: isTest ? 'Test' : 'Series',
        isTest: isTest,
        running: running,
        compare: compare,
        recordSeries: ! isTest && ! running
    };
}

funcStack.recorder = function () {

    jsVars['viewProperties'] ? $('#viewProperties').addClass('activeButton') : null;

    $('#viewProperties').click(function () {
        var $this = $(this);
        var activating = ! $this.hasClass('activeButton');

        $.ajax({
            type: 'post',
            url: jsVars['urls']['toggleTesterSessionJSON'],
            data: {
                toggle: activating,
                viewProperties: true
            },
            dataType: 'json',
            success: function () {
                $this.toggleClass('activeButton');
            }
        });


    });

    $('.testerInfo').tooltip();
    $('#clearTestDB').click(clearTestDB);
    $('#switchRecordMode, #recordButton').click(switchRecordMode);
    $('#testingOptions').change(function () {

        var options = getOptions();

        $('.testOptions').hide();

        if (! options.mode) {
            return;
        }

        $('#instructions').show();

        options.target.show();

        var caption = 'Run Comparison';

        if (! options.compare) {

            var action = options.running ? 'Run' : 'Record';

            caption = action + ' ' + options.display;
        }

        var actionButton = options.running ? '#runButton' : '#recordButton';

        $(actionButton).show().html(caption);
    });

    $('#runButton, #recordButton').click(function () {

        // Series recording is the only option that does not need the
        // runPageTest controller
        var options = getOptions();
        if (! options.isTest && ! options.running) {
            return;
        }

        $.ajax({
            url: jsVars['urls']['getTestingInfo'],
            dataType: 'json',
            data: {
                isTest: options.isTest,
                term: options.target.val()
            },
            success: function (response) {
                var message = 'Series Not Found';
                var link = jsVars['urls']['runPageTests']+'/type/'+
                            options.type+'/id/'+response.id+'/mode/'+
                            options.mode;
                response.id ? window.open(link) : alert(message);
            }
        });

    });

    ! jsVars['runTests'] ? switchRecordMode() : null;
    $('.collapseExpand').click(collapseExpand);

    $('.testMode').change(testDropDowns.changeTestMode);
    $('#testSubMenus').change(testDropDowns.changeSubMenu);
    $('#startSession, #endSession').click(testsDisplay.toggleSession);

    $('#tests').change(updateRunTestLink);

    $('#testID').autocomplete({source: jsVars['urls']['testAutocomplete']});
    $('#seriesID').autocomplete({source: jsVars['urls']['seriesAutocomplete']});
   
    $('#testID, #seriesID').on('focus', function(){
        var $this = $(this);
        $this.val('');
    });
};

/*
********************************************************************************
*/

testsDisplay = {

    toggleSession: function() {
        $.ajax({
            type: 'post',
            url: jsVars['urls']['toggleTesterSessionJSON'],
            data: {
                toggle: this.id === 'startSession'
            },
            dataType: 'json',
            success: function (response) {

                if (response.noAccess) {
                    return alert('You Need Dev Access');
                }

                $('html').append($('#testSetter'));

                response.on ?
                    window.parent.$('#testSetter').removeClass('hidden') :
                    $('#testSetter').addClass('hidden');

                response.on ? $('#startSession').hide() :
                    $('#mainDisplay').contents().find('#startSession').show();
            }
        });
    }
};

/*
********************************************************************************
*/

testDropDowns = {

    changeTestMode: function () {

        var mode = $(this).val();

        var $hideObject = mode === 'run' ? $('#recordMode') : $('#runMode'),
            $showObject = mode === 'run' ? $('#runMode') : $('#recordMode');

        $hideObject.hide();
        $showObject.show();

        $.ajax({
            url: jsVars['urls']['changeTestMode'],
            dataType: 'json',
            data: {
                mode: mode
            },
            success: function (response) {
                if (response) {
                    testDropDowns.addCaseDropDowns(response);
                }
            }
        });
    },

    addCaseDropDowns: function (response) {

        var $select = $('#tests');

        $select.empty();

        $.each(response, function(index, displayName) {
            var $option = $('<option/>', {
                value: index,
                text: displayName
            });

            $select.append($option);
        });

        if ($('option', $select).length > 1) {
            // add Test All if there are more than one select option
            var $option = $('<option/>', {
                value: 0,
                text: 'All Tests'
            });

            $select.prepend($option);
        }
    },

    changeSubMenu: function () {

        $('#testPages option').show();

        var subMenu = $(this).val();

        if (subMenu > 0) {
            // hide pages from deselected submenu
            $('#testPages :not(option[data-sub-menu=' + subMenu + '])').hide();
            // show "Test All" if this option is present in the dropdown
            $('#testPages option[value=0]').show();
            // select the first dropdown option
            $('#testPages')[0].selectedIndex = 0;
        }
    }
};

/*
********************************************************************************
*/

function clearTestDBTrigger()
{
    clearTestDBExecute('go');
}

/*
********************************************************************************
*/

function clearTestDB()
{
    var message = 'Are you sure you would like to clear test data?';

    defaultConfirmDialog(message, 'clearTestDBTrigger');
}

/*
********************************************************************************
*/

function clearTestDBExecute(go, callBack)
{
    if (! go) {
        return;
    }

    $.blockUI({
        message: 'Clearing wms_tests_run Database. Do NOT Close This Window.'
    });

    $.ajax({
        type: 'post',
        data: {
            clearTestDB: true
        },
        url: jsVars['urls']['clearTestDB'],
        success: function () {
            typeof callBack === 'function' ? callBack() : $.unblockUI();
        }
    });
}

/*
********************************************************************************
*/

function switchRecordMode(clicked)
{
    var options = getOptions(),
        $option = $('#testingOptions option:selected');

    if (! options.recordSeries || ! $option.is('[data-mode]')) {
        // skip on page load as "Select Testing Option" is a default option
        return;
    }

    var seriesID = options.target.val();

    if (! seriesID && clicked) {
        return defaultAlertDialog('No Test ID Selected');
    }

    $button = $('#switchRecordMode');

    var startText = 'Start Recording';
    var stopText = 'Stop Recording';

    // Set the id to 0 if toggle clicked and recording
    var currentText = $button.html();
    var toggleClick = this.id === 'switchRecordMode';
    seriesID = toggleClick && currentText === stopText ? 0 : seriesID;

    var newText = seriesID ? stopText : startText;

    $.ajax({
        url: jsVars['urls']['updateSeriesID'],
        type: 'post',
        dataType: 'json',
        data: {
            recordTest: true,
            seriesID: seriesID
        },
        success: function (response) {


            response ? $('#seriesID, #recordButton').hide() :
                       $('#seriesID, #recordButton').show();

            response ? $('#switchRecordMode').show() :
                       $('#switchRecordMode').hide();

            response ? $button.addClass('recording') :
                       $button.removeClass('recording');

            $button.html(newText);

            responseID = response ? parseInt(response.id) : 0;

            if (response) {
                var displayInfo = 'Series ID: ' + responseID +
                    '<br>Description: ' + response.description;
                $('#series').html(displayInfo).css('display', 'block');
            } else {
                $('#series').empty().css('display', 'none');
            }

            if (! response && seriesID) {
                var message = 'Test ' + seriesID + ' is not defined';
                return defaultAlertDialog(message);
            }

        }
    });
}

/*
********************************************************************************
*/

function runTests()
{
    $.ajax({
        url: jsVars['urls']['runTests'],
        dataType: 'json',
        data: {
            subMenus: $('#testSubMenus').val(),
            pages: $('#testPages').val()
        }
    });
}

/*
********************************************************************************
*/

function updateRunTestLink()
{
    $.ajax({
        type: 'post',
        url: jsVars['urls']['updateSeriesID'],
        dataType: 'json',
        data: {
            recordTest: false,
            seriesID: false
        },
        success: function () {
            var $linkAnchor = $('#runTestsLink');

            var link = $linkAnchor.attr('data-link'),
                test = $('#tests').val();

            var href = link + '/test/' + test;

            $linkAnchor.attr('href', href);
        }
    });
}

/*
********************************************************************************
*/

function collapseExpand()
{
    var counterButton = $(this).attr('data-counter');

    $(this).hide();
    $('#' + counterButton).show();

    $('.collapsable').toggle();
}

/*
********************************************************************************
*/
