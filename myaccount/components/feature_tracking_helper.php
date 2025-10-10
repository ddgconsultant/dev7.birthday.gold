<?php
/**
 * Feature Tracking Helper Functions
 *
 * Utility functions for tracking feature setup progress and completion
 * in the bg_user_attributes table for persistent tracking beyond sessions.
 */

class FeatureTracker {
    private $database;
    private $user_id;

    public function __construct($database, $user_id) {
        $this->database = $database;
        $this->user_id = $user_id;
    }

    /**
     * Get the current feature setup progress for a user
     * @return array|null Progress data or null if not found
     */
    public function getSetupProgress() {
        $sql = "SELECT description, modify_dt FROM bg_user_attributes
                WHERE user_id = :user_id
                AND type = 'feature_setup'
                AND name = 'feature_setup_progress'
                AND status = 'active'
                ORDER BY modify_dt DESC
                LIMIT 1";

        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $this->user_id]);
        $result = $stmt->fetch();

        if ($result && !empty($result['description'])) {
            $data = json_decode($result['description'], true);
            $data['last_modified'] = $result['modify_dt'];
            return $data;
        }
        return null;
    }

    /**
     * Get all completed features for a user
     * @return array List of completed features
     */
    public function getCompletedFeatures() {
        $sql = "SELECT name, description, create_dt
                FROM bg_user_attributes
                WHERE user_id = :user_id
                AND type = 'feature_completion'
                AND status = 'completed'
                ORDER BY create_dt DESC";

        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $this->user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $completed = [];
        foreach ($results as $row) {
            $data = json_decode($row['description'], true);
            $feature_name = str_replace('_completed', '', $row['name']);
            $completed[$feature_name] = [
                'completed_at' => $data['completed_at'] ?? $row['create_dt'],
                'option_selected' => $data['option_selected'] ?? null,
                'value' => $data['value'] ?? null
            ];
        }
        return $completed;
    }

    /**
     * Get skipped features for a user (optionally filtered by date)
     * @param string|null $date Date to filter (default: today)
     * @return array List of skipped features
     */
    public function getSkippedFeatures($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        $sql = "SELECT name, description, create_dt
                FROM bg_user_attributes
                WHERE user_id = :user_id
                AND type = 'feature_skip'
                AND status = 'active'
                AND DATE(create_dt) = :date
                ORDER BY create_dt DESC";

        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            'user_id' => $this->user_id,
            'date' => $date
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $skipped = [];
        foreach ($results as $row) {
            $feature_name = str_replace('_skipped', '', $row['name']);
            $data = json_decode($row['description'], true);
            $skipped[$feature_name] = [
                'skipped_at' => $data['skipped_at'] ?? $row['create_dt'],
                'priority' => $data['priority'] ?? 0,
                'reason' => $data['reason'] ?? 'user_choice'
            ];
        }
        return $skipped;
    }

    /**
     * Get feature setup history for a user (all events)
     * @param int $limit Maximum number of records to return
     * @return array Feature setup history
     */
    public function getFeatureHistory($limit = 100) {
        $sql = "SELECT type, name, description, status, create_dt
                FROM bg_user_attributes
                WHERE user_id = :user_id
                AND type IN ('feature_setup', 'feature_completion', 'feature_skip')
                ORDER BY create_dt DESC
                LIMIT :limit";

        $stmt = $this->database->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $history = [];
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            $data = json_decode($row['description'], true) ?: [];
            $history[] = [
                'type' => $row['type'],
                'name' => $row['name'],
                'status' => $row['status'],
                'created' => $row['create_dt'],
                'details' => $data
            ];
        }
        return $history;
    }

    /**
     * Mark a feature as completed
     * @param string $feature_name Feature name (e.g., 'feature_email')
     * @param array $details Additional details about the completion
     * @return bool Success status
     */
    public function markFeatureCompleted($feature_name, $details = []) {
        $sql = "INSERT INTO bg_user_attributes
                (user_id, type, name, description, status, create_dt, modify_dt)
                VALUES (:user_id, 'feature_completion', :name, :description, 'completed', NOW(), NOW())";

        $description = json_encode(array_merge([
            'feature' => $feature_name,
            'completed_at' => date('Y-m-d H:i:s')
        ], $details));

        try {
            $stmt = $this->database->prepare($sql);
            $stmt->execute([
                'user_id' => $this->user_id,
                'name' => $feature_name . '_completed',
                'description' => $description
            ]);
            return true;
        } catch (PDOException $e) {
            error_log('[FeatureTracker] Error marking feature completed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all feature setup progress (useful for testing or reset)
     * @return bool Success status
     */
    public function clearProgress() {
        $sql = "UPDATE bg_user_attributes
                SET status = 'archived',
                    modify_dt = NOW()
                WHERE user_id = :user_id
                AND type IN ('feature_setup', 'feature_skip')
                AND status = 'active'";

        try {
            $stmt = $this->database->prepare($sql);
            $stmt->execute(['user_id' => $this->user_id]);
            return true;
        } catch (PDOException $e) {
            error_log('[FeatureTracker] Error clearing progress: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a summary of the user's feature setup status
     * @return array Summary with counts and status
     */
    public function getSetupSummary() {
        $completed = $this->getCompletedFeatures();
        $skipped = $this->getSkippedFeatures();
        $progress = $this->getSetupProgress();

        return [
            'total_completed' => count($completed),
            'completed_features' => array_keys($completed),
            'skipped_today' => count($skipped),
            'skipped_features' => array_keys($skipped),
            'in_progress' => $progress ? $progress['current_feature'] ?? null : null,
            'remaining_count' => $progress ? $progress['total_remaining'] ?? 0 : 0,
            'last_activity' => $progress ? $progress['last_updated'] ?? null : null
        ];
    }
}