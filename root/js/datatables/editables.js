//Editables autocomplete plugin
  
$.editable.addInputType('autocompleteData', {
    element : $.editable.types.text.element,
    plugin: function (settings, original) {
            if (typeof customAutoCompleteFunc != "undefined") {
                customAutoCompleteFunc(this, this.settings, original);
            } else {
                var colIndex = $(original).index();
                $('input', this).autocomplete({
                    source: jsVars['urls']['autocomplete'][colIndex]
                });
            }

        }
});

/*
********************************************************************************
*/

funcStack.editables = function () {
    $('#btnAddNewRow').click(function () {
        $('#addRowNotice').html(null);
    });
    
    $('.dynamicDT').each(function (index, table) {
        // Get Table ID
        var $currentTable = $(this);

        $currentTable.on('focusin', 'input[type=text]', function () {
            // make input the same width as the parent column
            
            var $tdTag = $(this).parent().parent();
            var col = $tdTag.parent().children().index($tdTag);

            var columnWidth = $currentTable.find('th').eq(col)[0].style.width;

            // convert to integer to be able to widen input box
            columnWidth = parseInt(columnWidth);
            // increase by 12 to widen a little input box
            columnWidth += 12;

            $(this).width(columnWidth+'px');
        });
    });
};

/*
********************************************************************************
*/

function addControllerSearch(aoData, table)
{
    var param = jsVars['dataTables'][table];

    if (typeof param['searchParams'] !== 'undefined') {
        aoData.searchParams = param['searchParams'];
    }

    if (typeof param['displayParams'] !== 'undefined') {
        aoData.displayParams = param['displayParams'];
    }
    
    return aoData;
}

/*
********************************************************************************
*/
