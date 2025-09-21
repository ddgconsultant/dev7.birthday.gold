<?PHP

echo '
<div class="email-sidebar">
<div class="email-sidebar-header d-grid"> <a href="javascript:;" class="btn btn-primary d-none compose-mail-btn"><i class="bx bx-plus me-2"></i> Compose</a>
</div>
<div class="email-sidebar-content">
    <div class="email-navigation">
        <div class="list-group list-group-flush"> <a href="/myaccount/mail_box" class="list-group-item active d-flex align-items-center"><i class="bx bxs-inbox me-3 font-20"></i><span>Inbox</span>
        ';
        if (!empty($featuremailcount['unread'])) echo '<span class="badge bg-primary rounded-pill ms-auto">'.number_format($featuremailcount['unread']).'</span>';
        
        echo '</a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center"><i class="bi bi-cake-fill me-3 font-20"></i><span>Birthday Rewards</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center"><i class="bx bxs-star me-3 font-20"></i><span>Sponsored</span></a>
            
            <a href="javascript:;" class="list-group-item d-flex align-items-center"><i class="bi bi-coin me-3 font-20"></i><span>Deals</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center"><i class="bi bi-shop me-3 font-20"></i><span>Marketing</span></a>


            <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bxs-send me-3 font-20"></i><span>Sent</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bxs-file-blank me-3 font-20"></i><span>Drafts</span><span class="badge bg-primary rounded-pill ms-auto">4</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center -- d-none"><i class="bx bxs-bookmark me-3 font-20"></i><span>Notices</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bxs-message-rounded-error me-3 font-20"></i><span>Chats</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bx-mail-send me-3 font-20"></i><span>Scheduled</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bxs-envelope-open me-3 font-20"></i><span>All Mail</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bxs-info-circle me-3 font-20"></i><span>Spam</span></a>


            <a href="javascript:;" class="list-group-item d-flex align-items-center"><i class="bi bi-archive-fill me-3 font-20"></i><span>Archive</span></a>
        </div>
    </div>

    ';
    
$avatar='/public/images/defaultavatar.png';
$avatarbuttontag='Upload';
if (!empty($current_user_data['avatar'])) { $avatar='/'.$current_user_data['avatar'];  $avatarbuttontag='Change';}

echo '

    <div class="email-meeting">
        <div class="list-group list-group-flush">
            <div class="list-group-item d-none"><span>Meet</span>
            </div> <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bxs-video me-3 font-20"></i><span>Start a meeting</span></a>
            <a href="javascript:;" class="list-group-item d-flex align-items-center d-none"><i class="bx bxs-group me-3 font-20"></i><span>Join a meeting</span></a>
            <div class="list-group-item email-hangout cursor-pointer border-top">
                <div class="d-flex align-items-center">
          
               
                    ';

                    ?>
                    <div class="dropdown">
                        <div class="dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown"><a href="/myaccount/settings">Settings <i class='bi bi-gear'></i></a>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>