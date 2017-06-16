<?php

class fileMover
{
    function __construct($inputName, $path, $sessionToken, $overRideName = FALSE,
        $stringContent = FALSE
    ) {
        $fileName = $overRideName
            ? $overRideName
            : getDefault($_FILES[$inputName]['name']);
            
        // Upload the file
        $subDirs = getSubDirs($sessionToken);

        if (! is_dir($uploadDir = $path.'/'.$subDirs[0])) {
            mkdir($uploadDir , 0600);
        }

        if (! is_dir($uploadSubDir = $uploadDir.'/'.$subDirs[1])) {
            mkdir($uploadSubDir , 0600);
        }

        $postName = str_replace(array(' ', ':', '-'), NULL, NOW);

        // If content has not been manually sent, use uploaded file
        if ($stringContent !== FALSE) {
            $fileHandle = fopen($uploadSubDir . '/' . $inputName . $postName, 'w')
            or die('Can\'t open the file');
            fwrite($fileHandle, $stringContent);
            fclose($fileHandle);
        } else {
            move_uploaded_file(
                $_FILES[$inputName]['tmp_name'],
                $uploadSubDir.'\\'.$inputName.$postName
            );
        }
    }
}