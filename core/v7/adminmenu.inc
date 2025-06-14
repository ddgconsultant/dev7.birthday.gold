<?PHP

// "fa-icon-wait" has been removed from a link class


echo '
<!-- ===============================================-->
<!--    Admin Menu-->
<!-- ===============================================-->
<li class="nav-item dropdown pe-3" id="headeradminmenu">
<a class="nav-link  nine-dots p-1" id="navbarDropdownMenu" role="button" data-hide-on-body-scroll="data-hide-on-body-scroll" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
<i class="bi bi-grid-3x3-gap-fill fs-3"  style="'.$local_iconcolor.'"></i>
</a>

  <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-caret-bg" aria-labelledby="navbarDropdownMenu">
    <div class="card shadow-none">
      <div class="scrollbar-overlay nine-dots-dropdown">
        <div class="card-body px-3">
          <div class="row text-center gx-0 gy-0">
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/myaccount/account">
                <div class="avatar avatar-2xl"> <img class="rounded-circle" src="'.$avatar.'" alt="" /></div>
                <p class="mb-0 fw-medium text-800 text-truncate fs-11">Account</p>
              </a></div>
              ';

              if ($account->isadmin() ) { echo '
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/admin/" target="_blank"><img class="rounded" src="/public/images/icon/protection.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Admin</p>
              </a></div>
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/roadmap" target="_blank"><img class="rounded" src="/public/assets/img/nav-icons/roadmap.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Roadmap</p>
              </a></div>
              ';
              }
              if ($account->isadmin() || $account->iscconsultant()) { echo '          
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/myaccount/ccdashboard" target="_blank"><img class="rounded" src="/public/images/icon/growth-green.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Sales</p>
              </a></div>
              ';
              }
              if ($account->isadmin() ) { echo '
                    <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/company/systems/" target="_blank"><img class="rounded" src="/public/images/icon/content-management-system.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Systems</p>
              </a></div>
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/company/projects/" target="_blank"><img class="rounded" src="/public/assets/img/nav-icons/trello.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Kanban</p>
              </a></div>
              ';
            }
echo '                      
            <div class="col-12">
              <hr class="my-3 mx-n3 bg-200" />
            </div>
';
            if ($account->isadmin() ) { echo '
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/company/status" target="_blank"><img class="rounded" src="/public/images/icon/002-status.png" alt="" width="40" height="40" />
            <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Status</p>
          </a></div>
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/admin/reports/companies.php" target="_blank"><img class="rounded" src="/public/images/icon/online-shop.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Businesses</p>
              </a></div>
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/admin/reports/stats" target="_blank"><img class="rounded" src="/public/images/icon/003-revenue.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Revenue</p>
              </a></div>
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/company/hr/" target="_blank"><img class="rounded" src="/public/assets/img/nav-icons/slack.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">HR</p>
              </a></div>
              ';
            }

            echo '
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/company/helpdesk/" target="_blank"><img class="rounded" src="/public/images/icon/customer-service.png" alt="" width="40" height="40" />
                <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Help Desk</p>
              </a></div>
';

if ($account->isadmin() ) { echo '
            <div class="col-4"><a class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="/app/events/event-detail.php" target="_blank">
            <img class="rounded" src="/public/images/icon/vecteezy_june-26-calendar-date-icon_.jpg" alt="" width="40" height="40" />
              
                <p class="mb-0 fw-medium text-800 text-truncate fs-11">Events</p>
              </a></div>
              ';
}

echo '
          </div>
        </div>
      </div>
    </div>
  </div>

</li>
';
