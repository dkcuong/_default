// Global array for storing instantiated datatables
var dtMaker = {},
    dataTables = {};

funcStack.datatables_ajax = function () {
    
    var dynamicDTs = $('.dynamicDT');
    
    $.makeArray(dynamicDTs).map(dtMaker.makeTable);
};

/*
********************************************************************************
*/

dtMaker = {

    old: {
        fnRowCallback: {},
        fnDrawCallback: {}
    },
    
    table: null,
    
    thisDT: null,

    tableID: null,
    
    callbacks: ['fnRowCallback', 'fnDrawCallback'],

    dtModsAreSet: false,
    
    makeTable: function (table) {

        // Change processing... text to a div
        $(table).on('draw.dt', dtMaker.textToDiv);

        // Get Table ID
        var tableID = dtMaker.tableID = table.id;

        dtMaker.thisDT = jsVars['dataTables'][tableID];

        dtMaker.addEditing();

        // Add modification properties
        $.extend(dtMaker.thisDT, dtMods[tableID]);
        
        dataTables[tableID] = $('#'+tableID).dataTable(dtMaker.thisDT);
        
        $(".dataTables_filter input")
        .unbind()
        .bind('keyup change', function(e) {
            if (this.value.length >= jsVars['quickSearchLength'] ||
                e.keyCode == 13) {

                dataTables[tableID].api().search(this.value).draw();
            } else if (this.value.length < jsVars['quickSearchLength'])  {
                dataTables[tableID].api().search(this.value);
            }

        });
    },
    
    /*
    ****************************************************************************
    */
   
    textToDiv: function () {
        var $div = $('<div>').html('Processing...');
        $('.dataTables_processing').html($div);
    },
    
    /*
    ****************************************************************************
    */
   
    addEditing: function () {
        
        if (typeof jsVars['editables'] === 'undefined') { 
            return;
        }
        
        dtMaker.dtModsAreSet = typeof dtMods[dtMaker.tableID] !== 'undefined';
        
        dtMaker.callbacks.map(function (callbackName) {
            dtMaker.processCallback(callbackName);
        });
    },
    
    /*
    ****************************************************************************
    */
   
    processCallback: function (callbackName) {   
        
        // Copy the previous row/draw callback and put it in the new one
        var funcSet = dtMaker.dtModsAreSet 
                && typeof dtMods[dtMaker.tableID][callbackName] === 'function';

        dtMaker.old[callbackName][dtMaker.tableID] = funcSet ?
            dtMods[dtMaker.tableID][callbackName] : false;

        if (funcSet) {
            delete dtMods[dtMaker.tableID][callbackName];
        }
        
        // Editables uses row ID for primary keys
        dtMaker.thisDT[callbackName] = dtMaker[callbackName];
    },
    
    /*
    ****************************************************************************
    */
   
    fnRowCallback: function (row, values, index) {
        // Custom AJAX Class stores the row ID at the end of each row
        var primaryKey = values.pop();

        $(row).attr('id', primaryKey);
        
        dtMaker.old['fnRowCallback'][dtMaker.tableID] ? 
            dtMaker.old['fnRowCallback'][dtMaker.tableID](row, values, index) : 
            null;
    },
    
    /*
    ****************************************************************************
    */
    
    fnDrawCallback: function () {
        // Add editability after each draw
        this.makeEditable(jsVars['editables'][dtMaker.tableID]);  
        dtMaker.old['fnDrawCallback'][dtMaker.tableID] ? 
            dtMaker.old['fnDrawCallback'][dtMaker.tableID]() : null;
    }

    /*
    ****************************************************************************
    */

};