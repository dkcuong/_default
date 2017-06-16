// Auto load array
var funcStack = {};

// Customize datatables by adding attributes to their index
var dtMods = {};

function jsAutoload()
{
    $.each(funcStack, function (functionName, functionCode) {
        functionCode();
    });
    
    $(document).bind('keyup', function(e) {
        if (e.keyCode == 27 && window.parent.customShow !== undefined) {
            window.parent.customShow();
        }
    });
}