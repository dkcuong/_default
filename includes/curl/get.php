<?php

namespace curl;

class get
{

    function __construct($params)
    {
        $values = isset($params['values']) ? $params['values'] : [];

        if (isset($params['post'])) {

            $valuesString = NULL;

            foreach($values as $key => $value) {
                $valuesString .= $key . '=' . $value . '&';
            }

            $curl = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($curl,CURLOPT_URL, $params['url']);
            curl_setopt($curl,CURLOPT_POST, count($values));
            curl_setopt($curl,CURLOPT_POSTFIELDS, $valuesString);

        } else {

            $curl = curl_init($params['url']);
            curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

        $result = curl_exec($curl);

        // Debug CURL
        if (! $result && function_exists('isDev') && isDev()) {
            echo curl_error($curl);
        }

        curl_close($curl);

        $this->result = $result;
    }
}
