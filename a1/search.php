<?php

	require_once('vendor/autoload.php');
    require_once('database/functions.php');
    include_once('config/config.php');

    function buildYearsList() {
        $bounds = getWineYearBounds();
        
        if (!is_null($bounds)) {
        	return range($bounds['min'], $bounds['max']);
        } else {
        	return null;
        }
    }
    
    // Establish a connection to the database
    $conn = connect_to_db();

    // Print error message and die if unable to
    // connect to the database
    if (get_class($conn) === "PDOException") {
        echo $conn->getMessage() . PHP_EOL;
        die();
    }
    
	$loader = new Twig_Loader_Filesystem('templates');
	$twig = new Twig_Environment($loader);

    if (isset($_SESSION['search_errors'])) {
        $twig->addGlobal('errors', $_SESSION['search_errors']);
    }
	
	echo $twig->render('search.html', array(
		'page_title' => 'Search Winestore DB',
		'regions' => getAllRegions(),
		'grapes' => getAllGrapeVarieties(),
		'years' =>	buildYearsList(),
        'min_price' => getCheapestWine(),
        'max_price' => getMostExpensiveWine(),
	));
?>
