<?php
/**
 * PowerDNS API Integration Class
 * Handles DNS record management via PowerDNS API
 */
class PowerDNS
{
    private $api_url;
    private $api_key;
    private $server_id = 'localhost';

    public function __construct($config = [])
    {
        $this->api_url = $config['api_url'] ?? 'http://pdns.thedatadesigngroup.com/api/v1';
        $this->api_key = $config['api_key'] ?? '';
        $this->server_id = $config['server_id'] ?? 'localhost';
    }

    /**
     * Make API request to PowerDNS
     */
    private function apiRequest($method, $endpoint, $data = null)
    {
        $url = $this->api_url . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-Key: ' . $this->api_key,
            'Content-Type: application/json'
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => json_decode($response, true),
                'http_code' => $http_code
            ];
        }

        return [
            'success' => false,
            'error' => $response,
            'http_code' => $http_code
        ];
    }

    /**
     * Create or update DNS A record
     */
    public function createARecord($zone, $hostname, $ip_address, $ttl = 300)
    {
        // Ensure zone ends with a dot
        if (substr($zone, -1) !== '.') {
            $zone .= '.';
        }

        // Ensure hostname is fully qualified
        if (substr($hostname, -1) !== '.') {
            $hostname .= '.';
        }

        $rrsets = [
            'rrsets' => [
                [
                    'name' => $hostname,
                    'type' => 'A',
                    'ttl' => $ttl,
                    'changetype' => 'REPLACE',
                    'records' => [
                        [
                            'content' => $ip_address,
                            'disabled' => false
                        ]
                    ]
                ]
            ]
        ];

        return $this->apiRequest('PATCH', "/servers/{$this->server_id}/zones/{$zone}", $rrsets);
    }

    /**
     * Create or update DNS CNAME record
     */
    public function createCNAME($zone, $hostname, $target, $ttl = 300)
    {
        // Ensure zone ends with a dot
        if (substr($zone, -1) !== '.') {
            $zone .= '.';
        }

        // Ensure hostnames are fully qualified
        if (substr($hostname, -1) !== '.') {
            $hostname .= '.';
        }
        if (substr($target, -1) !== '.') {
            $target .= '.';
        }

        $rrsets = [
            'rrsets' => [
                [
                    'name' => $hostname,
                    'type' => 'CNAME',
                    'ttl' => $ttl,
                    'changetype' => 'REPLACE',
                    'records' => [
                        [
                            'content' => $target,
                            'disabled' => false
                        ]
                    ]
                ]
            ]
        ];

        return $this->apiRequest('PATCH', "/servers/{$this->server_id}/zones/{$zone}", $rrsets);
    }

    /**
     * Delete DNS record
     */
    public function deleteRecord($zone, $hostname, $type = 'A')
    {
        // Ensure zone ends with a dot
        if (substr($zone, -1) !== '.') {
            $zone .= '.';
        }

        // Ensure hostname is fully qualified
        if (substr($hostname, -1) !== '.') {
            $hostname .= '.';
        }

        $rrsets = [
            'rrsets' => [
                [
                    'name' => $hostname,
                    'type' => $type,
                    'changetype' => 'DELETE'
                ]
            ]
        ];

        return $this->apiRequest('PATCH', "/servers/{$this->server_id}/zones/{$zone}", $rrsets);
    }

    /**
     * List all zones
     */
    public function listZones()
    {
        return $this->apiRequest('GET', "/servers/{$this->server_id}/zones");
    }

    /**
     * Get zone details
     */
    public function getZone($zone)
    {
        // Ensure zone ends with a dot
        if (substr($zone, -1) !== '.') {
            $zone .= '.';
        }

        return $this->apiRequest('GET', "/servers/{$this->server_id}/zones/{$zone}");
    }
}
