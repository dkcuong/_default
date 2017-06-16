<?php

namespace jQuery;

class tabs 
{
    function __construct($mvc, $tabs)
    {
        $this->mvc = $mvc;
        $mvc->includeJS['js/jQuery/tabs.js'] = TRUE;
        $mvc->includeCSS['css/jQuery/tabs.css'] = TRUE;

        $mvc->hasTabs = TRUE;

        $framesCount = 1;
        
        $firstTitle = array_shift($tabs);
        
        ob_start(); ?>
        <div id="tabs">
        <ul>
        <li><a href="#tabs-1"><?php echo $firstTitle; ?></a></li><?php 
        
        foreach (array_keys($tabs) as $tab) { ?>
            <li>
                <a class="getOnLoad" href="#iframe<?php echo $framesCount++; ?>">
                <?php echo $tab; ?>
                </a></li><?php
        } ?>
            
        </ul>
        <div id="tabs-1">
        <?php
        $mvc->jQueryTabsStart = ob_get_clean();

        $framesCount = 1;
        
        ob_start(); ?>

        </div>
        <?php foreach ($tabs as $url) { ?>
            <iframe class="innerFrames" id="iframe<?php echo $framesCount++; ?>" src="<?php echo $url; ?>">
            </iframe>
        <?php } ?>
        </div><?php
        
        $mvc->jQueryTabsEnd = ob_get_clean();
    }
    
    /*
    ****************************************************************************
    */
}
