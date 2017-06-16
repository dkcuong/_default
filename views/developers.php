<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function dbCheckerDevelopersView()
    { 
        ?>
        <form id="modifyStructure">
        <div id="addTest" name="addTest">
        <table id="testDescriptionTable" border="0">
            <tr>
                <td>Database:</td>
                <td><select name="database">
                    <option value="">Select</option>
                    <?php foreach ($this->dbKeys as $db) { ?>
                        <option><?php echo $db; ?></option>
                    <?php } ?>
                </select></td>
            </tr>
            <tr>
                <td>Description:</td>
                <td><input id="index"  name="command[index]" class="addTestInput" 
                           data-caption="Index"></td>
            </tr>
            <tr>
                <td>Command Type:</td>
                <td> 
                    <input type="radio" name="commandType" class="commandType" value="addData">
                    Add Data<br>
                    <input type="radio" name="commandType" class="commandType" value="modifyStructure">
                    Modify Database Structure
                </td>
            </tr>
            
            <tr class="modifyStructure toggleRows">
                <td>Command Model:</td>
                <td><select id="commandType" name="command[model]">
                    <option value="">Select</option>
                    <?php foreach ($this->queries as $index => $row) { ?>
                    <option value="<?php echo $index; ?>">
                        <?php echo $row['display']; ?></option>
                    <?php } ?>
                </select></td>
            </tr>
            <tr id="queryRow" class="modifyStructure toggleRows">
                <td>Query:</td>
                <td><?php foreach ($this->queryForms as $form) {
                    echo $form;
                } ?></td>
            </tr>
            
            <tr class="addData toggleRows">
                <td>Data Type:</td>
                <td><select id="dataType" name="command[dataType]">
                    <option value="">Select</option>
                    <?php foreach ($this->dataTypes as $index => $row) { ?>
                    <option value="<?php echo $index; ?>">
                        <?php echo $row['display']; ?></option>
                    <?php } ?>
                </select></td>
            </tr>
            <tr class="addData toggleRows">
                <td>Data Values:</td>
                <td><?php foreach ($this->dataTypes as $dataTypeID => $row) { ?>
                    <div id="<?php echo $dataTypeID; ?>" class="dataTypeTables">
                    <?php foreach ($row['targets'] as $target) { 
                        echo $target; ?>: <input name="dataInputs[<?php 
                        echo $dataTypeID; ?>][<?php echo $target; ?>]"><br><?php
                    } ?>
                    </div><?php 
                } ?></td>
            </tr>
            
            <tr>
                <td>Negates (optional):</td>
                <td><input id="negates" name="command[negates]" class="addTestInput"></td>
            </tr>
        </table>
        <br>
        <button id="addTestButton">Add Command</button>
        </div>
        </form>
            
        <br>
        <iframe id="commandsFrame" src="<?php echo $this->iframeURL; ?>"></iframe>
        <?php
    }

    /*
    ****************************************************************************
    */

    function dbCommandsIframeDevelopersView()
    {
        $commandCount = 1;
        ?>
        <button id="validate">Run Validation</button>

        <table id="commandsTable"><tr>
            <th>#</th>
            <th>Status</th>
            <th>Results</th>
            <th>Database</th>
            <th>Description</th>
            <th>Command</th>
            <th>Test</th>
            <th>Row Assertion</th>
            </tr><?php

        foreach ($this->dbCommands as $database => $commands) {
            $column = array_column($commands, 'negates');
            $negated = array_flip($column);

            foreach ($commands as $command) {
                $description = $command['description'];
                $isActive = ! isset($negated[$description]);
                $status = $isActive ? 'Active' : 'Negated';
                $untested = $isActive ? 'Not Tested' : 'Inactive';
                $resultsClass = $isActive ? NULL : 'good';
                ?>
                <tr>
                    <td><?php echo $commandCount++; ?></td>
                    <td><?php echo $status; ?></td>
                    <td class="testResults <?php echo $resultsClass; ?>"><?php echo $untested; ?></td>
                    <td><?php echo $database; ?></td>
                    <td><?php echo $description; ?></td>
                    <td><pre class="commands"
                              data-test="<?php
                                echo htmlentities($command['check']); ?>"
                              data-id="<?php echo $description; ?>"
                              data-db="<?php echo $database; ?>"
                              data-active="<?php echo intval($isActive); ?>"
                        ><?php echo $command['sql']; ?></pre></td>
                    <td><pre class="tests"><?php 
                        echo $command['check']; ?></pre></td>
                    <td><pre><?php 
                        echo getDefault($command['rowAssertDisplay']); 
                        ?></pre></td>
                </tr><?php

            }
        } ?>
        </table>

        <div title="Failed Test" id="failedTest" class="hidden">
            <p>This test has failed.</p>
            <span id="displayTest"></span>
            <p>Test Results:</p>
            <pre id="testResults"></pre>
            <p>Would you like to run the corresponding update or continue?</p>
            <span class="message" id="displayUpdate"></span>
        </div>

        <div title="Update Failure" id="updateFail" class="hidden">
            <span></span>
        </div>        <?php
    }

}