<?php
class Wine
{
    private $id;
    private $name;
    private $type;
    private $year;
    private $winery_id;
    private $desc;

    function __construct($id, $name, $type, $year, $winery_id, $desc=null)
    {
        $this->setId($id);
        $this->setName($name);
        $this->setType($type);
        $this->setYear($year);
        $this->setWineryId($winery_id);
        $this->setDescription($desc);
    }

    public function setId($id)
    {
        if (is_int($id))
        {
            $this->id = $id;
        }
        else {
            throw new Exception("Wine ID must be an integer!");
        }
    }
    public function setName($name) {
        if (is_string($name)) {
            if (strlen($name) <= 50) {
                $this->name = $name;
            }
            else {
                throw new Exception("Wine name must be less than 50 characters!");
            }
        }
        else {
            throw new Exception("Wine name must be a string.");
        }
    }
    public function setType($type) {
        if (is_int($type)) {
            $this->type = $type;
        }
        else {
            throw new Exception("Wine type must be an integer!");
        }
    }
    public function setYear($year) {
        if (is_int($year)) {
            $this->year = $year;
        }
        else {
            throw new Exception("Wine year must be an integer!");
        }
    }
    public function setWineryId($winery_id) {
        if (is_int($winery_id)) {
            $this->winery_id = $winery_id;
        }
        else {
            throw new Exception("Winery ID must be an integer!");
        }
    }
    public function setDescription($desc) {
        $this->desc = $desc;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getYear() { return $this->year; }
    public function getWineryId() { return $this->winery_id; }
    public function getDescription() { return $this->desc; }

}

?>