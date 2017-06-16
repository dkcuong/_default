<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/


class view extends controller
{
  
    function runDatabaseCheckView()
    {
        if (! isset($this->post['runCheck'])) { ?>

            <h2>Perform database check:</h2>
            <form method="post" onsubmit="return confirm('Are you sure?');">
                <input type="submit" name="runCheck" value="Run check">
            </form>
        
        <?php
        } else {
            echo '<h2>Database check results:</h2>';
            if ($this->checkResults) { ?>
            
            <div class="showsuccessMessage">
            
            <?php
                foreach ($this->checkResults as $result) {
                    if ($result['update']) {
                        echo '<br><span style="color: grey">' . $result['check']
                            . '</span><br>';
                        echo $result['update'] . '<br><br>';
                    } else {
                        echo $result['check'] . '<br>';
                    }
                }
            ?>
                
            </div>
                
            <?php
            } else {
                echo 'Test returned no results';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function method2EmptyView()
    {
        
    }    

    /*
    ****************************************************************************
    */
}