<?php
    require_once('database/functions.php');
    include_once('config/config.php');

// Base query from which the actual query sent to the DB
// will be built from
define("BASE_QUERY", 'SELECT w.`wine_id`, w.`wine_name`, w.`year`,
				   GROUP_CONCAT(DISTINCT gv.`variety`) as \'grapes\',
                   winery.`winery_name`, r.`region_name`,
                   i.`cost`, i.`on_hand`,
	               SUM(items.`qty`) AS qty_sold, SUM(items.`price`) AS revenue
            FROM wine AS w
            JOIN winery
            ON w.`winery_id` = winery.`winery_id`
            JOIN `wine_variety` wv
            ON w.`wine_id` = wv.`wine_id`
            JOIN grape_variety gv
            ON wv.`variety_id` = gv.`variety_id`
            JOIN region AS r
            ON r.`region_id` = winery.`region_id`
            JOIN inventory AS i
            ON i.`wine_id` = w.`wine_id`
            JOIN items
            ON items.`wine_id` = w.`wine_id`
            <<WHERE>>
            GROUP BY items.`wine_id`
            <<HAVING>>');


    function filter_enabled_fields() {
        // Store our enabled fields here
        $enabled = array();

        //
        $pattern = "/^(?<fieldname>[a-z\_]*)_(enabled)$/";


        // Iterate through $_GET keys
        foreach(array_keys($_GET) as $key) {
            // If a field matches the above pattern (i.e. a checkbox was checked
            // making it enabled) and it's corresponding field is set in $_GET
            // and the value of said field is not an empty string, add it
            // to the 'enabled fields' array.

            if (preg_match($pattern, $key, $matches)
                    && isset($_GET[$matches['fieldname']])
                    && strlen($_GET[$matches['fieldname']]) > 0)
            {
                array_push($enabled, $matches['fieldname']);
            }
            elseif (!in_array($key, $enabled)) {
                unset($_GET[$key]);
            }
        }

        return $enabled;
    }

    // Passes the enabled fields array by reference
    // so we can remove unexpected values
    function build_query(&$enabled_fields)
    {
        $clauses = array('WHERE' => array(), 'HAVING' => array());

        if (!empty($enabled_fields)) {

            // Compile list of clauses to add to the query
            foreach ($enabled_fields as $field) {

                switch($field) {
                    case 'wine': array_push($clauses['WHERE'], "w.`wine_name` LIKE ?");
                        break;
                    case 'winery': array_push($clauses['WHERE'], "winery.`winery_name` LIKE ?");
                        break;
                    case 'region':
                        // If the user selected 'All' the id will be 1
                        // so we don't need a 'WHERE' clause for this field
                        if ($_GET['region'] != '1') {
                            array_push($clauses['WHERE'], "r.`region_id` = ?");
                        }
                        break;
                    case 'grape_variety': array_push($clauses['WHERE'], "gv.`variety_id` = ?");
                        break;
                    case 'min_year': array_push($clauses['WHERE'], "w.`year` >= ?");
                        break;
                    case 'max_year': array_push($clauses['WHERE'], "w.`year` <= ?");
                        break;
                    case 'min_stock': array_push($clauses['WHERE'], "i.`on_hand` >= ?");
                        break;
                    case 'min_ordered': array_push($clauses['HAVING'], "SUM(items.`qty`) >= ?");
                        break;
                    case 'min_price': array_push($clauses['WHERE'], "i.`cost` >= ?");
                        break;
                    case 'max_price': array_push($clauses['WHERE'], "i.`cost` <= ?");
                        break;
                    // Remove unexpected field
                    default: unset($enabled_fields[array_search($field, $enabled_fields)]);
                        break;
                }
            }

            // Assemble WHERE clause(s) into valid SQL
            if (count($clauses['WHERE']) > 1) {
                $clauses['WHERE'] = "WHERE " . join(" AND ", $clauses['WHERE']);
            }
            elseif (count($clauses['WHERE']) === 1) {
                $clauses['WHERE'] = "WHERE " . $clauses['WHERE'][0];
            }
            else {
                $clauses['WHERE'] = "";
            }

            // Assemble HAVING clause(s) into valid SQL
            if (count($clauses['HAVING']) > 1) {
                $clauses['HAVING'] = "HAVING " . join(" AND ", $clauses['HAVING']) . ";";
            }
            elseif (count($clauses['HAVING']) === 1) {
                $clauses['HAVING'] = "HAVING " . $clauses['HAVING'][0] . ";";
            }
            else {
                $clauses['HAVING'] = ";";
            }
            
            return $clauses;
        }
        else {
            return false;
        }
    }

    function fetch_results($query, $enabled_fields) {
        global $pdo;

        // Prepare statement
        $statement = $pdo->prepare($query);

        // Get array of values from $_GET
        $values = array();
        foreach ($enabled_fields as $field) {
            if (isset($_GET[$field])) {

                // Add in wildcards for values that use 'LIKE'
                // in the SQL query
                if ($field === 'wine' || $field === 'winery')
                {
                    array_push($values, "%" . $_GET[$field] . "%");
                }
                else {
                    array_push($values, $_GET[$field]);
                }

            }
        }

        // Bind values to statement and execute
        $statement->execute($values);

        // Fetch results
        return $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    function do_sanity_checks($enabled_fields) {
        $errors = array();

        // Check that fields expected numeric data have valid input
        foreach ($enabled_fields as $f) {

            switch ($f) {
                case 'min_year':
                case 'max_year':
                case 'min_stock':
                case 'min_ordered':
                    // Check if value is an integer, otherwise return
                    // an error message.
                    if (!preg_match('/^\d+$/', $_GET[$f])) {
                        // Create array key and add error message

                        //echo "$f invalid<br>\n";
                        $errors[$f] = array("Input must be a whole number");
                    }
                    break;
                case 'min_price':
                case 'max_price':
                    // Check for a valid monetary valid.
                    // Return an error message if not found.
                    if(!preg_match('/^[\d]+(\.[\d]{1,})?$/', $_GET[$f])) {
                        // Create array key and add error message
                        $errors[$f] = array("Input must be a valid monetary value (e.g. 17.55)");
                    }
                    break;
            }
        }

        // Check that min year and max year are logical values
        if (isset($_GET['min_year']) && isset($_GET['max_year'])) {

            if ($_GET['min_year'] > $_GET['max_year']) {

                if (array_key_exists($errors, "min_year")) {
                    // Push error message to errors array
                    array_push($errors['min_year'], "Minimum year cannot be greater than maximum year!");
                }
                else {
                    // Create array key and add error message
                    $errors['min_year'] = array("Minimum year cannot be greater than maximum year!");
                }
            }
        }


        // Check that min price and max price are logical values
        if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
            if ($_GET['min_price'] > $_GET['max_price']) {
                if (array_key_exists($errors, "min_price")) {
                    // Push error message to errors array
                    array_push($errors['min_price'], "Minimum price cannot be greater than maximum price!");
                } else {
                    // Create array key and add error message
                    $errors['min_price'] = array("Minimum price cannot be greater than maximum price!");
                }
            }
        }

        return $errors;
    }

    function main() {
        $errors = array();

        // Try to connect to DB
        $pdo = null;

        try {
            global $pdo;
            $pdo = connect_to_db();
        }
        catch (PDOException $pe) {
            // Add error to errors array
            $errors['db'] = array("Could not connect to database. The following errors was returned: "
            . $pe->getMessage());
        }

        // Get an array of search fields "enabled"
        $enabled_fields = filter_enabled_fields();

        // Perform sanity checks adding any errors on
        $sanity_errs = do_sanity_checks($enabled_fields);
        if (!empty($sanity_errs)) {
            $errors = array_merge($errors, $sanity_errs);
        }

        // If errors exists, return to the search page and display them
        if (!empty($errors)) {
            $_SESSION['search_errors'] = $errors;
            header('Location: search.php');
            exit;
        }
        else {
            // Clear any previous errors that may be stored
            // in the session variable
            if (isset($_SESSION['search_errors'])) {
                unset($_SESSION['search_errors']);
            }
        }

        // Build the WHERE and/or HAVING clause(s)
        $clauses = build_query($enabled_fields);

        // Insert WHERE clause(s) into full query
        $full_query = str_replace('<<WHERE>>', $clauses['WHERE'], BASE_QUERY);

        // Insert HAVING clause(s) into full query
        $full_query = str_replace('<<HAVING>>', $clauses['HAVING'], $full_query);

        // Fetch results from DB
        $results = fetch_results($full_query, $enabled_fields);

        $_SESSION['search_results'] = $results;
        header('Location: results.php');
    }

    main();



