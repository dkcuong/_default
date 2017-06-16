<?php

namespace jQuery;

class reportIssues
{

    /*
    ****************************************************************************
    */

    function __construct($mvc)
    {
        $this->mvc = $mvc;

        $this->setDialogHTML();
    }

    /*
    ****************************************************************************
    */

    static function getUserData()
    {
        $userName = \access::$user['username'];

        $userInfo = \access::getUserInfoValue($userName);

        $userData = reset($userInfo);

        return reset($userData);
    }

    /*
    ****************************************************************************
    */

    function setDialogHTML()
    {
        $userData = self::getUserData();
        $noReply = \appConfig::getMailConfig();

        ob_start(); ?>

        <div id="reportIssueDialog">
            <form method="post" id="platesDetail" enctype="multipart/form-data"
                  action="<?php echo makeLink('issues', 'report') ?>" target="_blank">
                <div id="reportIssueHeader">
                    <div>
                        <div class="reportIssueHeaderTitle">To Email:</div>
                        <div class="reportIssueHeaderContent">
                            <?php echo $noReply['username']; ?>
                            <input type="hidden" value="support@seldatinc.com">
                        </div>
                    </div>
                    <div>
                        <div class="reportIssueHeaderTitle">From Email:</div>
                        <div class="reportIssueHeaderContent">
                            <input type="text" id="reportIssueEmail"
                                   name="reportIssueEmail"
                                   value="<?php echo $userData['email']; ?>">
                        </div>
                    </div>
                    <div>
                        <div class="reportIssueHeaderTitle">Phone:</div>
                        <div class="reportIssueHeaderContent">
                            <input type="text" id="reportIssuePhone"
                                   name="reportIssuePhone">
                        </div>
                    </div>
                </div>
                <div id="reportIssueBody">
                    <div>
                        <strong>Describe the Issue</strong><br>
                        <textarea id="reportIssueDescr" rows="5"
                                  name="reportIssueDescr"></textarea>
                    </div>
                    <div>
                        <input type="file" id="reportIssueFile" name="file">
                    </div>
                    <div>
                        <input type="checkbox" name="attachScreenshot">Attach screenshot
                    </div>
                    <div>
                        <img id="reportIssueScreenshot"></img>
                        <input type="hidden" id="reportIssueScreenshotValue"
                               name="reportIssueScreenshotValue">
                    </div>
                </div>
                <div id="reportIssueFooter">
                    <button type="submit" id="submitReportIssue">
                        Report the Issue
                    </button>
                </div>
            </form>
        </div>

        <?php return $this->mvc->reportIssueDialogHTML = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

}
