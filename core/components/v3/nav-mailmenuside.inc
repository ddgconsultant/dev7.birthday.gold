<?PHP

###########################################################################
###########################################################################
# THIS PAGE IS MANAGED IN THE CORE/v3 FOLDER
###########################################################################
###########################################################################


$lefpanelcontent['body_class']=$lefpanelcontent['body_class']??'container';
$lefpanelcontent['panel_class']=$lefpanelcontent['panel_class']??'col-md-3 col-lg-3';
if (!isset($lefpanelcontent['prepanel'])) $lefpanelcontent['prepanel']='';
if (!isset($lefpanelcontent['postpanel'])) $lefpanelcontent['postpanel']='';


$additionalstyles.="
<style>
/* USERLEFTPANEL styles ======================================== */
    .main-content {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .random-text {
        margin-top: 60px;
    }

    .border-right {
        border-right: 1px solid lightgrey;
    }

    .title-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 5px; /* Adjust to your needs */
        }
        .title-wrapper h4 {
            margin: 0;
            white-space: nowrap;
            padding-right: 10px; /* Adjust space between text and line */
        }
        .title-wrapper .title-line {
            flex-grow: 1;
            height: 1px;
            background-color: #000; /* Adjust color of the line */
        }
        .list-unstyled.list-userdetail li {
            padding: 5px 0;
            font-size: .9rem; /* Adjust font size */
        }
.list-unstyled.list-userdetail li i {
margin-left: 10px; /* Add right margin to icons */
    margin-right: 10px; /* Add right margin to icons */
}
        .placeholder {
        background: none !important; /* Remove the background */
        display: inline-block; /* Ensure it displays correctly inline */
        margin-left:5px;
        cursor: text !important;
        }

a:hover .placeholder {
    cursor: pointer !important; /* Change the cursor to pointer on hover */
}

</style>
";


echo '
<!-- Main Content -->
<div class="main-content">
    <div class="'.$lefpanelcontent['body_class'].' random-text">
        <div class="row">

        <div class="'.$lefpanelcontent['panel_class'].' d-none d-md-block border-right">
';

/*
echo '
<div class="xxemail-sidebar">
<div class="email-sidebar-header d-grid"> <a href="javascript:;" class="btn btn-primary d-none compose-mail-btn"><i class="bx bx-plus me-2"></i> Compose</a>
</div>
<div class="email-sidebar-content">
';
*/
echo '
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
                <a href="javascript:;" class="list-group-item d-flex align-items-center mt-4"><i class="bi bi-gear me-3 font-20"></i><span>Settings</span></a>
        </div>


    ';


echo '</div>';
echo '</div>';