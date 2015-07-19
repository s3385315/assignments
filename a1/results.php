<?php
require_once('vendor/autoload.php');
include_once('config/config.php');


function buildResultsList() {

	if (array_key_exists('search_results', $_SESSION) 
		&& isset($_SESSION['search_results']))
	{
	
		// New array to store changes in
		$search_results = array();
	
		// Iterate through results sent from answer.php
		foreach ($_SESSION['search_results'] as $row) {
	
			// Split comma separated list of grape varieties
			// into array. Needed to display them as an unordered list.
			$row['grapes'] = explode(',', $row['grapes']);
		
			// Push modified row to new array
			array_push($search_results, $row);
		}
	
		return $search_results;
	}
	else {
		return null;
	}
}

try {
	$loader = new Twig_Loader_Filesystem('templates');
	$twig = new Twig_Environment($loader);
	
	echo $twig->render('results.html', array(
		'page_title' => 'Results',
		'results' => buildResultsList(),
	));
} catch (Exception $e) {
	echo $e->getMessage();
}

if (isset($_SESSION['search_errors'])) {
    print_r($_SESSION['search_errors']);
}


?>