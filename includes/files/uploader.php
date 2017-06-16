<?php

/*
********************************************************************************
* UPLOADER                                                                     *
********************************************************************************
*/

class uploader
{
    public $results;
    public $errors;
    public $acceptedExts = array(
        'doc' => 'application/vnd.msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dot' => 'application/msword',
        'htm' => 'text/html',
        'html' => 'text/html',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'pdf' => 'application/pdf',
        'rsm' => 'application/octet-stream',
        'rtf' => 'application/msword',
        'txt' => 'text/plain',
        'wpd' => 'application/octet-stream',
        'wps' => 'application/octet-stream',
        'xls' => 'application/vnd.ms-excel'
    );

    /*
    ****************************************************************************
    */

    function __construct(
        $database,               // Database object with getLink method
        $path = UPLOADS_ROOT,    // Upload destination    
        $inputName,              // Name of the html input value 
                                 // (in case of multiple) 
        $prefix = NULL,          // Prefix for session and upload tables
        $displayResults = FALSE, // Format of results if necessary
        $sizeLimit = 0,          // Limit upload size, 0 for max
        $foreignID = FALSE,      // Default tokens need a foreign key passed
        $overRideName = FALSE,   // Customize the DB table file name
        $content = FALSE,        // Override upload content
        $sessionToken = FALSE    // Select manual session token
    ) {
        $errors[] = $database ? NULL : 'No Database Connection';
        $errors[] = $path ? NULL : 'No File Path';
        $errors[] = $inputName ? NULL : 'Field name not found';

        // Get the session token if one wasn't passed
        $prefixVars = getPrefixVars($prefix);
        $camelID = $prefixVars['camelID'];
        if (! $sessionToken) {
            $sessionToken = $prefixVars['sessionToken'];
        }
        
        $errors[] = $sessionToken ? NULL : 'Session has expired';
       
        // Override the files db name if custom name was passed
        $fileName = $overRideName;

        $extension = getExtension($fileName);
        $fileType = getDefault($this->acceptedExts[$extension]);
        $fileSize = getDefault($_FILES[$inputName]['size'], 0);
        $fileError = getDefault($_FILES[$inputName]['error'], 0);
        
        if ($content === FALSE) { 
       
            if (! $files = getDefault($_FILES[$inputName])) {
                $errors[] = 'File not found';    
            }

            if ($sizeLimit && $fileSize > $sizeLimit) {
                $errors[] = 'File exceeds size limit';
            }

            if (! $fileName = getDefault($files['name'])) {
                $errors[] = 'File not found';    
            }

            $fileType = getDefault($files['type']);

            $ext = pathinfo($files['name'], PATHINFO_EXTENSION);
            if (! getDefault($this->acceptedExts[$ext])) {
                $errors[] = 'File not accepted format';
            }
        }
        $errors[] = $fileType ? NULL : 'File Type Not Accepted';
                        
        $tokenPrefix = $prefix ? $prefix.'_' : NULL;
                
        // Get appID from app session and the token of the session
        $foreignID = $foreignID
            ? $foreignID
            : getIDFromSession($database, $prefix);

        $errors[] = $foreignID ? NULL : 'Your session has expired';
        
        $errors = array_filter($errors);
        if (! empty($errors)) {
            $this->errors = $errors;
            return;
        }
        
        // If the upload insertion is linked to a default session token, store
        // the token in the upload table because the default session tokens are
        // overwritten
        $defaultTokenField = $camelID != $prefixVars['sessionID'] ? 
            'defaultToken,' : NULL;
        $defaultTokenValue = $camelID != $prefixVars['sessionID'] ? 
            $this->quote($sessionToken).',' : NULL;
        $defaultTokenUpdate = $camelID != $prefixVars['sessionID'] ? 
            'defaultToken = '.$this->quote($sessionToken).',' : NULL;
            
        // Create an upload file entry in the DB
        $sql = 'INSERT INTO '.$prefixVars['uploadsTable'].' (
                    '.$camelID.',
                    '.$defaultTokenField.'
                    fileName,
                    fileType,
                    fileSize,
                    inputName,
                    error,
                    uploadTime
                ) VALUES (
                    '.intval($foreignID).',
                    '.$defaultTokenValue.'
                    '.$this->quote($fileName).',
                    '.$this->quote($fileType).',
                    '.intval($fileSize).',
                    '.$this->quote($inputName).',
                    '.intval($fileError).',
                    '.$this->quote(NOW).'
                ) ON DUPLICATE KEY UPDATE
                    '.$defaultTokenUpdate.'
                    fileName = '.$this->quote($fileName).',
                    fileType = '.$this->quote($fileType).',
                    fileSize = '.intval($fileSize).',
                    error = '.intval($fileError).',
                    uploadTime = '.$this->quote(NOW);

        $insertion = runAppQuery($sql, $database);
        
        // Put the file in a hashed dir
        new fileMover(
            $inputName, 
            $path, 
            $sessionToken, 
            $overRideName, 
            $content
        );
        
        $this->results = array(
            'foreignID' => $foreignID,
            'name' => $fileName,
            'type' => $fileType,
            'size' => $fileSize,
            'type' => $fileType,
            'error' => $fileError,
        );
        
        switch ($displayResults) {
            case 'json':
                return json_encode($this->results);
                break;
            case 'php':
                return $this->results;
                break;
        }
    }
}