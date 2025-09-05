<?php
/**
 * Company Class for Birthday.Gold
 * Handles all company-related operations including access control, management, and data
 * 
 * @author Birthday.Gold Development Team
 * @version 1.0.0
 * @date 2025-01-02
 */

class Company {
    private $database;
    private $account;
    private $system;
    private $current_company_id = null;
    private $company_data = null;
    private $access_cache = [];
    
    /**
     * Constructor
     * 
     * @param object $database PDO database connection
     * @param object $account Account class instance (optional)
     * @param object $system System class instance (optional)
     */
    public function __construct($database, $account = null, $system = null) {
        $this->database = $database;
        $this->account = $account;
        $this->system = $system;
    }
    
    // ============================================================================
    // COMPANY LOADING AND BASIC OPERATIONS
    // ============================================================================
    
    /**
     * Load a company by ID
     * 
     * @param int $company_id
     * @return array|false Company data or false if not found
     */
    public function load($company_id) {
        $sql = "SELECT c.*, 
                COUNT(DISTINCT mca.user_id) as total_users,
                COUNT(DISTINCT CASE WHEN mca.access_type = 'owner' THEN mca.user_id END) as owner_count
                FROM bg_companies c
                LEFT JOIN mk_company_access mca ON c.company_id = mca.company_id 
                    AND mca.status = 'active'
                    AND (mca.end_date IS NULL OR mca.end_date >= CURDATE())
                WHERE c.company_id = :company_id
                GROUP BY c.company_id";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['company_id' => $company_id]);
        
        $this->company_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($this->company_data) {
            $this->current_company_id = $company_id;
            return $this->company_data;
        }
        
        return false;
    }
    
    /**
     * Get current loaded company data
     * 
     * @return array|null
     */
    public function get() {
        return $this->company_data;
    }
    
    /**
     * Get specific company field
     * 
     * @param string $field
     * @return mixed|null
     */
    public function getField($field) {
        return $this->company_data[$field] ?? null;
    }
    
    /**
     * Create a new company
     * 
     * @param array $data Company data
     * @param int $owner_user_id User ID who will be the owner
     * @return int|false New company ID or false on failure
     */
    public function create($data, $owner_user_id) {
        $this->database->beginTransaction();
        
        try {
            // Insert company
            $sql = "INSERT INTO bg_companies 
                    (company_name, company_description, company_type, address, city, state, 
                     zip_code, country, phone, email, website, status, create_dt, modify_dt)
                    VALUES 
                    (:company_name, :company_description, :company_type, :address, :city, :state,
                     :zip_code, :country, :phone, :email, :website, 'active', NOW(), NOW())";
            
            $stmt = $this->database->prepare($sql);
            $stmt->execute($data);
            
            $company_id = $this->database->lastInsertId();
            
            // Add owner access
            $this->grantAccess($company_id, $owner_user_id, 'owner', $owner_user_id);
            
            $this->database->commit();
            return $company_id;
            
        } catch (Exception $e) {
            $this->database->rollBack();
            if ($this->system) {
                $this->system->logError('Company creation failed: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Update company details
     * 
     * @param int $company_id
     * @param array $data Fields to update
     * @param int $updated_by User ID making the update
     * @return bool
     */
    public function update($company_id, $data, $updated_by) {
        // Check if user has permission to edit
        if (!$this->hasAccess($updated_by, $company_id, 'editor')) {
            return false;
        }
        
        // Build update query
        $fields = [];
        $params = ['company_id' => $company_id];
        
        foreach ($data as $field => $value) {
            // Only allow specific fields to be updated
            $allowed_fields = ['company_name', 'company_description', 'company_type', 
                              'address', 'city', 'state', 'zip_code', 'country', 
                              'phone', 'email', 'website', 'logo_url', 'status'];
            
            if (in_array($field, $allowed_fields)) {
                $fields[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "modify_dt = NOW()";
        
        $sql = "UPDATE bg_companies SET " . implode(', ', $fields) . " WHERE company_id = :company_id";
        
        $stmt = $this->database->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete/Archive a company
     * 
     * @param int $company_id
     * @param int $deleted_by User ID requesting deletion
     * @param bool $hard_delete If true, permanently delete (default: soft delete)
     * @return bool
     */
    public function delete($company_id, $deleted_by, $hard_delete = false) {
        // Check if user has permission to delete
        if (!$this->hasAccess($deleted_by, $company_id, 'owner')) {
            return false;
        }
        
        if ($hard_delete) {
            // Permanent deletion (use with caution)
            $sql = "DELETE FROM bg_companies WHERE company_id = :company_id";
        } else {
            // Soft delete
            $sql = "UPDATE bg_companies 
                    SET status = 'deleted', 
                        modify_dt = NOW() 
                    WHERE company_id = :company_id";
        }
        
        $stmt = $this->database->prepare($sql);
        return $stmt->execute(['company_id' => $company_id]);
    }
    
    // ============================================================================
    // ACCESS CONTROL METHODS
    // ============================================================================
    
    /**
     * Check if user has specific access to a company
     * 
     * @param int $user_id
     * @param int $company_id
     * @param string $required_access Level required (owner, admin, editor, viewer, consultant)
     * @return bool
     */
    public function hasAccess($user_id, $company_id, $required_access = 'viewer') {
        // Check cache first
        $cache_key = "{$user_id}_{$company_id}_{$required_access}";
        if (isset($this->access_cache[$cache_key])) {
            return $this->access_cache[$cache_key];
        }
        
        $sql = "SELECT access_type, permissions, status
                FROM mk_company_access
                WHERE company_id = :company_id 
                AND user_id = :user_id 
                AND status = 'active'
                AND (end_date IS NULL OR end_date >= CURDATE())
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);
        
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$access) {
            $this->access_cache[$cache_key] = false;
            return false;
        }
        
        // Check hierarchical access
        $has_access = false;
        switch ($required_access) {
            case 'owner':
                $has_access = ($access['access_type'] == 'owner');
                break;
            case 'admin':
                $has_access = in_array($access['access_type'], ['owner', 'admin']);
                break;
            case 'editor':
                $has_access = in_array($access['access_type'], ['owner', 'admin', 'editor']);
                break;
            case 'viewer':
                $has_access = in_array($access['access_type'], ['owner', 'admin', 'editor', 'viewer', 'consultant']);
                break;
            case 'consultant':
                $has_access = ($access['access_type'] == 'consultant');
                break;
            default:
                // Check specific permission in JSON
                if ($access['permissions']) {
                    $permissions = json_decode($access['permissions'], true);
                    $has_access = isset($permissions[$required_access]) && $permissions[$required_access];
                }
        }
        
        $this->access_cache[$cache_key] = $has_access;
        return $has_access;
    }
    
    /**
     * Check specific permission from JSON permissions
     * 
     * @param int $user_id
     * @param int $company_id
     * @param string $permission Specific permission to check
     * @return bool
     */
    public function hasPermission($user_id, $company_id, $permission) {
        $access = $this->getUserAccess($user_id, $company_id);
        
        if (!$access) {
            return false;
        }
        
        // Owners have all permissions
        if ($access['access_type'] === 'owner') {
            return true;
        }
        
        // Check specific permission in JSON
        if (isset($access['permissions'][$permission])) {
            return $access['permissions'][$permission] === true;
        }
        
        return false;
    }
    
    /**
     * Get user's access details for a company
     * 
     * @param int $user_id
     * @param int $company_id
     * @return array|null
     */
    public function getUserAccess($user_id, $company_id) {
        $sql = "SELECT 
                    mca.*,
                    u.first_name as granted_by_first,
                    u.last_name as granted_by_last
                FROM mk_company_access mca
                LEFT JOIN bg_users u ON u.user_id = mca.grant_by
                WHERE mca.user_id = :user_id 
                AND mca.company_id = :company_id
                AND mca.status = 'active'
                AND (mca.end_date IS NULL OR mca.end_date >= CURDATE())
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);
        
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($access && $access['permissions']) {
            $access['permissions'] = json_decode($access['permissions'], true);
            $access['granted_by_name'] = trim($access['granted_by_first'] . ' ' . $access['granted_by_last']);
        }
        
        return $access;
    }
    
    /**
     * Grant access to a user for a company
     * 
     * @param int $company_id
     * @param int $user_id
     * @param string $access_type (owner, admin, editor, viewer, consultant)
     * @param int $granted_by User ID who is granting access
     * @param array $options Optional parameters (permissions, end_date, start_date)
     * @return bool
     */
    public function grantAccess($company_id, $user_id, $access_type, $granted_by, $options = []) {
        // Set default permissions based on access type
        $default_permissions = $this->getDefaultPermissions($access_type);
        
        // Merge with custom permissions if provided
        if (isset($options['permissions'])) {
            $permissions = array_merge($default_permissions, $options['permissions']);
        } else {
            $permissions = $default_permissions;
        }
        
        $sql = "INSERT INTO mk_company_access 
                (user_id, company_id, access_type, permissions, grant_by, status, start_date, end_date)
                VALUES 
                (:user_id, :company_id, :access_type, :permissions, :grant_by, 'active', :start_date, :end_date)
                ON DUPLICATE KEY UPDATE
                    access_type = VALUES(access_type),
                    permissions = VALUES(permissions),
                    grant_by = VALUES(grant_by),
                    status = 'active',
                    end_date = VALUES(end_date),
                    modify_dt = NOW()";
        
        $stmt = $this->database->prepare($sql);
        return $stmt->execute([
            'user_id' => $user_id,
            'company_id' => $company_id,
            'access_type' => $access_type,
            'permissions' => json_encode($permissions),
            'grant_by' => $granted_by,
            'start_date' => $options['start_date'] ?? date('Y-m-d'),
            'end_date' => $options['end_date'] ?? null
        ]);
    }
    
    /**
     * Revoke access from a user for a company
     * 
     * @param int $company_id
     * @param int $user_id
     * @param int $revoked_by User ID who is revoking access
     * @return array Result with success status and message
     */
    public function revokeAccess($company_id, $user_id, $revoked_by) {
        // Check if user has permission to revoke
        if (!$this->hasAccess($revoked_by, $company_id, 'admin')) {
            return [
                'success' => false,
                'message' => 'Insufficient permissions to revoke access'
            ];
        }
        
        // Check if this is the last owner
        $sql = "SELECT COUNT(*) as owner_count
                FROM mk_company_access
                WHERE company_id = :company_id
                AND access_type = 'owner'
                AND status = 'active'
                AND (end_date IS NULL OR end_date >= CURDATE())";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['company_id' => $company_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user being revoked is an owner
        $user_access = $this->getUserAccess($user_id, $company_id);
        
        if ($result['owner_count'] == 1 && $user_access && $user_access['access_type'] == 'owner') {
            return [
                'success' => false,
                'message' => 'Cannot revoke access from the last owner'
            ];
        }
        
        // Revoke access
        $sql = "UPDATE mk_company_access
                SET status = 'revoked',
                    modify_dt = NOW()
                WHERE company_id = :company_id
                AND user_id = :user_id
                AND status = 'active'";
        
        $stmt = $this->database->prepare($sql);
        $success = $stmt->execute([
            'company_id' => $company_id,
            'user_id' => $user_id
        ]);
        
        // Clear access cache
        $this->clearAccessCache($user_id, $company_id);
        
        return [
            'success' => $success,
            'message' => $success ? 'Access revoked successfully' : 'Failed to revoke access'
        ];
    }
    
    // ============================================================================
    // COMPANY LISTING AND SEARCH METHODS
    // ============================================================================
    
    /**
     * Get all companies a user has access to
     * 
     * @param int $user_id
     * @param array $filters Optional filters (status, access_type, etc.)
     * @return array
     */
    public function getUserCompanies($user_id, $filters = []) {
        $where_conditions = ["mca.user_id = :user_id"];
        $params = ['user_id' => $user_id];
        
        // Apply filters
        if (isset($filters['status'])) {
            $where_conditions[] = "mca.status = :status";
            $params['status'] = $filters['status'];
        } else {
            $where_conditions[] = "mca.status = 'active'";
        }
        
        if (isset($filters['access_type'])) {
            $where_conditions[] = "mca.access_type = :access_type";
            $params['access_type'] = $filters['access_type'];
        }
        
        $where_conditions[] = "(mca.end_date IS NULL OR mca.end_date >= CURDATE())";
        
        $sql = "SELECT 
                    c.*,
                    mca.access_type,
                    mca.permissions,
                    mca.start_date,
                    mca.end_date,
                    CASE 
                        WHEN mca.end_date IS NOT NULL THEN DATEDIFF(mca.end_date, CURDATE())
                        ELSE NULL
                    END as days_remaining,
                    (SELECT COUNT(*) FROM bg_user_companies WHERE company_id = c.company_id) as total_enrollments
                FROM mk_company_access mca
                INNER JOIN bg_companies c ON c.company_id = mca.company_id
                WHERE " . implode(' AND ', $where_conditions) . "
                ORDER BY 
                    FIELD(mca.access_type, 'owner', 'admin', 'editor', 'viewer', 'consultant'),
                    c.company_name";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON permissions
        foreach ($companies as &$company) {
            if ($company['permissions']) {
                $company['permissions'] = json_decode($company['permissions'], true);
            }
            $company['is_owner'] = ($company['access_type'] == 'owner');
            $company['is_admin'] = in_array($company['access_type'], ['owner', 'admin']);
            $company['can_edit'] = in_array($company['access_type'], ['owner', 'admin', 'editor']);
        }
        
        return $companies;
    }
    
    /**
     * Get all users with access to a company
     * 
     * @param int $company_id
     * @param bool $include_inactive Include inactive/revoked users
     * @return array
     */
    public function getCompanyUsers($company_id, $include_inactive = false) {
        $status_condition = $include_inactive ? "" : "AND mca.status = 'active'";
        
        $sql = "SELECT 
                    mca.access_id,
                    mca.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.phone,
                    u.avatar,
                    mca.access_type,
                    mca.permissions,
                    mca.status,
                    mca.start_date,
                    mca.end_date,
                    mca.grant_by,
                    gu.first_name as granted_by_first,
                    gu.last_name as granted_by_last,
                    mca.create_dt,
                    mca.modify_dt,
                    CASE 
                        WHEN mca.end_date IS NOT NULL AND mca.end_date < CURDATE() THEN 'expired'
                        WHEN mca.end_date IS NOT NULL THEN CONCAT('expires in ', DATEDIFF(mca.end_date, CURDATE()), ' days')
                        ELSE 'permanent'
                    END as access_status
                FROM mk_company_access mca
                INNER JOIN bg_users u ON u.user_id = mca.user_id
                LEFT JOIN bg_users gu ON gu.user_id = mca.grant_by
                WHERE mca.company_id = :company_id
                {$status_condition}
                ORDER BY 
                    FIELD(mca.access_type, 'owner', 'admin', 'editor', 'viewer', 'consultant'),
                    u.last_name, u.first_name";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['company_id' => $company_id]);
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process users
        foreach ($users as &$user) {
            if ($user['permissions']) {
                $user['permissions'] = json_decode($user['permissions'], true);
            }
            
            $user['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            $user['granted_by_name'] = trim($user['granted_by_first'] . ' ' . $user['granted_by_last']);
            $user['is_temporary'] = !is_null($user['end_date']);
            
            if ($user['is_temporary']) {
                $end = new DateTime($user['end_date']);
                $now = new DateTime();
                $user['days_remaining'] = max(0, $end->diff($now)->days);
                $user['is_expired'] = $end < $now;
            }
        }
        
        return $users;
    }
    
    /**
     * Search companies
     * 
     * @param string $search_term
     * @param array $filters Optional filters
     * @return array
     */
    public function searchCompanies($search_term, $filters = []) {
        $where_conditions = [];
        $params = [];
        
        // Search conditions
        $search_conditions = [];
        if (!empty($search_term)) {
            $search_conditions[] = "c.company_name LIKE :search";
            $search_conditions[] = "c.company_description LIKE :search";
            $search_conditions[] = "c.email LIKE :search";
            $search_conditions[] = "c.phone LIKE :search";
            $params['search'] = '%' . $search_term . '%';
            $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
        }
        
        // Apply filters
        if (isset($filters['status'])) {
            $where_conditions[] = "c.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (isset($filters['state'])) {
            $where_conditions[] = "c.state = :state";
            $params['state'] = $filters['state'];
        }
        
        if (isset($filters['company_type'])) {
            $where_conditions[] = "c.company_type = :company_type";
            $params['company_type'] = $filters['company_type'];
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT 
                    c.*,
                    COUNT(DISTINCT mca.user_id) as total_users,
                    COUNT(DISTINCT uc.user_id) as total_enrollments
                FROM bg_companies c
                LEFT JOIN mk_company_access mca ON c.company_id = mca.company_id 
                    AND mca.status = 'active'
                LEFT JOIN bg_user_companies uc ON c.company_id = uc.company_id
                {$where_sql}
                GROUP BY c.company_id
                ORDER BY c.company_name
                LIMIT 100";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ============================================================================
    // COMPANY ATTRIBUTES AND METADATA
    // ============================================================================
    
    /**
     * Get company attributes
     * 
     * @param int $company_id
     * @param string $type Optional type filter
     * @return array
     */
    public function getAttributes($company_id, $type = null) {
        $sql = "SELECT * FROM bg_company_attributes 
                WHERE company_id = :company_id";
        
        $params = ['company_id' => $company_id];
        
        if ($type) {
            $sql .= " AND type = :type";
            $params['type'] = $type;
        }
        
        $sql .= " AND status = 'active' ORDER BY type, name";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Set company attribute
     * 
     * @param int $company_id
     * @param string $type
     * @param string $name
     * @param string $value
     * @param array $options Optional parameters
     * @return bool
     */
    public function setAttribute($company_id, $type, $name, $value, $options = []) {
        $sql = "INSERT INTO bg_company_attributes 
                (company_id, type, name, value, description, status, create_dt, modify_dt, grouping, category)
                VALUES 
                (:company_id, :type, :name, :value, :description, :status, NOW(), NOW(), :grouping, :category)
                ON DUPLICATE KEY UPDATE
                    value = VALUES(value),
                    description = VALUES(description),
                    modify_dt = NOW()";
        
        $stmt = $this->database->prepare($sql);
        return $stmt->execute([
            'company_id' => $company_id,
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'description' => $options['description'] ?? null,
            'status' => $options['status'] ?? 'active',
            'grouping' => $options['grouping'] ?? null,
            'category' => $options['category'] ?? null
        ]);
    }
    
    // ============================================================================
    // COMPANY STATISTICS AND ANALYTICS
    // ============================================================================
    
    /**
     * Get company statistics
     * 
     * @param int $company_id
     * @return array
     */
    public function getStatistics($company_id) {
        $stats = [
            'total_users' => 0,
            'total_enrollments' => 0,
            'active_campaigns' => 0,
            'total_revenue' => 0,
            'owners' => 0,
            'admins' => 0,
            'editors' => 0,
            'viewers' => 0,
            'consultants' => 0
        ];
        
        // Get user counts by access type
        $sql = "SELECT 
                    access_type,
                    COUNT(*) as count
                FROM mk_company_access
                WHERE company_id = :company_id
                AND status = 'active'
                AND (end_date IS NULL OR end_date >= CURDATE())
                GROUP BY access_type";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['company_id' => $company_id]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['access_type'] . 's'] = $row['count'];
            $stats['total_users'] += $row['count'];
        }
        
        // Get enrollment count
        $sql = "SELECT COUNT(DISTINCT user_id) as total_enrollments
                FROM bg_user_companies
                WHERE company_id = :company_id";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['company_id' => $company_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_enrollments'] = $result['total_enrollments'];
        
        return $stats;
    }
    
    // ============================================================================
    // HELPER METHODS
    // ============================================================================
    
    /**
     * Clear access cache for a user/company
     * 
     * @param int $user_id
     * @param int $company_id
     */
    private function clearAccessCache($user_id = null, $company_id = null) {
        if ($user_id && $company_id) {
            // Clear specific user/company cache
            foreach ($this->access_cache as $key => $value) {
                if (strpos($key, "{$user_id}_{$company_id}_") === 0) {
                    unset($this->access_cache[$key]);
                }
            }
        } else {
            // Clear all cache
            $this->access_cache = [];
        }
    }
    
    /**
     * Get default permissions for an access type
     * 
     * @param string $access_type
     * @return array
     */
    private function getDefaultPermissions($access_type) {
        $permissions = [
            'owner' => [
                'can_edit_details' => true,
                'can_manage_users' => true,
                'can_view_analytics' => true,
                'can_manage_billing' => true,
                'can_delete_company' => true,
                'can_manage_marketing' => true,
                'can_export_data' => true,
                'can_manage_integrations' => true,
                'api_access' => true
            ],
            'admin' => [
                'can_edit_details' => true,
                'can_manage_users' => true,
                'can_view_analytics' => true,
                'can_manage_billing' => true,
                'can_manage_marketing' => true,
                'can_export_data' => true,
                'api_access' => true
            ],
            'editor' => [
                'can_edit_details' => true,
                'can_view_analytics' => true,
                'can_manage_marketing' => false,
                'can_export_data' => true,
                'api_access' => false
            ],
            'viewer' => [
                'can_edit_details' => false,
                'can_view_analytics' => true,
                'can_export_data' => false,
                'api_access' => false
            ],
            'consultant' => [
                'can_view_analytics' => true,
                'can_export_data' => true,
                'can_provide_recommendations' => true,
                'api_access' => false
            ]
        ];
        
        return $permissions[$access_type] ?? ['can_view' => true];
    }
    
    /**
     * Validate company data
     * 
     * @param array $data
     * @return array Validation errors (empty if valid)
     */
    public function validateCompanyData($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['company_name'])) {
            $errors['company_name'] = 'Company name is required';
        }
        
        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        }
        
        // Website validation
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors['website'] = 'Invalid website URL';
        }
        
        // Phone validation (basic)
        if (!empty($data['phone']) && !preg_match('/^[\d\s\-\(\)\+\.]+$/', $data['phone'])) {
            $errors['phone'] = 'Invalid phone number';
        }
        
        return $errors;
    }
}
?>