<?php
    require_once('classes/MiniTemplator.php');
    require_once('database/functions.php');

    session_start();

    function generateRegionList($t, $conn) {
        foreach(getAllRegions($conn) as $region) {
            $t->setVariable('region_id', $region['region_id']);
            $t->setVariable('region_name', $region['region_name']);
            $t->addBlock("regionListOption");
        }
    }

    function generateGrapeVarietyList($t, $conn) {
        foreach(getAllGrapeVarieties($conn) as $variety) {
            $t->setVariable('variety_id', $variety['variety_id']);
            $t->setVariable('variety_name', $variety['variety']);
            $t->addBlock('varietyListOption');
        }
    }

    function generateWineYearList($t, $conn) {
        $bounds = getWineYearBounds($conn);

        for ($i = $bounds['min']; $i <= $bounds['max']; $i++) {
            $t->setVariable('year', $i);
            $t->addBlock('wineYearListOption');
        }
    }

    function generateSearchPage($conn) {
        $t = new MiniTemplator();
        $t->readTemplateFromFile('templates/search.html');

        // Generate region drop down list
        generateRegionList($t, $conn);

        // Generate grape variety drop down list
        generateGrapeVarietyList($t, $conn);

        // Generate wine year drop down list
        generateWineYearList($t, $conn);

        $t->generateOutput();
    }

    // Establish a connection to the database
    $conn = connect_to_db();

    // Print error message and die if unable to
    // connect to the database
    if (get_class($conn) === "PDOException") {
        echo $conn->getMessage() . PHP_EOL;
        die();
    }

    generateSearchPage($conn);
?>
