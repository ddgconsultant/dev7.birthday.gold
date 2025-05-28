<?PHP

// RETRIEVE
    # breakpoint('hit retrieve');
 $modaltitle='Edit Details';
 $modelaction='editexisting';
 $sql = 'SELECT d.id,
 d.user_id,
 IFNULL(d.type, "") AS `type`,
 IFNULL(d.name, "") AS `name`,
 IFNULL(d.description, "") AS `description`,
 IFNULL(d.category, "") AS category,
 IFNULL(d.grouping, "") AS `grouping`,
 IFNULL(d.data_type, "") AS `data_type`,
 IFNULL(d.kipath, "'.$am_default_kidirpath.'") AS `kipath`,
 IFNULL(d.encrypted_name, "") AS encrypted_name_raw,
 IFNULL(d.encrypted_value, "") AS `encrypted_value_raw`, 
 IFNULL(d.password_strength, "") as password_strength,
 IFNULL(d.host_link_type, "") AS host_link_type,
 IFNULL(d.host, "") AS `host`,
 IFNULL(d.notes, "") AS notes,
 IFNULL(d.file_path, "") AS file_path,
 IFNULL(t1.icon, "bi bi-box") AS type_icon, 
 IFNULL(t2.icon, "bi bi-key") AS datatype_icon, 
 d.create_dt, d.modify_dt 
 FROM am_datastore d 
 LEFT JOIN am_types t1 ON (d.type = t1.type and t1.category="category")
 LEFT JOIN am_types t2 ON (d.data_type = t2.type and t2.category="data_type")
 WHERE d.id="'.$_POST['id'].'" LIMIT 1';
 
 
         
 #breakpoint($sql);
 $stmt = $database->prepare($sql);
 
 // Bind parameters and execute the statement
 $stmt->execute();
 
 $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
 $row=$result[0];
 #  breakpoint($row); 
 
 $row['encrypted_name']=$accessmanager->decrypt_wki($row['encrypted_name_raw'], $row['kipath']);
 $row['encrypted_value']=$accessmanager->decrypt_wki($row['encrypted_value_raw'], $row['kipath']);
 $row['notes']=$accessmanager->decrypt_wki($row['notes'], $row['kipath']);
 
 
 
 
 #$strengthColors = ['danger', 'warning', 'success'];
 
 $row['link']=$row['host_link_type'].'://'.$row['host'];
 /*
 $strengthValue = $row['password_strength'];
 if ($strengthValue=='' || $strengthValue=='20')  {
 $strengthresult=$accessmanager->checkPassword($row['encrypted_value']); 
 $strengthValue=$strengthresult['scale']; 
 $row['name'].='--'.$strengthValue; 
 $strengthColor=$strengthresult['color']; 
 }
 */
 
 
 $strengthresult= $accessmanager->getStrength($row['password_strength']);
 
 
 /*
 if ($strengthValue <= 33) {
 $strengthColor = 'danger'; // Low strength - Red
 } elseif ($strengthValue <= 66) {
 $strengthColor = 'warning'; // Medium strength - Yellow
 } else {
 $strengthColor = 'success'; // High strength - Green
 }
 */
 $row['listofusers_groups']='no aditional users';
 
 $datastore_datatype=$row['data_type'];
 
 
 $outputcontent.= '<!-- DATA PANEL AJAX HTML -->
 <div class="card">
 <div class="card-header d-flex justify-content-between align-items-center">
 <h4>' . $row['name'] . ' 
 <small class="fs-8 text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $row['type'] . '">Details</small>
 </h4>
 <div>
 ';
 $outputcontent.= '
 <!-- LINK Button -->
 <a type="button" class="btn btn-sm btn-secondary" href="https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id='.$qik->encodeId($row['id']).'&apikey=PUTAPIKEYHERE" 
 data-bs-toggle="tooltip" data-bs-placement="top" title="Get Access Link">
     <i class="bi bi-link-45deg"></i>
 </a>
 
 ';


 $outputcontent.= '
 <!-- Edit Button -->
 <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
 ';
 /*
  $outputcontent.= '
 <!-- Dropdown Menu -->
 <div class="btn-group">
 <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
 Actions
 </button>
 <ul class="dropdown-menu dropdown-menu-end">
 <li><a class="dropdown-item" href="#">Options</a></li>
 <li><a class="dropdown-item" href="#">Export</a></li>
 <li><a class="dropdown-item" href="#">Admin Actions</a></li>
 </ul>
 </div>
 ';
*/

  $outputcontent.= '
 </div>
 </div>
 
 
 <div class="card-body" id="rightColumnContent">
 <!-- Content Section -->
 <h5 class="card-title">'.$row['name'].'</h5>
 ';
 
 if (!empty($row['host'])) {
 $outputcontent.= '
 <div class="d-flex justify-content-between align-items-center mb-3">
 <p class="text-muted mb-0">Login URL: <a href="' . htmlspecialchars($row['host']) . '" target="_host" rel="noopener noreferrer">' . htmlspecialchars($row['host']) . '</a></p>
 <a href="' . htmlspecialchars($row['host']) . '" target="_host" rel="noopener noreferrer" class="text-muted">
 <i class="bi bi-box-arrow-up-right text-primary"></i>
 </a>
 </div>
 ';
 }
 
 
 ///////////////////////////////////////////////////////////////////////////
 //// FOR DISPLAYING THE DATASTORE DATA
 $datastore_datatype=$row['type'];
 
 switch ($datastore_datatype) {
 case 'sshkey':
 $outputcontent .= '
 <!-- SSH Key Display Fields -->
 <div class="mb-3">
 <label for="publickey" class="form-label">SSH Public Key</label>
 <textarea class="form-control" id="publickey" rows="9" readonly>'.htmlspecialchars($row['encrypted_name']).'</textarea>
 </div>
 
 <div class="mb-3">
 <label for="privatekey" class="form-label">SSH Private Key</label>
 <textarea class="form-control" id="privatekey" rows="9" readonly>'.htmlspecialchars($row['encrypted_value']).'</textarea>
 </div>
 ';
 break;
 
 case 'file':
 $outputcontent .= '
 <!-- File Display Fields -->
 <div class="mb-3">
 <label for="filename" class="form-label">File Name</label>
 <input type="text" class="form-control" id="filename" value="'.htmlspecialchars($row['filename']).'" readonly>
 </div>
 
 <!-- Add more fields as needed for file display -->
 ';
 break;
 
 case 'special':
 // Default fields for username and password
 $outputcontent .= '
 <div class="mb-3">
 <label for="password" class="form-label">Value</label>
 <div class="input-group">
 <input type="password" class="form-control" id="password" value="' . htmlspecialchars($row['encrypted_value']) . '" readonly>
 <button class="btn btn-success" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy to Clipboard" onclick="copyToClipboardAndLog(' . htmlspecialchars(json_encode($row)) . ');">
 <i class="bi bi-clipboard"></i>
 </button>
 <button class="btn btn-warning" type="button" id="togglePassword" data-bs-toggle="tooltip" data-bs-placement="top" title="Reveal" onclick="togglePasswordVisibility('.$row['id'] .');">
 <i class="bi bi-eye-slash" id="toggleIcon"></i>
 </button>
 </div>
 </div>
 
 <div class="mb-3">
 <label for="passwordStrength" class="form-label">Password Strength</label>
 <div class="progress">
 <div class="progress-bar bg-'.$strengthresult['color'].'" role="progressbar" style="width: '.$strengthresult['scale'].'%;" aria-valuenow="'.$strengthresult['scale'].'" aria-valuemin="0" aria-valuemax="100"></div>
 </div>
 </div>
 ';
 break;
 
 default:
 // Default fields for username and password
 $outputcontent .= '
 <div class="mb-3">
 <label for="username" class="form-label fw-bold">Username</label>
 <input type="text" class="form-control" id="username" value="'.htmlspecialchars($row['encrypted_name']).'" readonly>
 </div>
 
 <div class="mb-3">
 <label for="password" class="form-label">Password</label>
 <div class="input-group">
 <input type="password" class="form-control" id="password" value="' . htmlspecialchars($row['encrypted_value']) . '" readonly>
 <button class="btn btn-success" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy to Clipboard" onclick="copyToClipboardAndLog(' . htmlspecialchars(json_encode($row)) . ');">
 <i class="bi bi-clipboard"></i>
 </button>
 <button class="btn btn-warning" type="button" id="togglePassword" data-bs-toggle="tooltip" data-bs-placement="top" title="Reveal" onclick="togglePasswordVisibility('.$row['id'] .');">
 <i class="bi bi-eye-slash" id="toggleIcon"></i>
 </button>
 </div>
 </div>
 
 <div class="mb-3">
 <label for="passwordStrength" class="form-label">Password Strength</label>
 <div class="progress">
 <div class="progress-bar bg-'.$strengthresult['color'].'" role="progressbar" style="width: '.$strengthresult['scale'].'%;" aria-valuenow="'.$strengthresult['scale'].'" aria-valuemin="0" aria-valuemax="100"></div>
 </div>
 </div>
 ';
 break;
 }
 
 
 
 ///////////////////////////////////////////////////////////////////////////
 
 
 $lineCount = substr_count($row['notes'], "\n") + 1;
 $lineHeightpx = 20;
 $maxdisplaylines=30;
 $textareaHeight = min($lineCount, $maxdisplaylines) * $lineHeightpx;
 
 
 
 $outputcontent .= '
 <div class="accordion mb-3" id="accordionUsersGroups">
 <div class="accordion-item border-0">
 <h2 class="accordion-header" id="headingUsersGroups">
 <button class="accordion-button collapsed border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsersGroups" aria-expanded="false" aria-controls="collapseUsersGroups">
 Users/Groups
 </button>
 </h2>
 <div id="collapseUsersGroups" class="accordion-collapse collapse" aria-labelledby="headingUsersGroups" data-bs-parent="#accordionUsersGroups">
 <div class="accordion-body border-0">
 <p class="text-muted">' . htmlspecialchars($row['listofusers_groups']) . '</p>
 </div>
 </div>
 </div>
 </div>
 ';
 
 
 
 $outputcontent .= '
 <style>
 textarea::-webkit-scrollbar-track { background: #f1f1f1; }
 textarea::-webkit-scrollbar-thumb { background: #888; }
 textarea::-webkit-scrollbar-thumb:hover { background: #555; }
 textarea::-webkit-scrollbar { width: 8px; }
 </style>
 <div class="mb-3">
 <label class="form-label">Notes</label>
 <textarea class="form-control text-muted scrollbar-overlay" id="autoresizingTextarea" style="height:'. $textareaHeight.'px;">'.htmlspecialchars($row['notes']).'</textarea>
 </div>
 
 
 <div class="mb-3">
 <label class="form-label">Files or Photos</label>
 <div class="d-flex align-items-center">
 <i class="' . htmlspecialchars($row['type_icon']) . ' fs-1 me-2"></i> <!-- Adjusted for Bootstrap large icon with fs-1 class for size -->
 <p class="text-muted mb-0">' . htmlspecialchars($row['file_path']) . '</p>
 </div>
 
 </div>
 ';
 
 $outputcontent.= '
 <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-lg">
 <div class="modal-content">
 <div class="modal-header">
 <h5 class="modal-title" id="editModalLabel">'.$modaltitle.'</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 ';
 
  