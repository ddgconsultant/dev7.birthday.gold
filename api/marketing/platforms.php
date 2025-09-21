<?php
$addClasses[] = 'marketing';
$codemode = 'api';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

// Get company context
$company_id = $current_user_data['company_id'] ?? 0;

// Handle consultant company switching
if (isset($_GET['company_id']) && $_GET['company_id'] != $company_id) {
    $requested_company = intval($_GET['company_id']);
    
    // Verify consultant access
    $access_check = $database->getrow(
        "SELECT access_type FROM mk_company_access WHERE user_id = :user_id AND company_id = :company_id AND status = 'active'",
        ['user_id' => $current_user_data['user_id'], 'company_id' => $requested_company]
    );
    
    if ($access_check) {
        $company_id = $requested_company;
    } else {
        echo json_encode(['error' => 'Access denied to requested company']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List platforms for company
        $platforms_sql = "SELECT * FROM mk_platforms 
                         WHERE company_id = :company_id 
                         ORDER BY display_order ASC, platform_name ASC";
        $platforms = $database->getrows($platforms_sql, ['company_id' => $company_id]);
        
        // Enhance with campaign counts
        foreach ($platforms as &$platform) {
            $campaign_count = $database->getrow(
                "SELECT COUNT(*) as count FROM mk_campaigns WHERE platform_id = :platform_id",
                ['platform_id' => $platform['platform_id']]
            );
            $platform['campaign_count'] = $campaign_count['count'] ?? 0;
            $platform['platform_config'] = json_decode($platform['platform_config'], true);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $platforms,
            'company_id' => $company_id,
            'total_count' => count($platforms)
        ]);
        break;
        
    case 'POST':
        // Create new platform
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['platform_name', 'platform_type', 'platform_url'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['error' => "Missing required field: $field"]);
                exit;
            }
        }
        
        $platform_config = [
            'display_name' => $input['platform_name'],
            'description' => $input['description'] ?? '',
            'platform_type' => $input['platform_type'],
            'tags' => [
                'url' => $input['platform_url'],
                'icon' => $input['icon_class'] ?? 'bi bi-link'
            ],
            'rank' => $input['display_order'] ?? 50
        ];
        
        $platform_id = $marketing->createPlatform($platform_config);
        
        if ($platform_id) {
            echo json_encode([
                'success' => true,
                'platform_id' => $platform_id,
                'message' => 'Platform created successfully'
            ]);
        } else {
            echo json_encode(['error' => 'Failed to create platform']);
        }
        break;
        
    case 'PUT':
        // Update platform
        $platform_id = intval($_GET['id'] ?? 0);
        if (!$platform_id) {
            echo json_encode(['error' => 'Platform ID required']);
            exit;
        }
        
        // Verify ownership
        $platform = $database->getrow(
            "SELECT * FROM mk_platforms WHERE platform_id = :id AND company_id = :company_id",
            ['id' => $platform_id, 'company_id' => $company_id]
        );
        
        if (!$platform) {
            echo json_encode(['error' => 'Platform not found or access denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $update_sql = "UPDATE mk_platforms SET 
            platform_name = :name,
            platform_type = :type,
            description = :description,
            platform_url = :url,
            icon_class = :icon,
            display_order = :order,
            status = :status,
            modify_dt = NOW()
            WHERE platform_id = :id AND company_id = :company_id";
        
        $result = $database->query($update_sql, [
            'name' => $input['platform_name'] ?? $platform['platform_name'],
            'type' => $input['platform_type'] ?? $platform['platform_type'],
            'description' => $input['description'] ?? $platform['description'],
            'url' => $input['platform_url'] ?? $platform['platform_url'],
            'icon' => $input['icon_class'] ?? $platform['icon_class'],
            'order' => $input['display_order'] ?? $platform['display_order'],
            'status' => $input['status'] ?? $platform['status'],
            'id' => $platform_id,
            'company_id' => $company_id
        ]);
        
        // Log activity
        $marketing->logActivity(
            'platform_updated',
            'Platform Updated: ' . ($input['platform_name'] ?? $platform['platform_name']),
            'Platform configuration updated',
            $platform_id,
            'platform'
        );
        
        echo json_encode(['success' => true, 'message' => 'Platform updated successfully']);
        break;
        
    case 'DELETE':
        // Delete platform
        $platform_id = intval($_GET['id'] ?? 0);
        if (!$platform_id) {
            echo json_encode(['error' => 'Platform ID required']);
            exit;
        }
        
        // Verify ownership
        $platform = $database->getrow(
            "SELECT * FROM mk_platforms WHERE platform_id = :id AND company_id = :company_id",
            ['id' => $platform_id, 'company_id' => $company_id]
        );
        
        if (!$platform) {
            echo json_encode(['error' => 'Platform not found or access denied']);
            exit;
        }
        
        // Delete related campaigns first
        $database->query("DELETE FROM mk_campaigns WHERE platform_id = :id", ['id' => $platform_id]);
        
        // Delete platform
        $database->query("DELETE FROM mk_platforms WHERE platform_id = :id", ['id' => $platform_id]);
        
        // Log activity
        $marketing->logActivity(
            'platform_deleted',
            'Platform Deleted: ' . $platform['platform_name'],
            'Platform and associated campaigns removed',
            0,
            'platform'
        );
        
        echo json_encode(['success' => true, 'message' => 'Platform deleted successfully']);
        break;
        
    default:
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>