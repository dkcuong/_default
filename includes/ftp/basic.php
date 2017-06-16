<?php

namespace ftp;

class basic 
{
    
    static function upload($params)
    {
        $remote = isset($params['remotePath']) ? $params['remotePath'] 
            : basename($params['file']);

        // set up basic connection 
        $connection = ftp_connect($params['server']); 

        // login with username and password 
        ftp_login($connection, $params['username'], $params['password']); 

        // upload a file 
        
        echo ftp_put($connection, $remote, $params['file'], FTP_ASCII)
            ? 'Successfully uploaded '.$params['file'].'<br>'
            : 'There was a problem while uploading '.$params['file'].'<br>'; 

        // close the connection 
        ftp_close($connection); 
    }
}
