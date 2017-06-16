<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function reportIssuesController()
    {
        $post = $this->post;

        $userData = jQuery\reportIssues::getUserData();
        $noReply = \appConfig::getMailConfig();

        $fullName = $userData['firstName'] . ' ' . $userData['lastName'];

        $params = [
            'recipient' => $noReply['username'],
            'subject' => 'Issue Report',
            'body' => 'From: ' . $fullName . '<br>'
                . 'Email: ' . $post['reportIssueEmail'] . '<br>'
                . 'Phone: ' . $post['reportIssuePhone'] . '<br>'
                . 'Description: ' . $post['reportIssueDescr'] . '<br>',
        ];

        $fileName = $_FILES['file']['name'];

        if ($fileName) {

            $filePath = models\directories::getDir('uploads', 'reportIssuesFiles');

            $fileAttached = $filePath . '/' . $fileName;

            move_uploaded_file($_FILES['file']['tmp_name'], $fileAttached);

            $params['body'] .= 'File attached: ' . $fileName . '<br>';

            $params['files'][] = $fileAttached;
        } else {
            $params['body'] .= 'No File attached<br>';
        }

        $params['addReplyTo'] = [
            'email' => $post['reportIssueEmail'],
            'fullName' => $fullName,
        ];

        if (getDefault($post['attachScreenshot'])) {

            $screenshot = $post['reportIssueScreenshotValue'];

            $screenData = substr($screenshot, strpos($screenshot, ',') + 1);

            $unencodedScreenData = base64_decode($screenData);

            $filePath = models\directories::getDir('uploads',
                    'reportIssuesScreenshots');

            $fileName = 'Error Screenshot ' .  date('Y-m-d H_i_s', time())
                    . ' reported by ' . $fullName . '.png';

            $screenshotAttached = $filePath . '/' . $fileName;

            file_put_contents($screenshotAttached, $unencodedScreenData);

            $params['body'] .= 'Screenshot attached: ' . $screenshotAttached;

            $params['files'][] = $screenshotAttached;
        } else {
            $params['body'] .= 'No Screenshot attached';
        }

        \PHPMailer\send::mail($params); ?>

        <div class="showsuccessMessage">The issue report was sent.</div>

    <?php }

    /*
    ****************************************************************************
    */

}