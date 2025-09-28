<?php
// controllers/IssueController.php

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/Issue.php';
include_once __DIR__ . '/../models/Lookup.php';

class IssueController {
    private $db;
    private $issue;
    private $lookup;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->issue = new Issue($this->db);
        $this->lookup = new Lookup($this->db);
    }

    public function createIssue($data, $agent_id) {
        // Set issue properties from POST data
        $this->issue->title = $data['title'];
        $this->issue->description = $data['description'];
        $this->issue->location = $data['location'];
        $this->issue->electoral_area_id = $data['electoral_area_id'];
        $this->issue->community_id = $data['community_id'];
        $this->issue->suburb_id = $data['suburb_id'];
        $this->issue->category_id = $data['category_id'];
        $this->issue->sector_id = $data['sector_id'];
        $this->issue->subsector_id = $data['subsector_id'];
        $this->issue->type = $data['type'];
        $this->issue->severity = $data['severity'];
        $this->issue->constituent_id = $data['constituent_id'];
        $this->issue->people_affected = $data['people_affected'];
        $this->issue->budget_estimate = $data['budget_estimate'];
        $this->issue->additional_notes = $data['additional_notes'];
        $this->issue->public_visibility = isset($data['public_visibility']) ? 1 : 0; // Checkbox value

        // Agent ID is from the session
        $this->issue->agent_id = $agent_id;

        if ($this->issue->create()) {
            // Log the action in issue history
            $this->issue->logIssueHistory($this->db->lastInsertId(), $agent_id, 'created_issue', 'New issue submitted by agent.');
            return ['status' => 'success', 'message' => 'Issue was created successfully.'];
        } else {
            return ['status' => 'error', 'message' => 'Unable to create issue.'];
        }
    }

    public function getLookupData() {
        $electoralAreas = $this->lookup->getElectoralAreas()->fetchAll(PDO::FETCH_ASSOC);
        $categories = $this->lookup->getIssueCategories()->fetchAll(PDO::FETCH_ASSOC);
        $sectors = $this->lookup->getIssueSectors()->fetchAll(PDO::FETCH_ASSOC);
        $constituents = $this->lookup->getConstituents()->fetchAll(PDO::FETCH_ASSOC);

        return [
            'electoralAreas' => $electoralAreas,
            'categories' => $categories,
            'sectors' => $sectors,
            'constituents' => $constituents
        ];
    }

    public function getCommunities($electoral_area_id) {
        return $this->lookup->getCommunitiesByElectoralArea($electoral_area_id)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSuburbs($community_id) {
        return $this->lookup->getSuburbsByCommunity($community_id)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubsectors($sector_id) {
        return $this->lookup->getIssueSubsectorsBySector($sector_id)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>