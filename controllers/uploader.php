<?php 

/*
********************************************************************************
* UPLOADER CLASS CONTROLLER METHODS                                            *
********************************************************************************
*/

class controller extends template
{

    function applicantUploaderController()
    {
        $qsVars = $this->qsVars;
        if (! $inputName = getDefault($qsVars['inputName'])) {
            die('Field name not found.');
        }

        if (! $files = $_FILES['applicantUpload']) {
            die('File not found.');    
        }

        if (! $applicantSession = getDefault($_SESSION['token'])) {
            die('Session has expired.');
        }

        if ($files['size'][$inputName] > 100000) {
            die('File exceeds size limit.');
        }

        if (! $files['name'][$inputName]) {
            die('File not found.');    
        }

        $acceptedUploadFormats = array(
            'image/gif'       => TRUE,
            'image/jpeg'      => TRUE,
            'image/pjpeg'     => TRUE,
            'image/png'       => TRUE,
            'image/svg+xml'   => TRUE,
            'image/tiff'      => TRUE,    
            'application/pdf' => TRUE,
        );
            
        $fileType = getDefault($files['type'][$inputName]);
            
        if (! getDefault($acceptedUploadFormats[$fileType])) {
            die('File not image or PDF format.');
        }


        // Get offerID from app session and the token of the session

        $sql = 'SELECT      offerID,
                            offerToken
                FROM        applicant_sessions s
                LEFT JOIN   offers o ON o.id = offerID
                WHERE       sessionToken = ' . $this->quote($applicantSession);
                
        $session = runQuery($sql, $this->getLink('application'));

        $session or die('Your session has expired.');

        // Create an upload file entry in the DB
        $sql = 'INSERT INTO offer_uploads (
                    offerID,
                    fileName,
                    fileType,
                    fileSize,
                    inputName,
                    error
                ) VALUES (
                    ' . intval($session['offerID']) . ',
                    ' . $this->quote($files['name'][$inputName]) . ',
                    ' . $this->quote($files['type'][$inputName]) . ',
                    ' . intval($files['size'][$inputName]) . ',
                    ' . $this->quote($qsVars['inputName']) . ',
                    ' . intval($files['error'][$inputName]) . '
                ) ON DUPLICATE KEY UPDATE
                    fileName = ' . $this->quote($files['name'][$inputName]) . ',
                    fileType = ' . $this->quote($files['type'][$inputName]) . ',
                    fileSize = ' . intval($files['size'][$inputName]) . ',
                    error = ' . intval($files['error'][$inputName]) . ',
                    uploadTime = NOW()';

        $insert = runQuery($sql, $this->getLink('application'));

        // Upload the file
        $subDir = substr($session['offerToken'], 0, 1);
        $subSubDir = substr($session['offerToken'], 0, 2);


        if (! is_dir($uploadDir = 'uploads\\applicants\\' . $subDir)) {
            mkdir($uploadDir, 0600);
        }

        if (! is_dir($uploadSubDir = $uploadDir . '\\' . $subSubDir)) {
            mkdir($uploadSubDir, 0600);
        }

        move_uploaded_file(
            $files['tmp_name'][$inputName],
            $uploadSubDir . '\\' . $files['name'][$inputName]
        );

        echo makeLink(
            'applicantDownloader&name=' . $files['name'][$inputName], 
            $files['name'][$inputName] . ' <i>(' .
            round($files['size'][$inputName] / 1000, 1) . ' KB)</i>'
        );

    }
    
}