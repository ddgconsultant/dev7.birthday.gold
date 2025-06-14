<?PHP
  // Array of Bootstrap color classes to cycle through
  $typeColorMap = [
    'current' => 'success',
    'last'    => 'primary',
    'history' => 'light'
  ];

  $output_devices ='';

    // Fetch the login history
  foreach ($device_result as $logrow) {
    $logrow['type'] = 'AutoLogin Session';
    // Determine the color class based on the log type
    $badgeColor = isset($typeColorMap[$logrow['type']]) ? $typeColorMap[$logrow['type']] : 'secondary'; // Default to 'secondary' if the type is not mapped

    // Attempt to decode the JSON data and ensure it's an associative array
    $description = json_decode($logrow['description'], true);
    $descriptionIsValid = json_last_error() === JSON_ERROR_NONE && is_array($description);
    $description['agent'] = $description['user_agent'] ?? $description['agent'];
    // Check for bot data
    $botData = $system->isBot($logrow['description']);
    $botname = !empty($botData['name']) ? htmlspecialchars($botData['name']) : '';

    if ($descriptionIsValid) {
        $devicelist[] = $logrow['name'];

      // Extract data safely with checks
      $browser = htmlspecialchars($description['browser'] ?? 'Unknown Browser');
      $os = htmlspecialchars($description['os'] ?? 'Unknown OS');
      $ipAddress = isset($description['client_ip']) ? substr(htmlspecialchars($description['client_ip']), 0, strrpos($description['client_ip'], '.')) . '.xxx' : 'N/A';
      $city = htmlspecialchars($description['location']['city'] ?? 'Unknown City');
      $state = htmlspecialchars($description['location']['region'] ?? 'Unknown State');
      $country = htmlspecialchars($description['location']['country'] ?? 'Unknown Country');

      $details = !empty($description['agent']) ? $agentparser->getAllDetails($description['agent']) : [
        'browser' => 'Unknown Browser',
        'browser_icon' => 'bi-globe',
        'browser_icontag' => '<i class="bi bi-globe"></i>',
        'os' => 'Unknown OS',
        'os_icon' => 'bi-laptop',
        'os_icontag' => '<i class="bi bi-laptop"></i>',
        'deviceType' => 'Unknown Device Type',
        'deviceType_icon' => 'bi-laptop',
        'deviceType_icontag' => '<i class="bi bi-laptop"></i>',
        'deviceModel' => 'Unknown Model'
      ];

      // Define icons with default fallbacks
      $browser_icon = match (strtolower($browser)) {
        'chrome' => 'bi-google-chrome',
        'firefox' => 'bi-firefox',
        'safari' => 'bi-safari',
        'edge' => 'bi-edge',
        default => 'bi-globe', // Generic icon if none match
      };

      $os_icon = match (strtolower($os)) {
        'windows' => 'bi-windows',
        'mac', 'apple' => 'bi-apple',
        'linux' => 'bi-linux',
        default => 'bi-laptop', // Generic icon if none match
      };

      // Ensure $details is an array before accessing its elements
      $modelDisplay = ($details['deviceModel'] === 'Unknown Model' || empty($details['deviceModel'])) ? '' : " ({$details['deviceModel']})";

      // Combine device type and model if model is known
      $deviceInfo = "{$details['deviceType_icontag']} {$details['deviceType']}$modelDisplay";
    
      
      $output_devices.= '
      <div class="col-12 col-lg-6">
          <div class="card h-100 shadow-sm bg-secondary-subtle">
              <div class="card-body">
                  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3">
                      <span class="badge fs-3 text-bg-' . $badgeColor . ' rounded-pill mb-2 mb-lg-0">' . $logrow['type'] . '</span>
                      <small class="text-muted text-wrap text-lg-end" style="white-space: nowrap;">' . $logrow['create_dt'] . '</small>
                  </div>
                  <div class="mb-2">
                      ' . ((empty($ipAddress) || $ipAddress == 'N/A') ? '&nbsp;' : '<strong>IP Address:</strong> ' . $ipAddress) . '
                  </div>
                  <div class="d-flex align-items-center mb-2">
                      ' . str_replace("<i class='bi ", "<i class='fs-3 me-3 bi ", $details['os_icontag']) . '
                      <span class="ms-2">' . $details['os'] . '</span>
                  </div>
                  <div class="d-flex align-items-center mb-2">
                      ' . str_replace("<i class='bi ", "<i class='fs-3 me-3 bi ", $deviceInfo) . '
                  </div>
                  <div class="d-flex align-items-center mb-2">
                      ' . str_replace("<i class='bi ", "<i class='fs-3 me-3 bi ", $details['browser_icontag']) . '
                      <span class="ms-2">' . $details['browser'] . '</span>
                  </div>
                  <div>
                      <!--      <strong>Location:</strong> ' . $city . ', ' . $state . ', ' . $country . ' -->
                  </div>
                  <div class="d-flex justify-content-end mt-2">
                      <a href="/myaccount/loginhistory?view=devices&act=delete&devicelist=' . $logrow['name'] . '&_token=' . $display->inputcsrf_token('tokenonly') . '" class="btn btn-sm btn-danger">Delete</a>
                  </div>
              </div>
          </div>
      </div>
      ';

      
    }
  }


  echo '
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Trusted Devices</h5>
    ';

    if (count($devicelist) > 1) {
      echo '
    <form method="POST" action="/myaccount/loginhistory">
      <input type="hidden" name="act" value="delete">
        <input type="hidden" name="view" value="devices">
      <input type="hidden" name="_token" value="' . $display->inputcsrf_token('tokenonly') . '">
      <input type="hidden" name="devicelist" value="' . implode(',', $devicelist) . '">
      <button type="submit" class="btn btn-sm btn-danger">Delete All Devices</button>
    </form>
    ';
    }
    echo '
  </div>
  <div class="card-body border-top p-0">
    <div class="row g-3 p-4">
';    echo $output_devices;
  echo '
</div>
</div>
</div>
';