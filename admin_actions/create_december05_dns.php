<?php
/**
 * Create December05 DNS Records
 * Creates data server DNS records for MySQL replication
 */
$addClasses[] = 'powerdns';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

$results = [];
$server_ip = '72.60.121.193';

try {
    if (!isset($powerdns)) {
        echo json_encode(['success' => false, 'error' => 'PowerDNS not loaded']);
        exit;
    }

    // Create december05.bday.gold
    $result = $powerdns->createARecord('bday.gold', 'december05.bday.gold', $server_ip);
    $results['bday_gold'] = $result;

    // Create december05.birthday.gold
    $result = $powerdns->createARecord('birthday.gold', 'december05.birthday.gold', $server_ip);
    $results['birthday_gold'] = $result;

    echo json_encode([
        'success' => true,
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
