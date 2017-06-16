<?php 

/*
 * These will be moved to respective classes
 * 
********************************************************************************
* DATABASE FUNCTIONS                                                           *
********************************************************************************
*/

/*
********************************************************************************
* EMPLOYEE RELATED FUNCTIONS                                                   *
********************************************************************************
*/


/*

********************************************************************************
* SECONDARY DATABASE FUNCTIONS                                                 *
********************************************************************************

*/

/*
********************************************************************************
* APPLICANT RELATED FUNCTIONS                                                  *
********************************************************************************
*/

    function getApplicants($database = FALSE, $whereClause = 1, $oneResult = FALSE)
    {
        $sql = 'SELECT app_id,
                       email,
                       fname,
                       lname,
                       CONCAT(fname," ",lname) AS fullName,
                       CONCAT(lname,", ",fname) AS lfFullName
                FROM   applicants
                WHERE  ' . $whereClause;

        $results = runQuery($sql, $database->getLink('applicant'), TRUE);
                
        $applicants = mysqlArray($results);
        
        return getFirstElement($applicants, $oneResult);
    }

    /*
    ****************************************************************************
    */
    
    function getLimitedApplicantIDs($database = FALSE, $whereClause = 1, $oneResult = FALSE)
    {
        $sql = 'SELECT applicantID,
                       active,
                       id
                FROM   applicant_ids
                WHERE  ' . $whereClause;
        $results = runQuery($sql, $database->getLink('application'), TRUE);
        $applicantsID = mysqlArray($results);

        // Get all applicants by default
        $applicantIDsClause = '1';
        // Get count of second table
        $tableRatio = count($applicantsID) ?
            // If applicantIDs default to 0
            getTableCount($database->getLink('applicant'), 'applicants') / count($applicantsID) : 1;

        if ($tableRatio > 5) {
            // If second table is five times the size of applicants, use long query
            $applicantIDsClause = '0';
            foreach ($applicantsID as $applicantID => $applicant) {
                $applicantIDsClause .= ' OR app_id = ' . $applicantID;
            }
        }

        $applicants = getApplicants($database, $applicantIDsClause, $oneResult);
        
        return getFirstElement($applicants, $oneResult);

    }
/*
********************************************************************************
* EMPLOYEE RELATED FUNCTIONS                                                   *
********************************************************************************
*/
 
    function getUsersByEmployeeID($database = FALSE, $whereClause = 1, $oneResult = FALSE)
    {
        // Get user table
        $sql = 'SELECT id,
                       username,
                       level
                FROM   users_access
                WHERE  ' . $whereClause;
                
        $users = $database->queryResults($sql);

        // Get all employees by default
        $employeeIDsClause = '1';
        // Get count of second table
        $tableRatio = count($users) ?
            // If users default to 0
            getTableCount($database->getLink('employee'), 'employees_master') / count($users) : 1;

        if ($tableRatio > 5) {
            // If second table is five times the size of users, use long query
            $employeeIDsClause = '0';
            foreach ($users as $employeeID => $user) {
                $employeeIDsClause .= ' OR id = ' . $employeeID;
            }
        }

        $results = getEmployees($database, $employeeIDsClause, FALSE, TRUE);
        while ($employee = mysql_fetch_assoc($results)) {
            $employeeID = $employee['id'];
            if (isset($users[$employeeID])) { 
                foreach ($employee as $fieldName => $value) {
                    $users[$employeeID][$fieldName] = $value;
                }
            }
        }
        
        return getFirstElement($users, $oneResult);
    }
    
    /*
    ****************************************************************************
    */

    function getEmployeeExceptions($link = FALSE, $oneResult = FALSE)
    {
        $sql = 'SELECT incorrect,
                       correct,
                       id
                FROM   employee_exceptions';
                
        $results = runQuery($sql, $link, TRUE);

        $employeeExceptions = mysqlArray($results);

        return getFirstElement($employeeExceptions, $oneResult);
    }







