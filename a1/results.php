<?php
    require_once('classes/MiniTemplator.php');

    session_start();

    function generateResultsTable($t) {
        foreach ($_SESSION['search_results'] as $row) {
            $t->setVariable('wine_name', $row['wine_name']);
            $t->setVariable('year', $row['year']);
            $t->setVariable('winery_name', $row['winery_name']);
            $t->setVariable('region_name', $row['region_name']);
            $t->setVariable('cost', $row['cost']);
            $t->setVariable('on_hand', $row['on_hand']);
            $t->setVariable('qty_sold', $row['qty_sold']);
            $t->setVariable('revenue', $row['revenue']);
            $t->addBlock('resultsRow');
        }
    }

    function generateResultsPage() {
        $t = new MiniTemplator();
        $t->readTemplateFromFile('templates/results.html');

        generateResultsTable($t);

        $t->generateOutput();
    }

    generateResultsPage();
    #var_dump($_SESSION);