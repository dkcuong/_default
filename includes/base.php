<?php

use models\config;

if (! class_exists('base')) {

    class base extends dbInfo
    {
        public $title = NULL;

        public $sharedParams = [];
        public $qsVars = [];

        public $get = [];
        public $post = [];
        public $files = [];
        public $session = [];

        public $includeCSS = [
            'extra/css/jquery/jquery-ui-1.10.4.custom.min.css' => TRUE,
            'extra/css/jquery/datatables/jquery.dataTables.css' => TRUE,
            'css/includes/issues.css' => TRUE,
        ];

        public $includeJS = [
            'extra/js/jquery/jquery-1.10.2.js' => TRUE,
            'extra/js/jquery/jquery-ui-1.10.4.custom.min.js' => TRUE,
            'extra/js/jquery/datatables/jquery.dataTables.editable.js' => TRUE,
            'extra/js/jquery/datatables/jquery.dataTables.min.js' => TRUE,
            'extra/js/jquery/jeditable/jquery.jeditable.js' => TRUE,
            'extra/js/jquery/jeditable/jquery.validate.js' => TRUE,
            'extra/js/jquery/jeditable/jeditableDatepicker.js' => TRUE,
            'js/html2canvas/html2canvas.js' => TRUE,
            'js/autoloader.js' => TRUE,
            'js/includes/issues.js' => TRUE,
        ];

        public $jsVars = [];

        /*
        ****************************************************************************
        */

        function setTitle()
        {
            $queryParams = $this->parseQueryString();

            $params = [
                appConfig::get('site', 'requestClass'),
                appConfig::get('site', 'requestMethod'),
            ];

            $pageClauses = [];

            foreach ($queryParams as $key => $value) {
                $pageClauses[] = ' name = ? AND value = ? ';
                $params[] = $key;
                $params[] = $value;
            }

            $pageClause = $pageClauses ? implode(' OR ', $pageClauses) : 1;

            $sql = 'SELECT    p.id,
                              p.displayName,
                              COUNT(pp.id) AS paramCount
                    FROM      pages p
                    LEFT JOIN page_params pp ON p.id = pp.pageID
                    JOIN      submenu_pages sp ON sp.pageID = p.id
                    WHERE     p.class = ?
                    AND       p.method = ?
                    AND       (' . $pageClause . ')
                    AND       sp.active
                    AND       (pp.active IS NULL
                        OR pp.active
                    )
                    GROUP BY  p.id
                    ORDER BY  paramCount DESC
                    LIMIT     1';

            $results = $this->queryResult($sql, $params);

            $this->title = $results['displayName'];
        }

        /*
        ************************************************************************
        */

        function getTitle()
        {
            echo $this->title;
        }

        /*
        ************************************************************************
        */

        function setJSVars()
        {
            config::getAppURL();

            $this->jsVars = config::get('site');

            $this->jsVars['urls']['ajaxErrorSubmit'] =
                jsonLink('ajaxErrorSubmit');

            $this->jsVars['urls']['reportIssue']
                = customJSONLink('appJSON', 'reportIssue');        }

        /*
        ************************************************************************
        */

        function setImageDir()
        {
            $this->imageDir = config::getImagesDir();
        }

        /*
        ************************************************************************
        */

        function loadJS()
        {
            if (! isset($this->includeJS)) {
                return FALSE;
            }

            $appURL = config::getAppURL();

            foreach (array_keys($this->includeJS) as $include) { ?>
                <script type="text/javascript" src="<?php
                    echo $appURL.'/'.$include.assembler::getJSToken();
                ?>"></script><?php
            }
        }

        /*
        ************************************************************************
        */

        function loadCSS()
        {
            if (! isset($this->includeCSS)) {
                return FALSE;
            }

            $appURL = config::getAppURL();

            foreach (array_keys($this->includeCSS) as $include) { ?>
                <link rel="stylesheet" href="<?php
                    echo $appURL.'/'.$include.assembler::getJSToken();
                ?>"><?php
            }
        }

        /*
        ************************************************************************
        */

        function parseQueryString()
        {
            // Import the url query string into the object
            $query = $this->getVar('query', 'getDef', []);

            if (! $query) {
                return [];
            }

            $queryArray = explode('/', $query);
            $qsVars = [];

            while ($name = array_shift($queryArray)) {
                $value = array_shift($queryArray);
                $qsVars[$name] = $value;
            }

            return $qsVars;
        }

        /*
        ************************************************************************
        */

        function callChildMethod($class, $method, $passVars=[])
        {
            // If first method request, instantiate the includes dir object
            if (! isset($this->$class)) {
                $this->$class = new $class();
            }

            $this->$class->passVars($passVars);
            return $this->$class->$method();
        }

        /*
        ************************************************************************
        */

        function storeParams($sharedParams)
        {
            $this->sharedParams = $sharedParams;
        }

        /*
        ************************************************************************
        */

        function getParams()
        {
            return $this->sharedParams;
        }

        /*
        ************************************************************************
        */

        function storeRequestValues()
        {
            // Store post and get vars in object
            $this->get = filter_var_array($_GET);
            $this->post = filter_var_array($_POST);
            $this->files = filter_var_array($_FILES);
            $this->session = filter_var_array($_SESSION);

            // Move the query string into get vars
            $qsVars = $this->parseQueryString();
            $filteredQSVars = filter_var_array($qsVars);

            // Add filtered qsVars to get property array
            $this->get = $this->get ? $this->get : [];
            $this->get = array_merge($this->get, $filteredQSVars);
        }

        /*
        ************************************************************************
        */

        function storeGet($index, $values)
        {
            $_GET[$index] = $this->get[$index] = $values;
        }

        /*
        ************************************************************************
        */

        function storePost($index, $values)
        {
            $_POST[$index] = $this->post[$index] = $values;
        }

        /*
        ************************************************************************
        */

        function storeSession($index, $values)
        {
            $_SESSION[$index] = $this->session[$index] = $values;
        }

        /*
        ************************************************************************
        */

        function getVar($index, $getDefault=FALSE, $default=NULL)
        {
            return $getDefault ? getDefault($this->get[$index], $default) :
                $this->get[$index];
        }

        /*
        ************************************************************************
        */

        function postVar($index, $getDefault=FALSE, $default=NULL)
        {
            return $getDefault ? getDefault($this->post[$index], $default) :
                $this->post[$index];
        }

        /*
        ************************************************************************
        */

        function filesVar($index, $getDefault=FALSE, $default=NULL)
        {
            return $getDefault ? getDefault($this->files[$index], $default) :
                $this->files[$index];
        }

        /*
        ************************************************************************
        */

        function sessionVar($index, $getDefault=FALSE, $default=NULL)
        {
            return $getDefault ? getDefault($this->session[$index], $default) :
                $this->session[$index];
        }

        /*
        ************************************************************************
        */

        function getArray($type)
        {
            switch ($type) {
                case 'get':
                    return $this->get;
                case 'post':
                    return $this->post;
                case 'files':
                    return $this->files;
                case 'session':
                    return $this->session;
            }
        }

        /*
        ************************************************************************
        */

        function setArray($type, $values)
        {
            if (! $values) {
                return;
            }

            switch ($type) {
                case 'get':
                    $_GET = $this->get = $values;
                    break;
                case 'post':
                    $_POST = $this->post = $values;
                    break;
                case 'files':
                    $_FILES = $this->files = $values;
                    break;
                case 'session':
                    $_SESSION = $this->session = $values;
                    break;
            }
        }

        /*
        ************************************************************************
        */

        function unsetSession($index)
        {
            unset($_SESSION[$index]);
        }
    }
}