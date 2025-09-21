<?PHP
    
if ($app->formposted()) {
  if (isset($_POST['selectionlist']) && (is_array($_POST['selectionlist']) || is_array($_POST['existinglist']))) {
    foreach ($lists as $processlist => $processtype) {
      if (!empty($_POST[$processlist])) {
        $listdata[$processlist] = $_POST[$processlist];
        $$processlist = $listdata[$processlist];
        $session->set('goldmine_' . str_replace('list', 'List', $processlist), $listdata[$processlist]);
      }
    }

    if (isset($_POST['confirmed'])) {
      $finalcount = 0;
      foreach ($lists as $processlist => $processtype) {
        $listdata[$processlist] = $_POST[$processlist];
        $rowsInserted = 0;
        $stmt = $database->prepare("INSERT INTO bg_user_companies (user_id, company_id, create_dt, modify_dt, `status`) VALUES (:user_id, :value, now(), now(), '" . $processtype . "')");

        foreach ($listdata[$processlist] as $value) {
          $stmt->bindParam(':user_id', $current_user_data['user_id']);
          $stmt->bindParam(':value', $value);
          $stmt->execute();
          $rowsInserted += $stmt->rowCount();
        }
        $finalinsertedcount[$processlist] = $rowsInserted;
        $finalcount += $finalinsertedcount[$processlist];
      }

      if ($finalcount > 0) {
        $updatefields['enrollment_mode'] = 'normal';
        $account->updateSettings($current_user_data['user_id'], $updatefields);
        $current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
      }
      header('location: /myaccount/enrollment');
      exit;
    } else {
      $lists = ['existinglist' => 'existing', 'selectionlist' => 'selected'];
      $finalOutput = [];
      $rewardStats = [
        'we_enroll' => 0,
        'app_only' => 0,
        'already_own' => 0
      ];

      foreach ($lists as $processlist => $processtype) {
        $finalOutput[$processlist]['output'] = '';
        $finalOutput[$processlist]['listoutput'] = '';
        $finalOutput[$processlist]['counter'] = 0;
        $finalOutput['we_enroll']['output'] = '';
        $finalOutput['app_only']['output'] = '';

        if (!empty($_POST[$processlist])) {
          $currentList = $_POST[$processlist];
          $placeholders = array_map(function ($companyId, $index) {
            return ":company_id_$index";
          }, $currentList, array_keys($currentList));

          $sql = "SELECT * FROM bg_companies WHERE company_id IN (" . implode(',', $placeholders) . ")";
          $stmt = $database->prepare($sql);

          if ($stmt) {
            foreach ($currentList as $index => $companyId) {
              $paramName = ":company_id_$index";
              $stmt->bindValue($paramName, $companyId);
            }
            $stmt->execute();

            $output = '';
            $listoutput = '';
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $isChecked = in_array($row['company_id'], $currentList) ? 'checked' : '';
              $company = !empty($row['company_id']) ? $app->getcompany($row['company_id']) : null;

              $apponlytag = '';
              
              // Build the card HTML
              $cardHtml = '
              <div class="card mb-3 border-0 shadow-sm hover-card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex gap-3">
                      <div class="reward-icon-wrapper" style="min-width: 40px; width: 40px; height: 40px;">';
              
              if (!empty($company['company_logo'])) {
                $cardHtml .= '<div class="company-logo-wrapper rounded-circle overflow-hidden" style="width: 100%; height: 100%;">
                    <img src="' . $display->companyimage($row['company_id'] . '/' . $company['company_logo']) . '" 
                        class="company-logo w-100 h-100 object-fit-cover" 
                        alt="Company Logo">
                </div>';
              }
              
              $cardHtml .= '</div>
                      <div>
                        <div class="d-flex align-items-center">
                          <h5 class="card-title mb-1">' . htmlspecialchars($row['company_name']) . $apponlytag . '</h5>
                        </div>
                        <p class="card-text text-muted mb-2">' . htmlspecialchars($row['description']) . '</p>
                      </div>
                    </div>
                    <div class="text-end">
                      <span class="badge rounded-pill bg-light text-dark">
                          ' . htmlspecialchars($row['category']) . '
                      </span>
                    </div>
                  </div>
                </div>
              </div>';

              // Add to appropriate list based on type
              if ($processtype === 'existing') {
                $rewardStats['already_own']++;
                $finalOutput['existinglist']['output'] .= $cardHtml;
              } else if ($processtype === 'selected') {
                if ($row['signup_url'] === 'APP ONLY') {
                  $rewardStats['app_only']++;
                  $finalOutput['app_only']['output'] .= $cardHtml;
                } else {
                  $rewardStats['we_enroll']++;
                  $finalOutput['we_enroll']['output'] .= $cardHtml;
                }
              }

              $listoutput .= '<input type="hidden" name="' . $processlist . '[]" value="' . htmlspecialchars($row['company_id']) . '" ' . $isChecked . '>';
            }
            
            $finalOutput[$processlist]['listoutput'] = $listoutput;
          }
          $finalOutput[$processlist]['counter'] = count($currentList);
        }
      }

      include($dir['core_components'] . '/bg_pagestart.inc');
      include($dir['core_components'] . '/bg_header.inc');

      // Output the modern interface
      echo '
<div class="container main-content py-5">
  <div class="row">
    <!-- Header Section -->
    <div class="col-12 text-center mb-4">
      <div class="mb-3">
        <picture>
          <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.webp" type="image/webp">
          <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.gif" alt="👍" width="64" height="64">
        </picture>
      </div>
      <h1 class="display-5 fw-bold text-primary mb-3">Amazing Rewards Await!</h1>
      <p class="fs-4 text-muted">Confirm your ' . $finalOutput['selectionlist']['counter'] . ' selected rewards programs</p>
    </div>

    
<!-- Stats Cards -->
<div class="col-12 mb-4">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card h-100 border-0" style="background-color: ' . ($rewardStats['we_enroll'] > 0 ? '#e8f5e9' : '#f8f9fa') . '">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="' . ($rewardStats['we_enroll'] > 0 ? 'text-success' : 'text-muted') . '">
                            <i class="bi bi-person-check-fill fs-4"></i>
                        </span>
                        <span class="' . ($rewardStats['we_enroll'] > 0 ? 'text-success' : 'text-muted') . '">
                            <i class="bi bi-people-fill"></i> ' . $rewardStats['we_enroll'] . '</span>
                    </div>
                    <h5 class="card-title mb-1 ' . ($rewardStats['we_enroll'] > 0 ? '' : 'text-muted') . '">We\'ve Got This!</h5>
                    <p class="' . ($rewardStats['we_enroll'] > 0 ? 'text-success-emphasis' : 'text-muted') . ' small">We handle everything for you</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0" style="background-color: ' . ($rewardStats['app_only'] > 0 ? '#ede7f6' : '#f8f9fa') . '">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="' . ($rewardStats['app_only'] > 0 ? 'text-primary' : 'text-muted') . '">
                            <i class="bi bi-phone-fill fs-4"></i>
                        </span>
                        <span class="' . ($rewardStats['app_only'] > 0 ? 'text-primary' : 'text-muted') . '">
                            <i class="bi bi-people-fill"></i> ' . $rewardStats['app_only'] . '</span>
                    </div>
                    <h5 class="card-title mb-1 ' . ($rewardStats['app_only'] > 0 ? '' : 'text-muted') . '">Quick App Download</h5>
                    <p class="' . ($rewardStats['app_only'] > 0 ? 'text-primary-emphasis' : 'text-muted') . ' small">Just a few taps to save</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0" style="background-color: ' . ($rewardStats['already_own'] > 0 ? '#fff3e0' : '#f8f9fa') . '">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="' . ($rewardStats['already_own'] > 0 ? 'text-warning' : 'text-muted') . '">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </span>
                        <span class="' . ($rewardStats['already_own'] > 0 ? 'text-warning' : 'text-muted') . '">
                            <i class="bi bi-people-fill"></i> ' . $rewardStats['already_own'] . '</span>
                    </div>
                    <h5 class="card-title mb-1 ' . ($rewardStats['already_own'] > 0 ? '' : 'text-muted') . '">Already Yours</h5>
                    <p class="' . ($rewardStats['already_own'] > 0 ? 'text-warning-emphasis' : 'text-muted') . ' small">You\'re ahead of the game</p>
                </div>
            </div>
        </div>
    </div>
</div>
';

// Only show "We Enroll You" section if there are items
if ($rewardStats['we_enroll'] > 0) {
    echo '
    <!-- We Enroll You List -->
    <div class="col-12 mb-4">
        <div class="p-4 rounded-3 shadow-sm" style="background-color: #e8f5e9;">
            <h4 class="mb-4">
                <i class="bi bi-person-check-fill text-success me-2"></i>
                We Will Enroll You In These
            </h4>
            ' . $finalOutput['we_enroll']['output'] . '
        </div>
    </div>';
}

// Only show "App Only" section if there are items
if ( $rewardStats['app_only']> 0) {
    echo '
    <!-- App Only List -->
    <div class="col-12 mb-4">
        <div class="p-4 rounded-3 shadow-sm" style="background-color: #ede7f6;">
            <h4 class="mb-4 text-primary">
                <i class="bi bi-phone-fill text-primary me-2"></i>
                Download These Apps To Enroll Yourself
            </h4>
            <div class="list-wrapper bg-white p-3 rounded-3">
                ' . $finalOutput['app_only']['output'] . '
            </div>
        </div>
    </div>';
}

// Only show "Existing Accounts" section if there are items
if ( $rewardStats['already_own'] > 0) {
    echo '
    <!-- Existing Accounts -->
    <div class="col-12 mb-4">
        <div class="p-4 rounded-3 shadow-sm" style="background-color: #fff3e0;">
            <h4 class="mb-4">
                <i class="bi bi-check-circle-fill text-warning me-2"></i>
                You Already Have These Accounts
            </h4>
            ' . $finalOutput['existinglist']['output'] . '
        </div>
    </div>';
}



    echo '
    <!-- Action Buttons -->
    <div class="col-12">
      <div class="d-flex gap-3 justify-content-center">
        <a href="' . $submitpagename . '" class="btn btn-danger px-4 py-3">
          <i class="bi bi-arrow-left me-2"></i>Modify '.$qik->plural2($finalOutput[$processlist]['counter'] , 'Selection' ,'_hide_count') .'
        </a>
        <form action="' . $submitpagename . '" method="POST" id="confirmationform">
          ' . $display->inputcsrf_token() . '
          <input type="hidden" name="confirmed" value="Y">
          ' . $finalOutput['selectionlist']['listoutput'] . '
          ' . $finalOutput['existinglist']['listoutput'] . '
          <button type="submit" id="submit-button" name="submit_button_confirmed" value="confirmed" 
                  class="btn btn-success px-4 py-3">
            <i class="bi bi-check2-circle me-2"></i>Confirm All Rewards
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
.hover-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.hover-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
}

.reward-icon-wrapper {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(13,110,253,0.1);
  border-radius: 50%;
}

.btn {
  transition: all 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
  .btn {
    width: 100%;
    margin-bottom: 1rem;
  }
  
  .d-flex.gap-3.justify-content-center {
    flex-direction: column;
  }
}

.company-logo-wrapper {
    width: 40px;
    height: 40px;
    overflow: hidden;
}

.company-logo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>';
      
      $display_footertype = 'min';
      include($dir['core_components'] . '/bg_footer.inc');
      $app->outputpage();
      exit;
    }
  }
}