funcStack.jqueryTabs = function () {
    $('#tabs').tabs({
        beforeActivate: function( event, ui ) {
            $('iframe.innerFrames').css('padding', 0);     
        }
    });
    
    $('iframe.innerFrames').load(function () {
        var newHeight = this.contentWindow.document.body.scrollHeight + 10;
        this.style.height = newHeight + 'px';
    });
};
