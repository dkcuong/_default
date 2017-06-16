// Javascipt for a custom filter on datatables

var pickerCounter = 0,
    clauseCopy,
    searcher,
    isUPdateFinalUrl = false;

/*
********************************************************************************
*/

funcStack.searcher = function () {

    $('#searchForm').submit(submitForm);

    $('#submitSearch').click(submitForm);

    $('.exportSearcher').click(exportSearcher);

    $('#addClause').click(addClauseInputs);

    $(document.body).on('change', '.searchTypes', changeInputSource);

    $(document.body).on('click', '.removeButtons', removeClause);

    $('body').on('focus', '.searcherDate', function () {
        searcherDateInput($(this));
    });

    // Make a copy of clause for more copies
    clauseCopy = $('.clauses').first().clone();

    defaultSearcherDropdowns();

    $.each(jsVars['presetSearches'], searcher.loadPresetDropdowns);

    if (jsVars['presetSearches'].length) {
        isUPdateFinalUrl = true;
        filterSearch();
    }

};

/*
********************************************************************************
*/

searcher = {

    targets: [],
    originalTargets: [],
    targetIDs: [],
    outsideTable: false,
    emptyingTable: false,
    currentTarget: null,
    externalSearches: [],
    firstRun: true,

    useExternalParams: function () {

        this.originalTargets = jsVars['searcher']['multiID'];

        this.originalTargets.map(function (target) {

            searcher.currentTarget = target;

            var targetID = searcher.targetIDs[target] = '#'+target;

            if (searcher.firstRun) {
                // prevent change events firing multiple times (bind change event once)
                $(targetID).change(searcher.updateExternalParams);
            }

            searcher.adjustClientInventoryWidth();

            $(window).resize(searcher.adjustClientInventoryWidth);
        });

        searcher.updateExternalParams();

        searcher.firstRun = false;

        this.originalTargets ? null : submitForm(event);
    },

    updateExternalParams: function (event) {

        searcher.targets = [];
        searcher.externalSearches = [];

        searcher.originalTargets.map(function (target) {

            var targetID = searcher.targetIDs[target];

            if (typeof $(targetID).attr('disabled') === 'undefined') {
                searcher.getExternalSearches(target, targetID);
            }
        });

        return event === 'undefined' ? null : submitForm(event);
    },

    getExternalSearches: function (target, targetID) {

        var searchField = $(targetID).attr('data-search-field'),
            selectedIDs = [];

        $('option:selected', $(targetID)).map(function() {

            var id = $(this).attr('data-subject-id');

            if (id == -1) {
                return [];
            }

            selectedIDs.push({
                field: searchField,
                value: id
            });
        });

        if (selectedIDs.length) {

            searcher.targets.push(target);

            searcher.externalSearches[target] = selectedIDs;
        }
    },

    adjustClientInventoryWidth: function () {

        var bodyWidth = $('body').width();

        var targetID = searcher.targetIDs[searcher.currentTarget];

        var multiSelectWidth = searcher.outsideTable ? 0 :
                $(targetID).width() + 20;

        var wrapperWidth = bodyWidth - multiSelectWidth;

        var tableID = jsVars['searcher']['modelName'];

        $('#'+tableID+'_wrapper').width(wrapperWidth);
    },

    externalURL: function (params) {

        params.urlSegment = params.urlSegment || '';

        var isClient = typeof jsVars['isClient'] !== 'undeinfed'
                && jsVars['isClient'];

        if (! Object.keys(searcher.externalSearches).length) {

            if (isClient) {
                searcher.targets.map(function (target) {
                    // If this is a client request, they can not see all info
                    jsVars['searcher']['preSelects'][target].map(function (targetValue) {
                        searcher.externalSearches[target].push({
                            field: target,
                            value: targetValue
                        });
                    });
                });
            } else {
                return params.url + params.urlSegment;
            }
        }

        var urlSegments = [];

        searcher.targets.map(function (target) {

            var firstRun = urlSegments.length ? false : true;

            var addSearch;

            if (firstRun) {
                searcher.externalSearches[target].map(function (row) {
                    // Create the original query for each vendor
                    addSearch = '&searchTypes[]=' + row.field
                        + '&searchValues[]=' + encodeURIComponent(row.value)
                        + '&compareOperator=exact';

                    urlSegments.push(addSearch);
                });
            } else {
                var newSegments = [];

                searcher.externalSearches[target].map(function (row) {

                    addSearch = '&andOrs[]=and'
                        + '&searchTypes[]=' + row.field
                        + '&searchValues[]=' + encodeURIComponent(row.value)
                        + '&compareOperator=exact';

                    urlSegments.map(function (oldSearch) {
                        newSegments.push(oldSearch + addSearch);
                    });
                });

                urlSegments = newSegments;
            }
        });

        var allSegments = '';

        if (urlSegments.length) {

            var firstRun = true;

            urlSegments.map(function (segment) {
                allSegments += firstRun ? '&andOrs[]=and' : '&andOrs[]=or';
                allSegments += segment + params.urlSegment;
                firstRun = false;
            });
        } else {

            var fields = Object.keys(searcher.externalSearches);

            if (fields.length) {
                allSegments += '&andOrs[]=and'
                             + '&searchTypes[]=' + fields[0] + '&searchValues[]=true'
                             + '&andOrs[]=and'
                             + '&searchTypes[]=' + fields[0] + '&searchValues[]=false'
                             + '&compareOperator=exact';
            } else {
                allSegments = params.urlSegment;
            }
        }

        return params.url + allSegments;
    },

    outsideDataTable: function () {
        searcher.outsideTable = true;
    },

    loadPresetDropdowns: function (index, presets) {

        var ddValue = jsVars['dropdownValues'][presets.field];

        $('.searchTypes').eq(index).val(ddValue).trigger('change');

        $('.searchValues').eq(index).val(presets.value);
    }
};

/*
********************************************************************************
*/

function defaultSearcherDropdowns()
{
    if (typeof jsVars['searcher']['dropdowns'] === 'undefined') {
        return;
    }

    // Set the dropdowns to the values in the searcher array

    var counter = 0;
    $.map(jsVars['searcher']['dropdowns'], function (value) {

        if (! value) {
            return;
        }

        // Dont add clause on first run
        counter ? addClauseInputs(0) : null;

        // The index of the searchType is the counter
        var dropdown = $('.searchTypes').eq(counter);

        changeInputSource(dropdown, value);

        counter++;

    });

    // Update the table to the search
    filterSearch();
}

/*
********************************************************************************
*/

function filterSearch(returnSegment)
{
    var searchData = $('#searchForm').serialize();
    // Update the datatable
    if (searchData != null) {
        var modelName = jsVars['searcher']['modelName'];

        var operatorSent = typeof jsVars['compareOperator'] != 'undefined'
            && jsVars['compareOperator'];

        var compareOperator = operatorSent ?
            compareOperator = '&compareOperator='+jsVars['compareOperator'] : '';

        var tableAPI = dataTables[modelName].api();
        var urlSegment = '&' + searchData + compareOperator;

        if (typeof returnSegment == 'undefined') {

            var finalURL = searcher.externalURL({
                url: jsVars['urls']['searcher'],
                urlSegment: urlSegment
            });

            var tableAjax = tableAPI.ajax;

            tableAjax.data = searchData;

            if (finalURL) {
                if (isUPdateFinalUrl) {
                    tableAjax.url(finalURL);
                    isUPdateFinalUrl = false;
                } else {
                    tableAjax.url(finalURL).load();
                }
            } else {
                tableAPI.clear().draw();
            }
        } else {
            return urlSegment;
        }
    }
}

/*
********************************************************************************
*/

function searcherDateInput(searchInput)
{
    if (! $(searchInput).hasClass('hasDatepicker')) {
        // Add an ID to datepicker input bc jQuery gets confuse when there's
        // more than one dynamic picker
        var pickerID = pickerCounter++;
        var elementID = 'picker_'+pickerID;
        $(searchInput).attr('id', elementID);

        $(searchInput).datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }
}

/*
********************************************************************************
*/

function exportSearcher()
{
    var exportType = this.id == 'excel' ? 'excel' : 'csv';
        exportType = this.id == 'pdf' ? 'pdf' : exportType,
        searchParams = JSON.stringify(jsVars['searchParams']);

    // Export button click submits true data param
    var isExport = $('<input/>')
        .addClass('searcherParam')
        .attr('type', 'hidden')
        .attr('name', 'exportSearcher')
        .attr('value', 'true');

    var exportInput = $('<input/>')
        .addClass('searcherParam')
        .attr('type', 'hidden')
        .attr('name', 'exportType')
        .attr('value', exportType);

    var searchParams = $('<input/>')
        .addClass('searcherParam')
        .attr('type', 'hidden')
        .attr('name', 'searchParams')
        .attr('value', searchParams);

    var finalURL = searcher.externalURL({
        url: jsVars['urls']['searcher']
    });

    $('#searchForm').prop('action', finalURL);

    $('#searchForm .searcherParam').remove();

    $('#searchForm')
        .append(isExport)
        .append(exportInput)
        .append(searchParams)
        .prop('data_post', true)
        .submit();
}

/*
********************************************************************************
*/

function removeClause()
{
    var clauseIndex = $('.removeButtons').index(this);

    $('.clauses').eq(clauseIndex).hide(800, function () {
        $(this).remove();
    });
}

/*
********************************************************************************
*/

function addClauseInputs(delay)
{
    delay = delay || 400;

    // Copy first clause and empty
    var newClause = clauseCopy.clone();
    newClause.children().show();

    $('.searchValues', newClause).val('');
    newClause.hide();
    $('.clauses').last().after(newClause);

    var searcherWidth = $('#searcher table').width();

    $('.clauses').show(delay);
    $('#searcher').scrollLeft(searcherWidth);
}

/*
********************************************************************************
*/

function changeInputSource(dropdown, field)
{
    // The searchTypes class is not set when setting dropdowns on page load
    var settingDropdowns = ! $(this).hasClass('searchTypes');

    dropdown = settingDropdowns ? dropdown : this;

    field = field || $(dropdown).val();


    var inputIndex = $('.searchTypes').index(dropdown);

    // Set the dropdown if this is a dropdown param
    if (settingDropdowns) {
        $('.searchTypes').eq(inputIndex).val(field);
    }

    var oneSearchValue = $('.searchValues').eq(inputIndex);

    var hasPlaceHolder = typeof jsVars['searcherFields'][field] == 'undefined'
        ? false : true;

    if (hasPlaceHolder
    &&  typeof jsVars['searcherFields'][field]['searcherDD'] != 'undefined'
    ) {
        var searchDropDown = $('<select>').attr('name', 'searchValues[]')
            .addClass('searchValues');

        $.each(jsVars['searcherDDs'][field], function (fieldName, fieldValue) {
            var option = $('<option>').attr('value', fieldName).text(fieldName);

            //add tooltips to select box of search dropdown if has hintField
            if (fieldValue.titleName) {
                option.attr('title', fieldValue.titleName);
            }
            searchDropDown.append(option);
        });

        oneSearchValue.replaceWith(searchDropDown);

        //when user chang value, the tooltip show on select box
        addTittleFromOptionToSelect(searchDropDown);
        $(searchDropDown[inputIndex]).change(function() {
            addTittleFromOptionToSelect($(this));
        });
        return;
    }

    // Make the field a text input if it isn't
    oneSearchValue = changeToTextInput(oneSearchValue, inputIndex);

    // Check if value is a date
    var dateData = $('option:selected', dropdown).attr('data-date');
    var isDate = dateData == 'isDate' ? true : false;

    var skipAutocomplete = jsVars.searcherFields.hasOwnProperty(field)
                        && jsVars.searcherFields[field].hasOwnProperty('acDisabled')
                        && jsVars.searcherFields[field].acDisabled;

    if (skipAutocomplete) {
        oneSearchValue.removeAttr('placeholder');
    } else {
        // Add or remove placeholder
        if (hasPlaceHolder) {
            oneSearchValue.prop({
                placeholder: '(autocomplete)',
                'value': ''
            }).autocomplete({
                source: jsVars['urls']['filter'] + '&type=' + field
            });
        } else {
            oneSearchValue.removeAttr('placeholder').prop('autoComplete', 'off');
        }
    }

    oneSearchValue = $('.searchValues').eq(inputIndex);

    isDate ? oneSearchValue.addClass('searcherDate') :
        removeDatepicker(oneSearchValue);

    oneSearchValue.val('');

}

/*
********************************************************************************
*/

function removeDatepicker(oneSearchValue)
{
    // Remove this class regardless incase someone clickes another options
    // without triggering the datepicker
    $(oneSearchValue).removeClass('searcherDate');
    if ($(oneSearchValue).hasClass('hasDatepicker')) {
        $(oneSearchValue).datepicker('destroy')
             .removeClass('searcherDate hasDatepicker')
             .removeAttr('id');
    }
}

/*
********************************************************************************
*/

function changeToTextInput(oneSearchValue, inputIndex)
{
    removeDatepicker(oneSearchValue);

    if (! oneSearchValue.is('input[type=text]')) {
        var searchText = $('<input/>').attr('type', 'text')
            .attr('name', 'searchValues[]')
            .addClass('searchValues');

        oneSearchValue.replaceWith(searchText);

        var container = searchText.parent();

        var containerPosition = container.position();
        var containerLeft = containerPosition.left;
        var containerWidth = container.width();

        var inputPosition = searchText.position();
        var inputLeft = inputPosition.left;
        var inputWidth = searchText.width();

        if (inputLeft + inputWidth - 2.5 > containerLeft + containerWidth) {

            var newWidth = containerLeft + containerWidth - inputLeft + 2.5;

            searchText.width(newWidth);
        }
    }
    // Reset the search value variable
    return $('.searchValues').eq(inputIndex);
}

/*
********************************************************************************
*/

function submitForm(event)
{
    // Export button click submits true data param
    var postSubmit = $(this).prop('data_post');
    if (postSubmit) {
        return true;
    }

    // Otherwise update the datatable
    typeof event == 'undefined' ? null : event.preventDefault();

    filterSearch();

    return false;
}

/*
********************************************************************************
*/

function addTittleFromOptionToSelect(htmlObject)
{
    var title = htmlObject.find('option:selected').attr('title');
    if (title) {
        htmlObject.attr('title', title);
        htmlObject.tooltip({content: title});
    }
}

/*
********************************************************************************
*/

function runSearcher()
{
    var $searcherForm = $('#searchForm');

    $searcherForm.empty();

    $.map(jsVars['searchParams'], function (params) {
        $.each(params, function (type, value) {
            $('<input/>')
                .addClass(type)
                .attr('type', 'text')
                .attr('name', type + '[]')
                .css('display', 'none')
                .val(value)
                .appendTo($searcherForm);
        });
    });

    filterSearch();
}

/*
********************************************************************************
*/
