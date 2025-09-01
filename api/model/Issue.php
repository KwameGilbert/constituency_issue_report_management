<?php
// models/Issue.php

class Issue
{
    private $conn;
    private $table_name = "issues";

    // Issue properties
    public $id;
    public $title;
    public $description;
    public $location;
    public $electoral_area_id;
    public $community_id;
    public $suburb_id;
    public $category_id;
    public $sector_id;
    public $subsector_id;
    public $type;
    public $severity;
    public $status = 'pending'; // Default status for new issues
    public $status_description;
    public $agent_id;
    public $officer_id = NULL;
    public $pa_id = NULL;
    public $mp_mce_id = NULL;
    public $constituent_id;
    public $supervisor_id = NULL;
    public $people_affected;
    public $budget_estimate;
    public $resolution_notes;
    public $additional_notes;
    public $public_visibility = FALSE; // Default to private
    public $created_at;
    public $updated_at;
    public $resolved_at = NULL;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create Issue
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    title=:title, description=:description, location=:location,
                    electoral_area_id=:electoral_area_id, community_id=:community_id, suburb_id=:suburb_id,
                    category_id=:category_id, sector_id=:sector_id, subsector_id=:subsector_id,
                    type=:type, severity=:severity, status=:status,
                    agent_id=:agent_id, constituent_id=:constituent_id,
                    people_affected=:people_affected, budget_estimate=:budget_estimate,
                    public_visibility=:public_visibility, additional_notes=:additional_notes";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->electoral_area_id = htmlspecialchars(strip_tags($this->electoral_area_id));
        $this->community_id = htmlspecialchars(strip_tags($this->community_id));
        $this->suburb_id = htmlspecialchars(strip_tags($this->suburb_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->sector_id = htmlspecialchars(strip_tags($this->sector_id));
        $this->subsector_id = htmlspecialchars(strip_tags($this->subsector_id));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->severity = htmlspecialchars(strip_tags($this->severity));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->agent_id = htmlspecialchars(strip_tags($this->agent_id));
        $this->constituent_id = htmlspecialchars(strip_tags($this->constituent_id));
        $this->people_affected = htmlspecialchars(strip_tags($this->people_affected));
        $this->budget_estimate = htmlspecialchars(strip_tags($this->budget_estimate));
        $this->public_visibility = htmlspecialchars(strip_tags($this->public_visibility));
        $this->additional_notes = htmlspecialchars(strip_tags($this->additional_notes));


        // Bind values
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":electoral_area_id", $this->electoral_area_id);
        $stmt->bindParam(":community_id", $this->community_id);
        $stmt->bindParam(":suburb_id", $this->suburb_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":sector_id", $this->sector_id);
        $stmt->bindParam(":subsector_id", $this->subsector_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":severity", $this->severity);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":agent_id", $this->agent_id);
        $stmt->bindParam(":constituent_id", $this->constituent_id);
        $stmt->bindParam(":people_affected", $this->people_affected);
        $stmt->bindParam(":budget_estimate", $this->budget_estimate);
        $stmt->bindParam(":public_visibility", $this->public_visibility);
        $stmt->bindParam(":additional_notes", $this->additional_notes);

        // Execute query and check for success
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Function to log issue history
    public function logIssueHistory($issue_id, $user_id, $action, $comment = null)
    {
        $query = "INSERT INTO issue_history_logs (issue_id, user_id, action, comment) VALUES (:issue_id, :user_id, :action, :comment)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":issue_id", $issue_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":comment", $comment);
        $stmt->execute();
    }
}
