<?PHP


echo '
<!-- ===============================================-->
<!--   Logged In User Menu/Avatar-->
<!-- ===============================================-->
        <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="avatar avatar-2xl">
              <img class="bg-secondary bg-gradient rounded-circle" style="'.$local_iconcolor.'" src="'.$avatar.'" alt="" />

            </div>
          </a>
          <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end bgnav py-0" aria-labelledby="navbarDropdownUser">
            <div class="bg-white dark__bg-1000 rounded-2 py-2">
      
            ';
            if ($current_user_data['account_plan']!='life')  { 
              echo '       <a class="dropdown-item fw-bold text-warning" href="/signup"><span class="fas fa-crown me-1"></span><span>Go Lifetime</span></a>
                      <div class="dropdown-divider"></div>
                      ';
            }
                      if ($current_user_data['feature_email']!='') { echo '<a class="dropdown-item" href="/myaccount/mail"><i class="bi bi-envelope me-1"></i> BDGold Inbox</a>'; }
                      
                      echo '
                      
                      
              <a class="dropdown-item" href="/myaccount/">Account</a>                  
              <a class="dropdown-item" href="/myaccount/account">Settings</a>
              <a class="dropdown-item" href="/help">Help</a>

              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="/logout">Logout</a>
            </div>
          </div>
        </li>
        ';