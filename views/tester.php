<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function pagesTesterView()
    {
        ?>
        <button id="startSession" class="<?php echo $this->showStartButton; ?>">Start Test Session</button><br>

        <table id="testerTable">

        <?php

        foreach ($this->requests as $seriesData) {

            $requestID = $seriesData['requestID']; 
            
            if (isset($seriesData['request']['displayName'])) { ?>
            <tr>
                <td class="testCells testDescription">
                    Test: <?php 
                        echo $seriesData['request']['displayName']; ?>
                </td>
            </tr>
                <?php
            } ?>
            
            <tr>
                <td class="testCells testSeries">
                    Request Series: <?php echo $seriesData['request']['description']; ?>
                </td>
            </tr>
            <tr>
                <td class="testCells testURL">
                    URL: <?php echo $this->testURLs[$requestID]; ?>
                </td>
            </tr>
            <tr>
                <td class="testCells" class="resultCells"
                    id="result-<?php echo $requestID; ?>">
                    <a name="showResults<?php echo $requestID; ?>"></a>
                    <div class="resultTitles"></div>
                    <a href="#" class="toggleResults">Hide Results</a><br>
                    <iframe class="resultDisplays"></iframe>
                </td>
            </tr>

        <?php } ?>

        </table>

        <?php
    }

    /*
    ****************************************************************************
    */

    function listTesterView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;

        if ($this->addButton) {
            echo $this->searcherAddRowButton;
            echo $this->searcherAddRowFormHTML;
        }
    }

    /*
    ****************************************************************************
    */
}