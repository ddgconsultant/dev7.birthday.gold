<?php
/**
 * ConfigManager Class
 * Manages configuration values from bg_config table
 * 
 * @package BirthdayGold
 * @version 1.0
 */

class ConfigManager {
    private $db;
    private $cache = [];
    private $cache_ttl = 300; // 5 minutes
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $type Config type (e.g., 'company_category', 'system')
     * @param string $key Config key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get($type, $key = null, $default = null) {
        $cache_key = "{$type}:{$key}";
        
        // Check cache first
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }
        
        if ($key === null) {
            // Get all configs of this type
            $sql = "SELECT * FROM bg_config 
                    WHERE config_type = :type 
                    AND is_active = 1 
                    ORDER BY display_order, config_key";
            
            $result = $this->db->getrows($sql, ['type' => $type]);
            $this->cache[$cache_key] = $result;
            return $result;
        } else {
            // Get specific config
            $sql = "SELECT config_value, config_data 
                    FROM bg_config 
                    WHERE config_type = :type 
                    AND config_key = :key 
                    AND is_active = 1";
            
            $result = $this->db->getrow($sql, [
                'type' => $type,
                'key' => $key
            ]);
            
            if ($result) {
                $value = $result['config_value'];
                
                // If config_data exists, merge it with the value
                if ($result['config_data']) {
                    $data = json_decode($result['config_data'], true);
                    if (is_array($data)) {
                        $this->cache[$cache_key] = [
                            'value' => $value,
                            'data' => $data
                        ];
                        return $this->cache[$cache_key];
                    }
                }
                
                $this->cache[$cache_key] = $value;
                return $value;
            }
        }
        
        return $default;
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $type Config type
     * @param string $key Config key
     * @param mixed $value Config value
     * @param array $data Additional JSON data
     * @param int $user_id User making the change
     * @return bool
     */
    public function set($type, $key, $value, $data = null, $user_id = null) {
        try {
            $sql = "INSERT INTO bg_config 
                    (config_type, config_key, config_value, config_data, created_by, updated_by)
                    VALUES (:type, :key, :value, :data, :user_id, :user_id)
                    ON DUPLICATE KEY UPDATE
                    config_value = VALUES(config_value),
                    config_data = VALUES(config_data),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP";
            
            $params = [
                'type' => $type,
                'key' => $key,
                'value' => $value,
                'data' => $data ? json_encode($data) : null,
                'user_id' => $user_id
            ];
            
            $this->db->query($sql, $params);
            
            // Clear cache
            $cache_key = "{$type}:{$key}";
            unset($this->cache[$cache_key]);
            unset($this->cache["{$type}:"]);
            
            return true;
        } catch (Exception $e) {
            error_log("ConfigManager::set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all company categories
     * 
     * @return array
     */
    public function getCompanyCategories() {
        $sql = "SELECT 
                    config_id as category_id,
                    config_key as category_slug,
                    config_value as category_name,
                    JSON_UNQUOTE(JSON_EXTRACT(config_data, '$.icon')) as category_icon,
                    JSON_UNQUOTE(JSON_EXTRACT(config_data, '$.description')) as category_description,
                    display_order,
                    is_active
                FROM bg_config
                WHERE config_type = 'company_category'
                AND is_active = 1
                ORDER BY display_order";
        
        return $this->db->getrows($sql);
    }
    
    /**
     * Get a single company category
     * 
     * @param string $slug Category slug
     * @return array|null
     */
    public function getCompanyCategory($slug) {
        $config = $this->get('company_category', $slug);
        
        if ($config && is_array($config) && isset($config['data'])) {
            return [
                'category_name' => $config['value'],
                'category_slug' => $slug,
                'category_icon' => $config['data']['icon'] ?? '',
                'category_description' => $config['data']['description'] ?? ''
            ];
        }
        
        return null;
    }
    
    /**
     * Add or update a company category
     * 
     * @param string $slug Category slug
     * @param string $name Category name
     * @param string $icon Icon class
     * @param string $description Description
     * @param int $order Display order
     * @param int $user_id User making the change
     * @return bool
     */
    public function setCompanyCategory($slug, $name, $icon, $description = '', $order = 0, $user_id = null) {
        $data = [
            'slug' => $slug,
            'icon' => $icon,
            'description' => $description
        ];
        
        // First set the main config
        $result = $this->set('company_category', $slug, $name, $data, $user_id);
        
        if ($result && $order > 0) {
            // Update display order
            $sql = "UPDATE bg_config 
                    SET display_order = :order 
                    WHERE config_type = 'company_category' 
                    AND config_key = :slug";
            
            $this->db->query($sql, [
                'order' => $order,
                'slug' => $slug
            ]);
        }
        
        return $result;
    }
    
    /**
     * Delete a configuration
     * 
     * @param string $type Config type
     * @param string $key Config key
     * @return bool
     */
    public function delete($type, $key) {
        try {
            $sql = "UPDATE bg_config 
                    SET is_active = 0 
                    WHERE config_type = :type 
                    AND config_key = :key";
            
            $this->db->query($sql, [
                'type' => $type,
                'key' => $key
            ]);
            
            // Clear cache
            $cache_key = "{$type}:{$key}";
            unset($this->cache[$cache_key]);
            unset($this->cache["{$type}:"]);
            
            return true;
        } catch (Exception $e) {
            error_log("ConfigManager::delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all cache
     */
    public function clearCache() {
        $this->cache = [];
    }
}