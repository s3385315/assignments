<?php
    require_once('database/functions.php');

    session_start();

    $conn = connect_to_db();

    $sql = 'SELECT w.`wine_id`, w.`wine_name`, w.`year`,
                   winery.`winery_name`, r.`region_name`,
                   i.`cost`, i.`on_hand`,
	               SUM(items.`qty`) AS qty_sold, SUM(items.`price`) AS revenue
            FROM wine AS w
            JOIN winery
            ON w.`winery_id` = winery.`winery_id`
            JOIN region AS r
            ON r.`region_id` = winery.`region_id`
            JOIN inventory AS i
            ON i.`wine_id` = w.`wine_id`
            JOIN items
            ON items.`wine_id` = w.`wine_id`
            GROUP BY items.`wine_id`;';

    $results = $conn->query($sql);

    $_SESSION['search_results'] = $results->fetchAll(PDO::FETCH_ASSOC);
    header('Location: results.php');
?>