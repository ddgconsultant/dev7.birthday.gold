<?PHP

// "fa-icon-wait" has been removed from a link class

$notificationcount=0;
$notificationdot='';

echo '
<style>
.dropdown-menu-notification {
    min-width: 20rem !important;
}
@media (max-width: 768px) {
  .dropdown-menu-notification {
      min-width: 17rem !important; 
  }
}
.navbar .dropdown-menu.dropdown-menu-end:after {
  right: 2.1rem !important;
}
</style>
<!-- ===============================================-->
<!--    Notification Alarm-->
<!-- ===============================================-->

';


if ($notificationcount==0) {

echo '
<li class="nav-item dropdown pe-3">
  <span class="bi bi-bell-fill fs-3 me-1" data-fa-transform="shrink-6" style="'.$local_iconcolor.'"></span>
  </li>
';

} else {
  $notificationdot='notification-indicator notification-indicator-primary ';

echo '
<li class="nav-item dropdown pe-3">
<a class="nav-link '.$notificationdot.' px-0" id="navbarDropdownNotification" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-hide-on-body-scroll="data-hide-on-body-scroll">
<span class="bi bi-bell-fill fs-3 me-1" data-fa-transform="shrink-6" style="'.$local_iconcolor.'"></span>
</a>
<div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-menu-notification dropdown-caret-bg" aria-labelledby="navbarDropdownNotification">
  <div class="card card-notification shadow-none">
    <div class="card-header">
      <div class="row justify-content-between align-items-center">
        <div class="col-auto">
          <h6 class="card-header-title mb-0">Notifications</h6>
        </div>
        <div class="col-auto ps-0 ps-sm-3"><a class="card-link fw-normal" href="#">Mark all as read</a></div>
      </div>
    </div>
    <div class="scrollbar-overlay" style="max-height:19rem">
      <div class="list-group list-group-flush fw-normal fs-10">
        <div class="list-group-title border-bottom">NEW</div>
        <div class="list-group-item">
          <a class="notification notification-flush notification-unread" href="#!">
            <div class="notification-avatar">
              <div class="avatar avatar-2xl me-3">
                <img class="rounded-circle" src="/public/assets/img/team/1-thumb.png" alt="" />

              </div>
            </div>
            <div class="notification-body">
              <p class="mb-1"><strong>Emma Watson</strong> replied to your comment : "Hello world 😍"</p>
              <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">💬</span>Just now</span>

            </div>
          </a>

        </div>
        <div class="list-group-item">
          <a class="notification notification-flush notification-unread" href="#!">
            <div class="notification-avatar">
              <div class="avatar avatar-2xl me-3">
                <div class="avatar-name rounded-circle"><span>AB</span></div>
              </div>
            </div>
            <div class="notification-body">
              <p class="mb-1"><strong>Albert Brooks</strong> reacted to <strong>Mia Khalifa’s</strong> status</p>
              <span class="notification-time"><span class="me-2 fab fa-gratipay text-danger"></span>9hr</span>

            </div>
          </a>

        </div>
        <div class="list-group-title border-bottom">EARLIER</div>
        <div class="list-group-item">
          <a class="notification notification-flush" href="#!">
            <div class="notification-avatar">
              <div class="avatar avatar-2xl me-3">
                <img class="rounded-circle" src="/public/assets/img/icons/weather-sm.jpg" alt="" />

              </div>
            </div>
            <div class="notification-body">
              <p class="mb-1">The forecast today shows a low of 20&#8451; in California. See today’s weather.</p>
              <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">🌤️</span>1d</span>

            </div>
          </a>

        </div>
        <div class="list-group-item">
          <a class="border-bottom-0 notification-unread  notification notification-flush" href="#!">
            <div class="notification-avatar">
              <div class="avatar avatar-xl me-3">
                <img class="rounded-circle" src="/public/assets/img/logos/oxford.png" alt="" />

              </div>
            </div>
            <div class="notification-body">
              <p class="mb-1"><strong>University of Oxford</strong> created an event : "Causal Inference Hilary 2019"</p>
              <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">✌️</span>1w</span>

            </div>
          </a>

        </div>
        <div class="list-group-item">
          <a class="border-bottom-0 notification notification-flush" href="#!">
            <div class="notification-avatar">
              <div class="avatar avatar-xl me-3">
                <img class="rounded-circle" src="/public/assets/img/team/10.jpg" alt="" />

              </div>
            </div>
            <div class="notification-body">
              <p class="mb-1"><strong>James Cameron</strong> invited to join the group: United Nations International Children’s Fund</p>
              <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">🙋‍</span>2d</span>

            </div>
          </a>

        </div>
      </div>
    </div>
    <div class="card-footer text-center border-top"><a class="card-link d-block" href="/app/social/notifications.php">View all</a></div>
  </div>
</div>

</li>
';
}
/*

    <li class="nav-item dropdown">
 <!-- <a class="nav-link notification-indicator notification-indicator-primary px-0 fa-icon-wait" id="navbarDropdownNotification" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-hide-on-body-scroll="data-hide-on-body-scroll"> -->
      <a class="nav-link notification-indicator notification-indicator-primary px-0"              id="navbarDropdownNotification" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-hide-on-body-scroll="data-hide-on-body-scroll"> 
              <i class="bi bi-bell-fill  fs-6 me-1" data-fa-transform="shrink-6" style="’.$local_iconcolor.’"></i>
              <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-menu-notification dropdown-caret-bg" aria-labelledby="navbarDropdownNotification">
                <div class="card card-notification shadow-none">
                  <div class="card-header">
                    <div class="row justify-content-between align-items-center">
                      <div class="col-auto">
                        <h6 class="card-header-title mb-0">Notifications</h6>
                      </div>
                      <div class="col-auto ps-0 ps-sm-3"><a class="card-link fw-normal" href="#">Mark all as read</a></div>
                    </div>
                  </div>
                  <div class="scrollbar-overlay" style="max-height:19rem">
                    <div class="list-group list-group-flush fw-normal fs-10">
                      <div class="list-group-title border-bottom">NEW</div>
                      <div class="list-group-item">
                        <a class="notification notification-flush notification-unread" href="#!">
                          <div class="notification-avatar">
                            <div class="avatar avatar-2xl me-3">
                              <img class="rounded-circle" src="/public/assets/img/team/1-thumb.png" alt="" />

                            </div>
                          </div>
                          <div class="notification-body">
                            <p class="mb-1"><strong>Emma Watson</strong> replied to your comment : "Hello world 😍"</p>
                            <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">💬</span>Just now</span>

                          </div>
                        </a>

                      </div>
                      <div class="list-group-item">
                        <a class="notification notification-flush notification-unread" href="#!">
                          <div class="notification-avatar">
                            <div class="avatar avatar-2xl me-3">
                              <div class="avatar-name rounded-circle"><span>AB</span></div>
                            </div>
                          </div>
                          <div class="notification-body">
                            <p class="mb-1"><strong>Albert Brooks</strong> reacted to <strong>Mia Khalifa’s</strong> status</p>
                            <span class="notification-time"><span class="me-2 fab fa-gratipay text-danger"></span>9hr</span>

                          </div>
                        </a>

                      </div>
                      <div class="list-group-title border-bottom">EARLIER</div>
                      <div class="list-group-item">
                        <a class="notification notification-flush" href="#!">
                          <div class="notification-avatar">
                            <div class="avatar avatar-2xl me-3">
                              <img class="rounded-circle" src="/public/assets/img/icons/weather-sm.jpg" alt="" />

                            </div>
                          </div>
                          <div class="notification-body">
                            <p class="mb-1">The forecast today shows a low of 20&#8451; in California. See today’s weather.</p>
                            <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">🌤️</span>1d</span>

                          </div>
                        </a>

                      </div>
                      <div class="list-group-item">
                        <a class="border-bottom-0 notification-unread  notification notification-flush" href="#!">
                          <div class="notification-avatar">
                            <div class="avatar avatar-xl me-3">
                              <img class="rounded-circle" src="/public/assets/img/logos/oxford.png" alt="" />

                            </div>
                          </div>
                          <div class="notification-body">
                            <p class="mb-1"><strong>University of Oxford</strong> created an event : "Causal Inference Hilary 2019"</p>
                            <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">✌️</span>1w</span>

                          </div>
                        </a>

                      </div>
                      <div class="list-group-item">
                        <a class="border-bottom-0 notification notification-flush" href="#!">
                          <div class="notification-avatar">
                            <div class="avatar avatar-xl me-3">
                              <img class="rounded-circle" src="/public/assets/img/team/10.jpg" alt="" />

                            </div>
                          </div>
                          <div class="notification-body">
                            <p class="mb-1"><strong>James Cameron</strong> invited to join the group: United Nations International Children’s Fund</p>
                            <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">🙋‍</span>2d</span>

                          </div>
                        </a>

                      </div>
                    </div>
                  </div>
                  <div class="card-footer text-center border-top"><a class="card-link d-block" href="/app/social/notifications.php">View all</a></div>
                </div>
              </div>

            </li>
            ';
            */