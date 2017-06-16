/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

function reportIssuegModel()
{
    var self = this;

    //**************************************************************************

    self.open = function () {
        setTimeout(function () {
            html2canvas($('body'), {
                onrendered: function(canvas) {

                    var image = canvas.toDataURL('image/png');

                    $('#reportIssueScreenshotValue').val(image);
                    $('#reportIssueScreenshot').attr('src', image);

                    $('#reportIssueDialog').dialog({
                        title: 'Report An Issue',
                        autoOpen: false,
                        width: 380,
                        height: 530,
                        modal: true
                    }).dialog('open');
                }
            });
        }, 0);
    };

    //**************************************************************************

    return self;
};
