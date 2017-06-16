<?php

class environment
{
    
    static $environment = [
        
        'awms.lc' => [

            // Developers' Local Sites
            '/mvc/test' => 'local',

            // Developers' Local Sites
            '/wms' => 'local',
        
            // EDI Local Test Site
            '/mvc/edi' => 'local',

            // Database Cooridnation App
            '/mvc/dbSync' => 'local',

            // New Vietname Team Environment
            '/mvc/wms2' => 'local',

        ],
        
        '192.237.162.111' => [

            // Cron Request
            '/test/cronRequests' => 'production',

            // Live WMS
            '/mvc/live' => 'production',

            // Previous Live Site Before Importing Pallet Sheets
            '/mvc/wms' => 'production',

            // Making sure this works before moving to production
            '/test/testProdEnv' => 'production',

            // EDI Test Site
            '/mvc/edi' => 'development',

            // New EDI Test Site on Test MVC
            '/test/edi' => 'development',

            // Test Site After Inventory Optimizatoin
            '/test/wmsTest' => 'development',

            // Origianl Test Site
            '/mvc/seldat' => 'development',

            // New Jersey Employee Testing Site
            '/mvc/testWMS' => 'development',

            // LA Employee Testing Site
            '/mvc/testWMS2' => 'development',

            // Test Backed Up Data
            '/test/wmsTestBackup' => 'development',

            // Site for VN to make front end changes
            '/test/kiet' => 'development',

            // Test Database Cooridnation App
            '/test/dbSync' => 'development',

            // Prod Database Cooridnation App
            '/mvc/dbSync' => 'production',

            // New Vietname Team Environment
            '/testVietnam/wms' => 'development',
        ],

        '104.130.1.225' => [
            // Live WMS
            '/wms' => 'production',
        ],
        'wms-dev.seldatdirect.com' => [
            // Benchmark 5
            '/b6/wms' => 'development',
        ],
        
        'seldatawms.com' => [
            // Cron Request
            '/cronRequests' => 'production',

            // Live WMS
            '/wms' => 'production',
        ],
    ];
    
    /*
    ****************************************************************************
    */
    
    static function get() 
    {
        $serverName = \models\config::getServerVar('SERVER_NAME');

        $uri = models\config::get('site', 'uri');

        $environment = getDefault(self::$environment[$serverName][$uri]);
        
        $environment or die;
        
        return $environment;
    }
}
