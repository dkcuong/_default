/*[
    {
        "command": {
            "model": "removeField",
            "index": "remove inventoryBatch field from invoices_receiving table",
            "negates": ""
        },
        "queryInfo": {
            "table": "invoices_receiving",
            "dropField": "inventoryBatch"
        },
        "hash": "a7555c464ace9b71fbbabdca3690369b"
    },
    {
        "command": {
            "model": "removeField",
            "index": "remove invoiceBatch field from invoices_receiving table",
            "negates": ""
        },
        "queryInfo": {
            "table": "invoices_receiving",
            "dropField": "invoiceBatch"
        },
        "hash": "735e56642c515f0b9f297e2d30aa4554"
    },
    {
        "command": {
            "model": "updateFieldValue",
            "index": "update status METR to TROM",
            "negates": ""
        },
        "queryInfo": {
            "table": "statuses",
            "updateField": "shortName",
            "updateValue": "METR",
            "searchField": "shortName",
            "searchValue": "TROM"
        },
        "hash": "b01bd401340bf7008bd2345f41a190c4"
    },
    {
        "command": {
            "model": "addField",
            "index": "add inventoryBatch field to invoices_storage table",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE invoices_storage",
            "ADD COLUMN inventoryBatch INT(8) ",
            "NOT NULL AFTER recNum;"
        ],
        "queryInfo": {
            "table": "invoices_storage",
            "fieldName": "inventoryBatch"
        },
        "hash": "a4f646e31fb59c4e2ef652bd68ce891d"
    },
    {
        "command": {
            "model": "updateField",
            "index": "change recNum field structure in inventory_batches table",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE inventory_batches",
            "CHANGE recNum recNum INT(8) NOT NULL"
        ],
        "queryInfo": {
            "table": "inventory_batches",
            "fieldName": "recNum"
        },
        "rowAssert": [
            {
                "name": "Type",
                "compare": "!=",
                "value": "int(8)"
            },
            {
                "name": "Null",
                "compare": "!=",
                "value": "NO"
            },
            {
                "name": "Default",
                "compare": "IS NOT NULL",
                "value": ""
            }
        ],
        "hash": "bb898cfb5a0ee0b0a67a46debd9d3b0d"
    },
    {
        "command": {
            "model": "updateField",
            "index": "change recNum field structure in inventory_containers table",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE inventory_containers",
            "CHANGE recNum recNum INT(8) NOT NULL AUTO_INCREMENT"
        ],
        "queryInfo": {
            "table": "inventory_containers",
            "fieldName": "recNum"
        },
        "rowAssert": [
            {
                "name": "Type",
                "compare": "!=",
                "value": "int(8)"
            },
            {
                "name": "Null",
                "compare": "!=",
                "value": "NO"
            },
            {
                "name": "Default",
                "compare": "IS NOT NULL",
                "value": ""
            },
            {
                "name": "Extra",
                "compare": "!=",
                "value": "auto_increment"
            }
        ],
        "hash": "1caffcc8e9a7aa91509aa753ad68b101"
    },
    {
        "command": {
            "model": "updateField",
            "index": "change recNum field structure in invoices_receiving table",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE invoices_receiving",
            "CHANGE recNum recNum INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL"
        ],
        "queryInfo": {
            "table": "invoices_receiving",
            "fieldName": "recNum"
        },
        "rowAssert": [
            {
                "name": "Type",
                "compare": "!=",
                "value": "int(8) unsigned zerofill"
            },
            {
                "name": "Null",
                "compare": "!=",
                "value": "YES"
            },
            {
                "name": "Default",
                "compare": "IS NOT NULL",
                "value": ""
            }
        ],
        "hash": "e23c3e48358d492129fa8579bb6e7b52"
    },
    {
        "command": {
            "model": "updateField",
            "index": "change recNum field structure in invoices_storage table",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE invoices_storage",
            "CHANGE recNum recNum INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL"
        ],
        "queryInfo": {
            "table": "invoices_storage",
            "fieldName": "recNum"
        },
        "rowAssert": [
            {
                "name": "Type",
                "compare": "!=",
                "value": "int(8) unsigned zerofill"
            },
            {
                "name": "Null",
                "compare": "!=",
                "value": "YES"
            },
            {
                "name": "Default",
                "compare": "IS NOT NULL",
                "value": ""
            }
        ],
        "hash": "d376fb35f34aa05aa6f65652656bb60e"
    },
    {
        "command": {
            "model": "moveTable",
            "index": "moving group_users table from users to app database",
            "negates": ""
        },
        "queryInfo": {
            "tableName": "group_users",
            "databaseAlias": "users"
        },
        "hash": "93b7023c8f23b009d69419b02afef643"
    },
    {
        "command": {
            "model": "updateField",
            "index": "eplace group_users table active column data with values that are congruent to code",
            "negates": ""
        },
        "queryInfo": {
            "table": "group_users",
            "fieldName": "active"
        },
        "customQuery": [
            "ALTER TABLE group_users",
            "CHANGE active oldActive INT(2) NULL DEFAULT NULL;",
            "",
            "ALTER TABLE group_users ADD active TINYINT(1) NOT NULL DEFAULT 1;",
            "",
            "UPDATE group_users g",
            "JOIN   statuses s ON s.id = g.oldActive",
            "SET    active = s.displayName = \"Active\";",
            "",
            "ALTER TABLE group_users DROP oldActive;"
        ],
        "rowAssert": [
            {
                "name": "Type",
                "compare": "!=",
                "value": "tinyint(1)"
            }
        ],
        "hash": "effba1f768f1a0b1e17f5418239d7f9f"
    },
    {
        "command": {
            "model": "updateField",
            "index": "replace vendors table active column data with values that are congruent to code",
            "negates": ""
        },
        "queryInfo": {
            "table": "vendors",
            "fieldName": "active"
        },
        "customQuery": [
            "ALTER TABLE vendors",
            "CHANGE active oldActive INT(2) NULL DEFAULT NULL;",
            "",
            "ALTER TABLE vendors ADD active TINYINT(1) NOT NULL DEFAULT 1;",
            "",
            "UPDATE group_users g",
            "JOIN   statuses s ON s.id = g.oldActive",
            "SET    active = s.displayName = \"Active\";",
            "",
            "ALTER TABLE group_users DROP oldActive;"
        ],
        "rowAssert": [
            {
                "name": "Type",
                "compare": "!=",
                "value": "tinyint(1)"
            }
        ],
        "hash": "26d8e22e5a04b91a9a7ad4c399670757"
    },
    {
        "command": {
            "model": "addIndexes",
            "index": "add autoincrement and indices to group_users that was moved from users database",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE group_users",
            "ADD PRIMARY KEY (id),",
            "ADD UNIQUE KEY userID (userID, vendorID),",
            "ADD KEY active (active);",
            "",
            "ALTER TABLE group_users",
            "CHANGE id id INT(5) NOT NULL AUTO_INCREMENT"
        ],
        "queryInfo": {
            "tableName": "group_users",
            "searchName": "key_name",
            "searchValue": "PRIMARY"
        },
        "hash": "ceed6e13289d60215038235b26f763b3"
    },
    {
        "command": {
            "model": "addField",
            "index": "add barcode field to transfers table",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE transfers",
            "ADD COLUMN barcode VARCHAR(20) NOT NULL; ",
            "UPDATE transfers SET barcode = LEFT(MD5(id), 20);"
        ],
        "queryInfo": {
            "table": "transfers",
            "fieldName": "barcode"
        },
        "hash": "b06ab1334b0cde8478696fee10fd7eb4"
    },
    {
        "command": {
            "model": "addField",
            "index": "add discrepancy field to transfers table",
            "negates": ""
        },
        "customQuery": [
            "ALTER TABLE transfers",
            "ADD COLUMN discrepancy INT(8) DEFAULT NULL; "
        ],
        "queryInfo": {
            "table": "transfers",
            "fieldName": "discrepancy"
        },
        "hash": "f97b34f0de2350231f93a2261b86e82b"
    },
    {
        "commandType": "addData",
        "dataInputs": {
            "red": "0",
            "class": "scanners",
            "active": "1",
            "method": "confirmMezzanineTransfers",
            "hiddenName": "confirmMezzanineTransfers",
            "displayName": "Confirm Mezzanine Transfers",
            "submenuHidden": "administration",
            "itemBefore": "mezzanineInventoryTransfer",
            "itemAfter": "editupcs"
        },
        "command": {
            "model": "page",
            "index": "make a page for Mezzanine transfer confirmation scanner after Mezzanine Inventory Transfer page",
            "negates": null
        },
        "hash": "1239d02275a9eaa078beb6708f99347a"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "addField",
            "index": "add active field to upcs_assigned table",
            "negates": ""
        },
        "queryInfo": {
            "table": "upcs_assigned",
            "fieldName": "active"
        },
        "customQuery": [
            "ALTER TABLE upcs_assigned",
            "ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1;"
        ],
        "hash": "e2a57445c3f0cb8d1cc211ddf75a0d79"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "addTable",
            "index": "create awms_new_features table",
            "negates": ""
        },
        "queryInfo": {
            "tableName": "awms_new_features"
        },
        "customQuery": [
            "CREATE TABLE `awms_new_features` (",
            "    `id` int(11) NOT NULL AUTO_INCREMENT,",
            "    `versionID` int(11) NOT NULL,",
            "    `featureName` varchar(255) NOT NULL,",
            "    `featureDescription` varchar(255) NOT NULL,",
            "    `date` datetime NOT NULL,",
            "    `active` tinyint(1) NOT NULL DEFAULT \"1\",",
            "    PRIMARY KEY (`id`),",
            "    KEY `versionID` (`versionID`)",
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8"
        ],
        "hash": "d3d6cddfedfa3aa26c22603175e9acfa"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "addTable",
            "index": "create release_versions table",
            "negates": ""
        },
        "queryInfo": {
            "tableName": "release_versions"
        },
        "customQuery": [
            "CREATE TABLE `release_versions` (",
            "    `id` int(11) NOT NULL AUTO_INCREMENT,",
            "    `versionName` varchar(50) NOT NULL,",
            "    `date` datetime NOT NULL,",
            "    PRIMARY KEY (`id`)",
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8"
        ],
        "hash": "f22901496229264680a1fdfbfbcebe36"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "addTable",
            "index": "Create version_info table",
            "negates": ""
        },
        "queryInfo": {
            "tableName": "version_info"
        },
        "customQuery": [
            "CREATE TABLE `version_info` (",
            "    `id` int(11) NOT NULL AUTO_INCREMENT,",
            "    `userID` int(11) NOT NULL,",
            "    `versionID` int(11) NOT NULL,",
            "    `isShow` tinyint(1) NOT NULL DEFAULT \"1\",",
            "    PRIMARY KEY (`id`),",
            "    UNIQUE KEY `userID` (`userID`)",
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8"
        ],
        "hash": "3f1e5be3bac6283dc022ce74512c614b"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "dropField",
            "index": "remove active field from upcs_assigned table",
            "negates": "add active field to upcs_assigned table"
        },
        "queryInfo": {
            "table": "upcs_assigned",
            "dropField": "active"
        },
        "hash": "379879c6d8df9f6e684f7b1eaab10796"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "replaceIndex",
            "index": "change unique index in upcs_assigned table",
            "negates": ""
        },
        "rowAssert": [
            {
                "name": "Non_unique",
                "compare": "!=",
                "value": "1"
            }
        ],
        "queryInfo": {
            "tableName": "upcs_assigned",
            "searchName": "key_name",
            "searchValue": "userID"
        },
        "customQuery": [
            "ALTER TABLE upcs_assigned",
            "DROP INDEX userID,",
            "ADD UNIQUE userID (userID, upcID)"
        ],
        "hash": "8245ef9a6a4711f20b76cd0c072d5628"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "addField",
            "index": "add hiddenName field to groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "fieldName": "hiddenName"
        },
        "customQuery": [
            "ALTER TABLE groups",
            "ADD COLUMN hiddenName VARCHAR(50) NOT NULL AFTER groupName;"
        ],
        "hash": "edc0d44b4453b2adaf42cd0f3c151236"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "updateRowField",
            "index": "update Manager hidden name in groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "updateField": "hiddenName",
            "updateValue": "manager",
            "confirmField": "groupName",
            "confirmValue": "Manager"
        },
        "hash": "a549c2c42e9d594317a028b1ede0178a"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "updateRowField",
            "index": "update CSR hidden name in groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "updateField": "hiddenName",
            "updateValue": "csr",
            "confirmField": "groupName",
            "confirmValue": "CSR"
        },
        "hash": "a2d3bc8eee3bf61e9f69c64e243a06c6"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "updateRowField",
            "index": "update Warehouse Staff hidden name in groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "updateField": "hiddenName",
            "updateValue": "warehouseStaff",
            "confirmField": "groupName",
            "confirmValue": "Warehouse Staff"
        },
        "hash": "0a0c1d1aa8810a5232a5589326b6131d"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "updateRowField",
            "index": "update Data Entry hidden name in groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "updateField": "hiddenName",
            "updateValue": "dataEntry",
            "confirmField": "groupName",
            "confirmValue": "Data Entry"
        },
        "hash": "e1ce8c0fa32e016759722db0438b2fb6"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "updateRowField",
            "index": "update Picking Staff hidden name in groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "updateField": "hiddenName",
            "updateValue": "pickingStaff",
            "confirmField": "groupName",
            "confirmValue": "Picking Staff"
        },
        "hash": "493a43a3576a9e6363c34b9750ee8b16"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "updateRowField",
            "index": "update Shipping Staff hidden name in groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "updateField": "hiddenName",
            "updateValue": "shippingStaff",
            "confirmField": "groupName",
            "confirmValue": "Shipping Staff"
        },
        "hash": "66cb4015a9f82fa48f428d0eea6fd031"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "updateRowField",
            "index": "update Invoicing Staff hidden name in groups table",
            "negates": ""
        },
        "queryInfo": {
            "table": "groups",
            "updateField": "hiddenName",
            "updateValue": "invoicingStaff",
            "confirmField": "groupName",
            "confirmValue": "Invoicing Staff"
        },
        "hash": "c3e105ee5aec579ac3d7294e1c5418c9"
    },
    {
        "commandType": "modifyStructure",
        "command": {
            "model": "addIndexes",
            "index": "add hiddenName unique key to groups table",
            "negates": ""
        },
        "queryInfo": {
            "tableName": "groups",
            "searchName": "key_name",
            "searchValue": "hiddenName"
        },
        "customQuery": [
            "ALTER TABLE groups",
            "ADD UNIQUE KEY hiddenName (hiddenName)"
        ],
        "hash": "61183fc5f18b3113a93a5a02fc7b64ca"
    }
]*/