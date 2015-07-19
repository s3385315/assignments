<?php
    require_once('db.php');

    $pdo = connect_to_db();

    // Tries to connect to the DB and return a PDO instance.
    // Returns a PDOException on failure.
    function connect_to_db() {
        try {
            return new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        }
        catch (PDOException $e) {
            return $e;
        }
    }

    // Get all regions from the database.
    // Returns both the id and name for each region.
    function getAllRegions() {
        global $pdo;

        // Prepare SQL statement to select all regions
        $stmt = $pdo->prepare("SELECT region_id AS id, region_name AS name
        					   FROM region");

        // Execute SQL statement
        $stmt->execute();

        // Return the results
        return $stmt->fetchAll();
    }

    // Get all grape varieties from the database.
    function getAllGrapeVarieties() {
        global $pdo;

        return $pdo->query('SELECT `variety_id` AS `id`, `variety` AS `name`
							FROM grape_variety ORDER BY variety_id');
    }

    // Find lowest priced wine in stock
    function getCheapestWine() {
        global $pdo;

        $sql = "SELECT MIN(i.`cost`) AS \"min\"
                FROM `inventory` as i
                WHERE i.`on_hand` > 0";

        $statement = $pdo->prepare($sql);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_NUM)[0];
    }

    // Find highest priced wine in stock
    function getMostExpensiveWine() {
        global $pdo;

        $sql = "SELECT MAX(i.`cost`) AS \"max\"
                    FROM `inventory` as i
                    WHERE i.`on_hand` > 0";

        $statement = $pdo->prepare($sql);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_NUM)[0];
    }

    // Get the min and max years from the wine table
    function getWineYearBounds() {
        global $pdo;

        // Prepare SQL statement
        $sql = 'SELECT min(year) as min, max(year) as max FROM wine';

        // Return the first rowset of the resulting PDOStatement
        // from the query function. (Should only be one.)
        // Returns null on error.
        return (($result = $pdo->query($sql)))
            ? $result->fetch(PDO::FETCH_ASSOC)
            : null;
    }

    function getRegionIdFromName($name) {
        global $pdo;

        $sql = 'SELECT region_id
            FROM region
            WHERE region_name = ?';

        // Prepare SQL statement
        $stmt = $pdo->prepare($sql);

        // Bind region name variable to SQL statement
        $stmt->bindValue(1, $name);

        // Execute the SQL statement
        $stmt->execute();

        // Fetch an integer index array of result(s)
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return region id or null if region name couldn't be found
        return ($result) ? $result['region_id'] : null;
    }

    function getAllWineryInRegionByName($name) {
        global $pdo;

        // Find region id or return null if a match isn't found
        if (is_null(($region_id = getRegionIdFromName($name)))) {
            return null;
        }

        $sql = 'SELECT winery_id, winery_name
                   FROM winery
                   WHERE region_id = :region_id';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':region_id', $region_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return (!empty($results) && $results) ? $results : null;
    }

    /*function getWinesByWineryId($winery_id) {
        global $pdo;

        $sql = 'SELECT * FROM wine WHERE winery_id = :winery_id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':winery_id', $winery_id);
        $stmt->execute();

        $results = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $wine) {

            array_push($results, new Wine(
                (int) $wine['wine_id'],
                $wine['wine_name'],
                (int) $wine['wine_type'],
                (int) $wine['year'],
                (int) $wine['winery_id'],
                $wine['description']
            ));
        }

        return (!empty($results)) ? $results : null;
    }*/