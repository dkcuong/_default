<?php

namespace models;

class dbCommandFile
{
    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */

    function addMenuPage($data)
    {
        $hiddenName = $data['hiddenName'];
        $displayName = $data['displayName'];
        $search = $data['search'];
        $submenu = $data['submenu'];
        $class = $data['class'];
        $method = $data['method'];
        $red = getDefault($data['red'], 0);
        $displayOrder = getDefault($data['displayOrder']);
        $parentModelName = $data['parentModel'];
        $modelName = $data['model'];
        $after = getDefault($data['after']);
        $before = getDefault($data['before']);

        $parentModel = new $parentModelName($this->app);
        $model = new $modelName($this->app);

        $menuInfo = $parentModel->search([
            'search' => $search,
            'term' => $submenu,
            'oneResult' => TRUE,
        ]);

        $type = $before ? 'before' : 'after';
        $page = $before ? $before : $after;

        $valuesClause = $model->getPagesOrderQuery([
            'type' => $type,
            'page' => $page,
            'submenu' => $submenu,
            'subMenuID' => $menuInfo['id'],
            'displayOrder' => $displayOrder,
            'displayName' => $displayName,
            'hiddenName' => $hiddenName,
            'class' => $class,
            'method' => $method,
            'red' => $red,
            'active' => isset($data['active']) ? $data['active'] : 1,
        ]);

        $sql = $model->addRowQuery([
            'valuesClause' => $valuesClause,
            'subMenuID' => NULL,
            'displayName' => NULL,
            'displayOrder' => NULL,
            'hiddenName' => NULL,
            'class' => NULL,
            'method' => NULL,
            'red' => NULL,
            'active' => NULL,
        ]);

        return [
            'sql' => $sql,
            'check' => $model->search([
                'search' => 'hiddenName',
                'term' => $hiddenName,
                'oneResult' => TRUE,
                'returnQuery' => TRUE,
            ]),
            'callback' => function ($results) {
                return count($results);
            },
        ];
    }

    /*
    ****************************************************************************
    */

    function addMenuPageParameter($data)
    {
        $hiddenName = $data['hiddenName'];
        $name = $data['name'];
        $value = $data['value'];
        $parentModelName = $data['parentModel'];
        $modelName = $data['model'];

        $parentModel = new $parentModelName($this->app);
        $model = new $modelName($this->app);

        $valuesClause =
                $parentModel->getIDByHiddenNameQuery($name, $value, $hiddenName);

        return [
            'sql' => $model->addRowQuery([
                'valuesClause' => $valuesClause,
                'pageID' => NULL,
                'name' => NULL,
                'value' => NULL,
                'active' => NULL,
            ]),
            'check' => $model->search([
                'search' => ['hiddenName', 'name'],
                'term' => [$hiddenName, $name],
                'oneResult' => TRUE,
                'returnQuery' => TRUE,
            ]),
            'callback' => function ($results) {
                return count($results);
            },
        ];
    }

    /*
    ****************************************************************************
    */

    function addDatabase($data)
    {
        $description = getDefault($data['description']);
        $alias = $data['alias'];
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $dbName = $this->app->getDBName($alias);

        $key = $description ? $description : 'add ' . $dbName . ' database';

        return [
            $key => [

                'sql' => 'CREATE DATABASE ' . $dbName
                       . ' CHARSET=utf8 COLLATE=utf8_general_ci',

                'check' => 'SHOW DATABASES LIKE "' . $dbName . '"',

                'callback' => function ($results) {
                    return $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function dropTable($data)
    {
        $description = getDefault($data['description']);
        $table = $data['table'];
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $key = $description ? $description : 'remove ' . $table . ' table';

        return [
            $key => [

                'sql' => 'DROP TABLE ' . $table,

                'check' => 'SHOW TABLES LIKE "' . $table . '"',

                'callback' => function ($results) {
                    return ! $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function dropIndex($data)
    {
        $description = getDefault($data['description']);
        $table = $data['table'];
        $index = $data['index'];
        $type = getDefault($data['type'], NULL);
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $key = $description ? $description :
                'remove ' . $index . ' key from ' . $table . ' table';

        $clause = strtolower($type) == 'unique' ? 'AND Non_unique' : NULL;

        return [
            $key => [

                'sql' => 'ALTER TABLE ' . $table . ' DROP INDEX ' . $index,

                'check' => 'SHOW KEYS
                            FROM ' . $table . '
                            WHERE key_name = "' . $index . '"
                            ' . $clause,

                'callback' => function ($results) {
                    return ! $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function addIndex($data)
    {
        $description = getDefault($data['description']);
        $table = $data['table'];
        $fields = $data['fields'];
        $index = getDefault($data['index'], $fields);
        $type = getDefault($data['type'], NULL);
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $indexType = $type ? ' ' . strtolower($type) : NULL;

        $key = $description ? $description :
                'add ' . $index . $indexType . ' key to ' . $table . ' table';
        // primary key has no index name
        $indexName = strtoupper($type) == 'PRIMARY' ? NULL : $index;

        return [
            $key => [

                'sql' => 'ALTER TABLE ' . $table . '
                          ADD ' . strtoupper($type) . ' KEY '
                        . $indexName . ' (' . $fields . ')',

                'check' => 'SHOW KEYS
                            FROM ' . $table . '
                            WHERE key_name = "' . $index . '"',

                'callback' => function ($results) {
                    return $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function dropField($data)
    {
        $description = getDefault($data['description']);
        $table = $data['table'];
        $field = $data['field'];
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $key = $description ? $description :
                'remove ' . $field . ' field from ' . $table . ' table';

        return [
            $key => [

                'sql' => 'ALTER TABLE ' . $table . ' DROP ' . $field,

                'check' => 'SHOW FIELDS
                            FROM ' . $table  . '
                            WHERE Field = "' . $field . '"',

                'callback' => function ($results) {
                    return ! $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function addField($data)
    {
        $description = getDefault($data['description']);
        $table = $data['table'];
        $field = $data['field'];
        $fieldType = $data['fieldType'];
        $after = getDefault($data['after'], NULL);
        $default = getDefault($data['default'], NULL);
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $key = $description ? $description :
                'add ' . $field . ' field to ' . $table . ' table';

        $afterClause = $after ? ' AFTER ' . $after : NULL;

        $updateQuery = $default ?
                'UPDATE ' . $table . ' SET ' . $field . ' = ' . $default : NULL;

        return [
            $key => [

                'sql' => 'ALTER TABLE ' . $table . '
                          ADD COLUMN ' . $field . ' ' . $fieldType . $afterClause . '; '
                        . $updateQuery,

                'check' => 'SHOW FIELDS
                            FROM ' . $table . '
                            WHERE `Field` = "' . $field . '"',

                'callback' => function ($results) {
                    return $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function changeField($data)
    {
        $description = getDefault($data['description']);
        $table = $data['table'];
        $field = $data['field'];
        $oldName = getDefault($data['oldName'], $field);
        $fieldType = strtoupper($data['fieldType']);
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $nullClause = $defaultClause = $autoIncrementClause = $default = NULL;

        $key = $description ? $description :
                'change ' . $field . ' field structure in ' . $table
              . ' table to ' . strtolower($fieldType);

        if ($oldName == $field) {

            $nullPos = strpos($fieldType, 'NOT NULL');
            $nullPos = $nullPos === FALSE ? strpos($fieldType, 'NULL') : $nullPos;

            $type = substr($fieldType, 0, $nullPos);
            $cutString = substr($fieldType, $nullPos);

            $autoIncrementPos = strpos($cutString, 'AUTO_INCREMENT');

            if ($autoIncrementPos !== FALSE) {

                $autoIncrementClause = 'OR Extra != "auto_increment"';

                $cutString = trim(substr($cutString, 0, $autoIncrementPos));
            }

            $valueString = trim($cutString);

            $defaultPos = strpos($valueString, 'DEFAULT');

            if ($defaultPos !== FALSE) {

                $default = substr($valueString, $defaultPos + 8);

                $valueString = substr($valueString, 0, $defaultPos - 1);
            }

            $nullClause = 'OR `Null` != ';
            $nullClause .= $valueString == 'NOT NULL' ? '"NO"' : '"YES"';

            $defaultClause = 'OR `Default` ';
            $defaultClause .= ! $default || $default == 'NULL' ? 'IS NOT NULL' :
                    '!= ' . $default;

            $lowerTypeValue = strtolower($type);

            $clause = '`Field` = "' . $field . '"
                        AND   (Type != "' . trim($lowerTypeValue) . '"
                            ' . $nullClause . '
                            ' . $defaultClause . '
                            ' . $autoIncrementClause . '
                        )';
        } else {
            $clause = '`Field` = "' . $oldName . '"';
        }

        return [
            $key => [

                'sql' => 'ALTER TABLE ' . $table . '
                          CHANGE ' . $oldName . ' ' . $field . ' ' . $fieldType,

                'check' => 'SHOW FIELDS
                            FROM ' . $table . '
                            WHERE ' . $clause,

                'callback' => function ($results) {
                    return ! $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function deactivateSubmenu($data)
    {
        $description = getDefault($data['description']);
        $hiddenName = $data['hiddenName'];
        // negates key requires string type value
        $negates = getDefault($data['negates'], '');

        $key = $description ? $description :
                'remove ' . $hiddenName . ' submenu from the Main Menu';

        return [
            $key => [

                'sql' => 'UPDATE  pages p
                          JOIN      submenu_pages sp ON sp.pageID = p.id
                          JOIN      submenus sm ON sm.id = sp.subMenuID
                          SET     sp.active = 0
                                  sm.active = 0
                          WHERE   sm.hiddenName = "' . $hiddenName . '"',

                'check' => 'SELECT displayName
                            FROM   submenus
                            WHERE  hiddenName = "' . $hiddenName . '"
                            AND    active',

                'callback' => function ($results) {
                    return ! $results;
                },

                'negates' => $negates,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

}
