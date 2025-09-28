<?php
// models/Lookup.php

class Lookup
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getElectoralAreas()
    {
        $query = "SELECT id, name FROM electoral_areas ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getCommunitiesByElectoralArea($electoral_area_id)
    {
        $query = "SELECT id, name FROM communities WHERE electoral_area_id = ? ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $electoral_area_id);
        $stmt->execute();
        return $stmt;
    }

    public function getSuburbsByCommunity($community_id)
    {
        $query = "SELECT id, name FROM suburbs WHERE community_id = ? ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $community_id);
        $stmt->execute();
        return $stmt;
    }

    public function getIssueCategories()
    {
        $query = "SELECT id, name FROM issue_categories ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getIssueSectors()
    {
        $query = "SELECT id, name FROM issue_sectors ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getIssueSubsectorsBySector($sector_id)
    {
        $query = "SELECT id, name FROM issue_subsectors WHERE sector_id = ? ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $sector_id);
        $stmt->execute();
        return $stmt;
    }

    public function getConstituents()
    {
        $query = "SELECT id, name, email FROM constituents ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
