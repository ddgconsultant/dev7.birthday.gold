<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagelang = 'zxx';
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$postId = 123;


$additionalstyles .= '
<style > .main-content { overflow: hidden }
.left-panel { display: flex; flex-direction: column; height: calc(100vh - 75px); border-right: 1px solid #dee2e6;  overflow: hidden }
.comments-list { overflow-y: auto; flex-grow: 1; padding: 1rem; visibility: visible  }
.comment-item { display: flex; align-items: start; margin-bottom: 1rem }
.comment-item img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px }
.comment-body { flex-grow: 1 }
.comment-header { display: flex; justify-content: space-between }
.comment-text { font-size: 0.9rem; margin-bottom: 0.5rem }
.reply-link, .like-info { font-size: 0.8rem }
.comments-list::-webkit-scrollbar { width: 8px }
.comments-list::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 4px }
.comments-list::-webkit-scrollbar-track { background-color: #f1f1f1 }
.left-panel .action-bar { display: flex; justify-content: center; padding: 10px 0; background-color: #f8f9fa }
.left-panel .action-bar a { color: #000; font-size: 1.5rem }
.left-panel .icon-container { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1000px }
.left-panel .icon-container a { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: inherit; font-size: 1.5rem }
.left-panel .icon-title { font-size: 0.7rem; margin-top: 2px; color: #666 }
.monotypenumbers { font-family: "Roboto Mono" }
.error-message { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background-color: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 9999; display: none }

.left-panel .comments-list { display: block; overflow-y: auto }
#large-comments-panel .comments-list { height: calc(100vh - 260px); overflow-y: auto }
.right-panel .chrome-bottom-padding-1{        bottom: 20px;    }
.right-panel .chrome-bottom-padding-2{        bottom: 20px;    }
.right-panel .chrome-bottom-padding-3{        bottom: 50px;    }
.right-panel .chrome-bottom-padding-4{        bottom: 70px;    }
.right-panel .chrome-bottom-padding-seekbar{        bottom: 34px;    }
.right-panel .chrome-bottom-padding-carousel{        bottom: 30px;    }
.right-panel .chrome-bottom-padding-carousel-audio{        bottom: 65px;    }
@media (max-width:991.98px) {
.left-panel { display: none }
.comment-overlay.active { display: block !important }
.right-panel .chrome-bottom-padding-1{        bottom: 30px;    }
.right-panel .chrome-bottom-padding-2{        bottom: 50px;    }
.right-panel .chrome-bottom-padding-3{        bottom: 70px;    }
.right-panel .chrome-bottom-padding-4{        bottom: 90px;    }
.right-panel .chrome-bottom-padding-seekbar{        bottom: 65px;    }
.right-panel .chrome-bottom-padding-carousel{        bottom: 145px;    }
.right-panel .chrome-bottom-padding-carousel-audio{        bottom: 145px;    }
.right-panel .post-header img { width: 40px !important; height: 40px !important; border-radius: 50% }
.right-panel .soundtrack-avatar-icon{width:40px !important; height:40px  !important;}
}
.comment-overlay { display: none; position: fixed; bottom: 0; left: 0; width: 100%; height: calc(50vh - 0px); background-color: rgba(255, 255, 255, 0.98); z-index: 1050; overflow-y: hidden; padding: 1rem; box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) }
.comment-overlay .close-btn { position: absolute; top: 10px; right: 10px; font-size: 1.5rem; color: #333; cursor: pointer }
.write-comment { background-color: #fff; width: 100%; position: relative; padding: 1rem; border-bottom: 1px solid #dee2e6; flex-shrink: 0 }
.comment-overlay .write-comment { left: 0; width: calc(100% - 20px); padding: 1rem; background-color: #fff; border-bottom: 1px solid #dee2e6 }
#large-comments-panel .comments-list { height: calc(100vh - 260px); overflow-y: auto }
#small-comments-panel .comments-list { height: calc(45vh); overflow-y: auto }
#small-comments-panel .comments-list { overflow: auto !important }
.right-panel h1 { margin-top: 150px }
.right-panel .container, .left-panel .container { margin: 50px auto; max-width: 960px; display: flex; justify-content: space-between }
.column { width: 40%; padding: 40px; color: #fff; background: repeating-linear-gradient(0deg, rgba(0, 0, 0, 0.11) 0px, rgba(0, 0, 0, 0.11) 12px, rgba(1, 1, 1, 0.16) 12px, rgba(1, 1, 1, 0.16) 24px, rgba(0, 0, 0, 0.14) 24px, rgba(0, 0, 0, 0.14) 36px, rgba(0, 0, 0, 0.23) 36px, rgba(0, 0, 0, 0.23) 48px, rgba(0, 0, 0, 0.12) 48px, rgba(0, 0, 0, 0.12) 60px, rgba(1, 1, 1, 0.07) 60px, rgba(1, 1, 1, 0.07) 72px, rgba(0, 0, 0, 0.21) 72px, rgba(0, 0, 0, 0.21) 84px, rgba(0, 0, 0, 0.24) 84px, rgba(0, 0, 0, 0.24) 96px, rgba(1, 1, 1, 0.23) 96px, rgba(1, 1, 1, 0.23) 108px, rgba(1, 1, 1, 0.07) 108px, rgba(1, 1, 1, 0.07) 120px, rgba(0, 0, 0, 0.01) 120px, rgba(0, 0, 0, 0.01) 132px, rgba(1, 1, 1, 0.22) 132px, rgba(1, 1, 1, 0.22) 144px, rgba(1, 1, 1, 0.24) 144px, rgba(1, 1, 1, 0.24) 156px, rgba(0, 0, 0, 0) 156px, rgba(0, 0, 0, 0) 168px, rgba(0, 0, 0, 0.12) 168px, rgba(0, 0, 0, 0.12) 180px), repeating-linear-gradient(90deg, rgba(1, 1, 1, 0.01) 0px, rgba(1, 1, 1, 0.01) 12px, rgba(1, 1, 1, 0.15) 12px, rgba(1, 1, 1, 0.15) 24px, rgba(0, 0, 0, 0.09) 24px, rgba(0, 0, 0, 0.09) 36px, rgba(0, 0, 0, 0.02) 36px, rgba(0, 0, 0, 0.02) 48px, rgba(0, 0, 0, 0.1) 48px, rgba(0, 0, 0, 0.1) 60px, rgba(1, 1, 1, 0.07) 60px, rgba(1, 1, 1, 0.07) 72px, rgba(1, 1, 1, 0.15) 72px, rgba(1, 1, 1, 0.15) 84px, rgba(0, 0, 0, 0.18) 84px, rgba(0, 0, 0, 0.18) 96px, rgba(1, 1, 1, 0.15) 96px, rgba(1, 1, 1, 0.15) 108px, rgba(1, 1, 1, 0.09) 108px, rgba(1, 1, 1, 0.09) 120px, rgba(1, 1, 1, 0.07) 120px, rgba(1, 1, 1, 0.07) 132px, rgba(1, 1, 1, 0.05) 132px, rgba(1, 1, 1, 0.05) 144px, rgba(0, 0, 0, 0.1) 144px, rgba(0, 0, 0, 0.1) 156px, rgba(1, 1, 1, 0.18) 156px, rgba(1, 1, 1, 0.18) 168px), repeating-linear-gradient(45deg, rgba(0, 0, 0, 0.24) 0px, rgba(0, 0, 0, 0.24) 16px, rgba(1, 1, 1, 0.06) 16px, rgba(1, 1, 1, 0.06) 32px, rgba(0, 0, 0, 0.16) 32px, rgba(0, 0, 0, 0.16) 48px, rgba(1, 1, 1, 0) 48px, rgba(1, 1, 1, 0) 64px, rgba(1, 1, 1, 0.12) 64px, rgba(1, 1, 1, 0.12) 80px, rgba(1, 1, 1, 0.22) 80px, rgba(1, 1, 1, 0.22) 96px, rgba(0, 0, 0, 0.24) 96px, rgba(0, 0, 0, 0.24) 112px, rgba(0, 0, 0, 0.25) 112px, rgba(0, 0, 0, 0.25) 128px, rgba(1, 1, 1, 0.12) 128px, rgba(1, 1, 1, 0.12) 144px, rgba(0, 0, 0, 0.18) 144px, rgba(0, 0, 0, 0.18) 160px, rgba(1, 1, 1, 0.03) 160px, rgba(1, 1, 1, 0.03) 176px, rgba(1, 1, 1, 0.1) 176px, rgba(1, 1, 1, 0.1) 192px), repeating-linear-gradient(135deg, rgba(1, 1, 1, 0.18) 0px, rgba(1, 1, 1, 0.18) 3px, rgba(0, 0, 0, 0.09) 3px, rgba(0, 0, 0, 0.09) 6px, rgba(0, 0, 0, 0.08) 6px, rgba(0, 0, 0, 0.08) 9px, rgba(1, 1, 1, 0.05) 9px, rgba(1, 1, 1, 0.05) 12px, rgba(0, 0, 0, 0.01) 12px, rgba(0, 0, 0, 0.01) 15px, rgba(1, 1, 1, 0.12) 15px, rgba(1, 1, 1, 0.12) 18px, rgba(0, 0, 0, 0.05) 18px, rgba(0, 0, 0, 0.05) 21px, rgba(1, 1, 1, 0.16) 21px, rgba(1, 1, 1, 0.16) 24px, rgba(1, 1, 1, 0.07) 24px, rgba(1, 1, 1, 0.07) 27px, rgba(1, 1, 1, 0.23) 27px, rgba(1, 1, 1, 0.23) 30px, rgba(0, 0, 0, 0.2) 30px, rgba(0, 0, 0, 0.2) 33px, rgba(0, 0, 0, 0.18) 33px, rgba(0, 0, 0, 0.18) 36px, rgba(1, 1, 1, 0.12) 36px, rgba(1, 1, 1, 0.12) 39px, rgba(1, 1, 1, 0.13) 39px, rgba(1, 1, 1, 0.13) 42px, rgba(1, 1, 1, 0.2) 42px, rgba(1, 1, 1, 0.2) 45px, rgba(1, 1, 1, 0.18) 45px, rgba(1, 1, 1, 0.18) 48px, rgba(0, 0, 0, 0.2) 48px, rgba(0, 0, 0, 0.2) 51px, rgba(1, 1, 1, 0) 51px, rgba(1, 1, 1, 0) 54px, rgba(0, 0, 0, 0.03) 54px, rgba(0, 0, 0, 0.03) 57px, rgba(1, 1, 1, 0.06) 57px, rgba(1, 1, 1, 0.06) 60px, rgba(1, 1, 1, 0) 60px, rgba(1, 1, 1, 0) 63px, rgba(0, 0, 0, 0.1) 63px, rgba(0, 0, 0, 0.1) 66px, rgba(1, 1, 1, 0.19) 66px, rgba(1, 1, 1, 0.19) 69px), linear-gradient(90deg, rgb(239, 53, 115), rgb(79, 2, 93)) }
.hookto { overflow-y: auto; visibility: visible; height: calc(100vh - 260px); overflow-y: auto }

</style > ';


echo '
<div class="container-fluid main-content p-0 pt-0 mb-0 pt-lg-3">
<div class="row my-0 py-0">
';


echo '
<div class="comment-overlay  d-lg-none"> 
<span class="close-btn">&times;</span>
<div id="small-comments-panel" class="comments-container">
';
echo '
<div 
class="hookto comments-list"
data-hook-to-mobile-first="true"
data-hook-to="#hookto-large"
data-hook-to-position="after"
data-hook-to-return="991.98">
';
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/write-comment.inc');



// Determine the number of comments to generate (random between 3 and 15)
$numComments = rand(0, 35);


if ($numComments ==0) {
    echo '<div class="text-center text-muted mt-5">No comments yet</div>
    <div class="text-center my-4">
'.$icons_writecomment[array_rand($icons_writecomment)].'
</div>
<div class="text-center text-muted">
    Start the conversation!
    </div>';
} else {
// Loop to generate the comments
for ($i = 0; $i < $numComments; $i++) {
    $avatarNumber = rand(1, 10);
    $avatarSrc = "/public/avatars/sample_users/placeholder_$avatarNumber.png";
    $commentText = $qik->generateLoremIpsum((rand(3, 30)), 'words');
    $timeAgo = $qik->generateRandomDate();
    $likeCount = rand(2, 1000000);
    $usernames = ['User1', 'User2', 'User3', 'User4', 'User5'];
    $username = $usernames[array_rand($usernames)];

    $timeAgomessage = $qik->timeago($timeAgo, 90, 'm/d/y', 'm-d');


    // Output comment structure
    echo '
<div class="comment-item">
<a href="/social/user-profile?">  <img src="' . $avatarSrc . '" alt="User Avatar">  </a>
<div class="comment-body">
<div class="comment-header">
<a href="/social/user-profile?">  <strong>' . $username . '</strong>   </a>
</div>
<div class="comment-text">
' . $commentText . '... <span class="toggle-text text-muted" style="cursor: pointer;">more <i class="bi bi-caret-down-fill"></i></span>
</div>
<div class="d-flex justify-content-between align-items-center">
<div>
<span class="text-muted small me-2">' . $timeAgomessage['shortmessagevalue'] . '</span>
<a href="#" class="reply-link">Reply</a>
</div>
<span class="like-info icon-container-action text-muted" data-action="post-comment-like">
<i class="bi bi-hand-thumbs-up-fill icon"></i> ' . $qik->formatShortNumber($likeCount) . '
</span>
</div>

</div>
</div>
';
}
}
?>
</div> <!-- END OF hookto -->
</div> <!-- END OF comments-container -->
</div> <!-- END OF comment-overlay -->

<?PHP


/// LEFT PANEL ===============================================================================
echo '
<!-- Left Panel: 1/3 width for large screens -->
<div class="col-lg-4 left-panel p-0 m-0">
';

echo '
<!-- Action Icon Bar -->
<div class="action-bar m-0 bg-secondary-subtle px-5 py-3 ">
<div class="icon-container">
<a href="/social/" title="Home">
<i class="bi bi-house-door-fill text-dark"></i>
<div class="icon-title">Home</div>
</a>
<a href="/social/search" title="Search">
<i class="bi bi-search text-dark"></i>
<div class="icon-title">Search</div>
</a>
<a href="/social/create" title="Create Post">
<i class="bi bi-plus-circle-fill text-dark"></i>
<div class="icon-title">Create</div>
</a>
<a href="/social/activity" title="Bookmarks & Activity">
<i class="bi bi-bookmark-fill text-dark"></i>
<div class="icon-title">Activity</div>
</a>
<a href="/social/settings" title="Settings">
<i class="bi bi-gear-fill text-dark"></i>
<div class="icon-title">Settings</div>
</a>
</div>
</div>
';

echo '
<hr class="mt-0 pt-0">  
';


echo '
<!-- Comments List -->
<div id="large-comments-panel" class="comments-container ps-2">
<!-- Write Comment Section -->
';


echo '
<div  id="hookto-large" class="px-1">
<!-- Comments List dyamically placed by HOOKTO -->
</div>

</div>  <!-- END OF comments-container -->
';

echo '
</div> <!-- END OF Left Panel -->';


echo '
<!-- Right Panel: 2/3 width -->
<div class="col-lg-8 pe-lg-4">
';

$postTypes = ['images', 'images_audio', 'video', 'video', 'text', 'text_audio', 'video', 'video'];
$post['type'] = $postTypes[array_rand($postTypes)];

switch ($post['type']) {
    case 'images':
        include($_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-images.inc');
        break;
    case 'images_audio':
        include($_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-images_audio.inc');
        break;
    case 'video':
        include($_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-video.inc');
        break;
    case 'text':
        include($_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-text.inc');
        break;
    case 'text_audio':
        include($_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-text_audio.inc');
        break;
    default:
        // Optionally handle the case where $post['type'] is not recognized
        echo 'Invalid post type';
        break;
}




// Navigation Shortcut menu
echo '<div class="modal fade" id="keyboardShortcutsModal" tabindex="-1" aria-labelledby="keyboardShortcutsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="keyboardShortcutsLabel">Introducing keyboard shortcuts!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled">
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>Go to previous post</span>
                        <kbd>▲</kbd>
                    </li>
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>Go to next post</span>
                        <kbd>▼</kbd>
                    </li>
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>Like post</span>
                        <kbd>L</kbd>
                    </li>
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>Mute / unmute sound</span>
                        <kbd>M</kbd>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Trigger button for the modal (you can adjust placement or trigger behavior) -->
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#keyboardShortcutsModal">
    Keyboard Shortcuts
</button>
';
$additionalstyles.='
<style>
.modal-dialog-centered {
    display: flex;
    align-items: center;
    justify-content: center;
}

kbd {
    padding: 5px 10px;
    background-color: #f7f7f7;
    border-radius: 3px;
    font-size: 1rem;
}
    </style>
';

?>







</div>
</div>
</div>


<script>
    document.querySelectorAll('.toggle-text').forEach(item => {
        item.addEventListener('click', function() {
            let text = this.innerHTML;
            if (text.includes('more')) {
                this.innerHTML = 'less <i class="bi bi-caret-up-fill"></i>';
                this.previousElementSibling.textContent = 'This is the full comment that shows after clicking show more.';
            } else {
                this.innerHTML = 'more <i class="bi bi-caret-down-fill"></i>';
                this.previousElementSibling.textContent = 'This is a sample comment, showing the first 100 characters...';
            }
        });
    });
</script>

<?php

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/js-scrolling.inc');

$footerattribute['bottomfooter'] = '
<script src="/public/js/jquery.hookto.js"></script>' .
    "<script>

</script>
";
$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
