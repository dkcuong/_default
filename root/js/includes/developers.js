/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var checker = {};

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.developers = function () {

    $('span').tooltip();

    $('#validate').click(checker.clickValidate);

    $('#commandType').change(displayType);

    $('.queryInputs').change(function (event) {
        var elementClasses = $(this).attr('class').split(' ')
            .map(function (value) {
                return '.'+value;
            }).join('');

        $(elementClasses).val(this.value);

    });

    $('#addTestButton').click(addTest);
    
    $('#testName').autocomplete({
        source: jsVars['urls']['testNameAutocomplete']
    });
    
    // Hacky way to adjust command display height. Need to replace main html 
    // table with divs to correct this
    $('#commandsFrame').load(function () {
        var $mainDisplay = $('#mainDisplay', window.parent.document);
        if ($mainDisplay.length) {

            var height = $mainDisplay.height() - 215;
            this.style.height = height + 'px'; 
        } else {
            this.style.width = this.contentWindow.document.body.scrollWidth + 'px'; 
            this.style.height = this.contentWindow.document.body.scrollHeight + 'px'; 
        }
    });
    
    $('#dataType').change(function () {
       $('.dataTypeTables').hide();
       $('.dataTypeTables#'+this.value).show();
    });
    
    $('.commandType').change(function () {
        console.log(this.value);
        $('.toggleRows').css('display', 'none');
        $('.'+this.value).css('display', 'table-row');
    });
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

checker = {
    commands: [],
    currentRow: 0,
    openDialog: null,
    currentSpan: null,
    showRowChanges: false,

    /*
    ****************************************************************************
    */

    clickValidate: function () {

        checker.currentRow = 0;

        checker.commands = $('.commands').toArray();

        checker.runTest();
    },

    /*
    ****************************************************************************
    */

    runTest: function () {

        if (! checker.commands.length) {
            return;
        }


        checker.currentSpan = $(checker.commands).first();
        var isActive = $(checker.currentSpan).attr('data-active');

        if (! parseInt(isActive)) {
            // Go to next if this command is inactive
            checker.changeRow();
            return checker.runTest();

        }

        $.ajax({
            type: 'post',
            url: jsVars['urls']['runDBCheckJSON'],
            dataType: 'json',
            data: checker.getData(),
            success: checker.runTestRespons,
            error: checker.error
        });
    },

    /*
    ****************************************************************************
    */

    getData: function () {
        return {
            id: $(checker.currentSpan).attr('data-id'),
            db: $(checker.currentSpan).attr('data-db'),
        };
    },

    /*
    ****************************************************************************
    */

    changeRow: function () {
        checker.commands.shift();
        checker.currentRow++;
        checker.showRowChanges ?
            alert('incremented to '+checker.currentRow) : null;
    },

    /*
    ****************************************************************************
    */

    closeAndTest: function (dialogName) {
        $(dialogName).dialog('close');
        checker.runTest();
    },

    /*
    ****************************************************************************
    */

    runUpdate: function () {

       // checker.commands.unshift(checker.currentSpan);

        $.blockUI({
            message: 'Running DB update. Do NOT Close This Window.'
        });

        $.ajax({
            type: 'post',
            url: jsVars['urls']['runDBUpdateJSON'],
            dataType: 'json',
            data: checker.getData(),
            success: function () {
                checker.closeAndTest('#failedTest');
                $.unblockUI();
            },
            error: function () {
                checker.error;
                $.unblockUI();
            }
        });
    },

    /*
    ****************************************************************************
    */

    closeDialog: function () {
        checker.openDialog ?
            $(checker.openDialog).dialog('close') : null;
        checker.openDialog = null;
    },

    /*
    ****************************************************************************
    */

    error: function (response) {
        checker.closeDialog();
        $('#updateFail span').html(response.responseText);
        $('#updateFail').dialog({
            modal: true,
            buttons: {
                'Run Update': function () {
                    checker.closeDialog();;
                    checker.runUpdate();
                },
                'Continue': function () {
                    checker.updateResult('Failed');
                    checker.changeRow();
                    checker.closeAndTest('#updateFail');
                }
            }
        });
        checker.openDialog = '#updateFail';
    },

    /*
    ****************************************************************************
    */

    updateResult: function (message) {
        var $testResult = $('.testResults').eq(checker.currentRow);
        var resultClass = message === 'Passed' ? 'good' : 'bad';
        var removeClass = message === 'Passed' ? 'bad' : 'good';
        $testResult.html(message).addClass(resultClass).removeClass(removeClass);
    },

    /*
    ****************************************************************************
    */

    runTestRespons: function (response) {

        if (response.passed) {
            checker.updateResult('Passed');
            checker.changeRow();
            return checker.runTest();
        } 
        
        var testMessage = $('.tests').eq(checker.currentRow).clone();

        $('#displayTest').empty().append(testMessage);
        $('#testResults').html(response.results);

        var testUpdate = $('.commands').eq(checker.currentRow).text();
        var pre = $('<pre>').html(testUpdate);

        $('#displayUpdate').html(pre)
            .css('display', 'inline-block');

        $('#failedTest').dialog({
            modal: true,
            buttons: {
                'Run Update': checker.runUpdate,
                'Continue': function () {
                    checker.updateResult('Failed');
                    checker.changeRow();
                    checker.closeAndTest('#failedTest');
                }
            }
        }).dialog('option', 'width', 'auto');

        checker.openDialog = '#failedTest';
    }

};

/*
********************************************************************************
*/

function displayType()
{
    var displayDiv = $(this).val();
    
    if (! displayDiv) {
        return $('#queryRow').hide();
    }

    $('.queryInput').hide();
    $('#' + displayDiv+', #queryRow').show();
}

/*
********************************************************************************
*/

function addTest(event)
{
    
    
    event.preventDefault();

    var inputs = {};
    $('form').serializeArray().map(function (row) {
        inputs[row.name] = row.value;
    });
    
    $.ajax({
        type: 'post',
        url: jsVars['urls']['addTestJSON'],
        dataType: 'json',
        data: inputs,
        success: function (response) {

            var errorMessages = [];

            var errors = response.errors;
            
            if (errors) {
                errors.noModel ? 
                    errorMessages.push('No Query Model Selected') : null;
                errors.noDB ? 
                    errorMessages.push('No Database Selected') : null;
                errors.noDesc ? 
                    errorMessages.push('No Description') : null;
            
                var missing = errors.missingQueryValues;
                missing ? missing.map(function (value) {
                    errorMessages.push('Missing Query Value: '+value);                
                }) : null;

                errors.alreadyExists ? 
                    errorMessages.push('This Command Already Exists') : null;
            }

            var errorMessage = 'Error: ' + "\n" + errorMessages.join("\n");
            
            console.log(errorMessages.length, errorMessages);
            if (errorMessages.length) {
                alert(errorMessage);
            } else {
                console.log($('#commandsFrame'));
                console.log($('#commandsFrame').contentWindow);
                $('#commandsFrame').attr('src', jsVars['urls']['iframe']);
                alert('Command Added');
            }
        },
        error: function (response) {
            alert(response.responseText);
        }
        
    });
}

/*
********************************************************************************
*/

function runTestUpdateAction(action, data, $testsTable)
{
}

/*
********************************************************************************
*/

function runTestUpdateActionExecute(testName, response, $testsTable)
{    
    if (~$.inArray[response.action, ['updated', 'removed']]) {
        $('tr[data-test-name="' + testName + '"]', $testsTable).remove();
    }

    if (typeof response.commands === 'undefined') {
        return false;
    }
    
    var database = response.commands.database,
        queries = response.commands.queries;

    $.map(queries, function (query) {

        var $checkCell = $('<div>')
                .addClass('message')
                .html(query.check);

        var $sqlCell = $('<div>')
                .addClass('message')
                .html(query.sql);

        var $tr = $('<tr>')
            .attr('data-test-name', testName)
            .append(
                $('<td>').html(testName)
            )
            .append(
                $('<td>').html(database)
            )
            .append(
                $('<td>').append($checkCell)
            )
            .append(
                $('<td>').append($sqlCell)
            );

        $testsTable.append($tr);
    });
}

/*
********************************************************************************
*/
