<?PHP
    
if ($app->formposted()) {
 # breakpoint($_POST);
  if (isset($_POST['selectionlist']) && (is_array($_POST['selectionlist']) || is_array($_POST['existinglist']))) {
    foreach ($lists as $processlist => $processtype) {
      if (!empty($_POST[$processlist])) {
        $listdata[$processlist] = $_POST[$processlist];
        $$processlist = $listdata[$processlist];
        $session->set('goldmine_' . str_replace('list', 'List', $processlist), $listdata[$processlist]);
      }
    }

    #-------------------------------------------------------------------------------
    # RECORD THE SELECTION
    #-------------------------------------------------------------------------------
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

      foreach ($lists as $processlist => $processtype) {
        $finalOutput[$processlist]['output'] = '';
        $finalOutput[$processlist]['listoutput'] = '';
        $finalOutput[$processlist]['counter'] = 0;

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

            $output = '<ul>';
            $listoutput = '';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $isChecked = in_array($row['company_id'], $currentList) ? 'checked' : '';
              $apponlytag = '';

              if ($processtype === 'selected') {
                if ($row['signup_url'] === 'APP ONLY') {
                  $apponlytag = '<p class="text-danger">This is an APP ONLY enrollment. We\'ll send you a link to download their app and you can sign up for their program.</p>';
                }
                $output .= '<li class="m-2"><B>' . $row['company_name'] . ':</b> ' . $row['description'] . $apponlytag . '</li>';
              } else {
                $output .= '<li class="m-2"><B>' . $row['company_name'] . '</b></li>';
              }

              $listoutput .= '<input type="hidden" name="' . $processlist . '[]" value="' . htmlentities($row['company_id']) . '" ' . $isChecked . '>';
            }
            $output .= '</ul>';
            $finalOutput[$processlist]['output'] = $output;
            $finalOutput[$processlist]['listoutput'] = $listoutput;
          }
          $finalOutput[$processlist]['counter'] = count($currentList);
        }
      }

   #   include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
      include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


      echo '
<div class="container py-6 main-content">
<div class="container">
<div class="row">
<div class="col-12  text-center justify-content-center">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.gif" alt="&#x1F44D;" width="64" height="64">
</picture>
<h2 class="my-4">Please Confirm Your ' . $finalOutput['selectionlist']['counter'] . ' Enrollment ' . $qik->plural('Selection', $finalOutput['selectionlist']['counter']) . '</h2>
</div>
<div class="bg-secondary-subtle p-3 mb-3">
<h6>These are the ones we will enroll you in:</h6>
<p class="">' . $finalOutput['selectionlist']['output'] . '</p>
</div>
';

      if ($finalOutput['existinglist']['listoutput'] > 0) {
        echo '
<div class="border-1 border-black bg-light p-3">
<h6>These are the ones you are are saying you already have existing accounts to:</h6>
' . $finalOutput['existinglist']['output'] . ' 
</div>
';
      }
      echo '
<div class="row mt-5 text-center justify-content-center">
<div class="col-6">
<a class="btn btn-danger py-3 px-5" href="' . $submitpagename . '">No. Take me back to change them</a>
</div>
<div class="col-6">
<form action="' . $submitpagename . '" method="POST" id="confirmationform">                
' . $display->inputcsrf_token() . '
<input type="hidden" name="confirmed" value="Y">
' . $finalOutput['selectionlist']['listoutput'] . '
' . $finalOutput['existinglist']['listoutput'] . '
<button type="submit" id="submit-button" name="submit_button_confirmed" value="confirmed" class="btn btn-success py-3 px-5">Yes! I Want These</button>
</form>
</div>
</div>
</div>
</div>
</div>
';
    
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
exit;
    }
  }
}