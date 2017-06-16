<?php 

class downloader 
{
    function __construct($database, $prefix, $id, $inputName, $customPath=FALSE) 
    {
        if (! $inputName || ! $id) {
            die('Missing Arguments');
        }
        $prefixVars = getPrefixVars($prefix);
        
        $filePath = $customPath ? $customPath : '';
        // Get the file path
        $sql = 'SELECT      fileName,
                            fileType,
                            uploadTime, 
                            defaultToken,
                            sessionToken
                FROM        '.$prefixVars['uploadsTable'].' u
                LEFT JOIN   '.$prefixVars['sessionsTable'].' s 
                ON          u.'.$prefixVars['camelID'].' = s.'.$prefixVars['sessionID'].' 
                WHERE       u.id = '.intval($id).'
                AND         inputName = '.$this->quote($inputName).'
                LIMIT       1';
        
        $result = runAppQuery($sql, $database);
        $row = mysql_fetch_assoc($result);

        // If the upload table isn't referencing a custom session table use the
        // default sessions stored in the uploads table
        $sessionToken = $row['defaultToken']
            ? $row['defaultToken']
            : $row['sessionToken'];

        $subDirs = getSubDirs($sessionToken);
        
        // Set the file type
        header('Content-type: '.$row['fileType']);

        // Set the file name
        header('Content-Disposition: attachment; filename="'.$row['fileName'].'"');
        
        // Output the file
        die('No longer allowed');
        readfile(UPLOAD_DIR.'/'.$filePath.'/'.$subDirs[0].'/'.$subDirs[1].'/'
        . $inputName.str_replace([' ', ':', '-'], NULL, $row['uploadTime']));
    }    
}

