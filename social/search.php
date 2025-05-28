<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page and header includes
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/header-nav.inc');

$additionalstyles .='
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
.left-panel .comments-list { display: block; overflow-y: auto }
#large-comments-panel .comments-list { height: calc(100vh - 260px); overflow-y: auto }
.right-panel .chrome-bottom-padding-1{        bottom: 20px;    }
.right-panel .chrome-bottom-padding-2{        bottom: 20px;    }
.right-panel .chrome-bottom-padding-4{        bottom: 70px;    }

@media (max-width:991.98px) {
.left-panel { display: none }
.comment-overlay.active { display: block !important }
.right-panel .chrome-bottom-padding-1{        bottom: 30px;    }
.right-panel .chrome-bottom-padding-2{        bottom: 50px;    }
.right-panel .chrome-bottom-padding-4{        bottom: 90px;    }
.right-panel .post-header img { width: 40px !important; height: 40px !important; border-radius: 50% }
.right-panel .soundtrack-avatar-icon{width:40px  !important;height:40px  !important;}
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
.icon-container{font-size:1rem; color:#666;}

/* Hover effect for search result cards */
.right-panel .card:hover {
    background-color: rgba(211, 211, 211, 0.5) !important; /* Light grey */
    transition: background-color 0.3s ease;
}

</style > ';

// Set the number of results to generate
$numResults = rand(3, 20);

// Available usernames and avatars
$usernames = ['User1', 'User2', 'User3', 'User4', 'User5', 'User6', 'User7', 'User8', 'User9', 'User10'];

?>

<div class="container my-5">
    <!-- Search Form -->
    <div class="row mb-4">
    <h1>Search Posts</h1>
    <div class="card mb-4">
    <div class="card-body">
        <div class="col-lg-8 col-md-10 mx-auto">
            <form class="d-flex" action="/search-results.php" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="Search posts..." aria-label="Search" required>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </form>
        </div>
    </div>
    
    <!-- Content Type Filters (Checkboxes) -->
    <div class="row mb-4">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="d-flex justify-content-between">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="filter[]" value="users" id="users" checked>
                    <label class="form-check-label" for="users">Users</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="filter[]" value="videos" id="videos" checked>
                    <label class="form-check-label" for="videos">Videos</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="filter[]" value="images" id="images" checked>
                    <label class="form-check-label" for="images">Images</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="filter[]" value="soundtracks" id="soundtracks" checked>
                    <label class="form-check-label" for="soundtracks">SoundTracks</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="filter[]" value="places" id="places" checked>
                    <label class="form-check-label" for="places">Places</label>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>


    <!-- Main Row (Filters + Results) -->
    <div class="row">
        <!-- Filters Panel -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Filters</h5>
                </div>
                <div class="card-body">
                    <!-- Sort By -->
                    <h6>Sort By</h6>
                    <select class="form-select mb-3" name="sort_by">
                        <option value="recent">Most Recent</option>
                        <option value="popular">Most Popular</option>
                    </select>

                    <!-- Date Range Filter -->
                    <h6>Date Range</h6>
                    <input type="date" class="form-control mb-3" name="start_date" placeholder="Start Date">
                    <input type="date" class="form-control mb-3" name="end_date" placeholder="End Date">

                    <!-- Viewable Categories -->
                    <h6>Viewable Categories</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category[]" value="friends" id="friends">
                        <label class="form-check-label" for="friends">Friends</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category[]" value="public" id="public">
                        <label class="form-check-label" for="public">Public</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category[]" value="groups" id="groups">
                        <label class="form-check-label" for="groups">Groups</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results Panel -->
        <div class="col-lg-8 right-panel">
            <div id="search-results">
                <?php
                // Loop to dynamically generate search results
                for ($i = 0; $i < $numResults; $i++) {
                    // Generate random avatar, username, text, and likes
                    $avatarNumber = rand(1, 10);
                    $avatarSrc = "/public/avatars/sample_users/placeholder_$avatarNumber.png";
                    $postText = $qik->generateLoremIpsum(rand(10, 50), 'words');
                    $timeAgo = $qik->generateRandomDate();
                    $likeCount = rand(2, 10000);
                    $username = $usernames[array_rand($usernames)];
                    $timeAgomessage = $qik->timeago($timeAgo, 90, 'm/d/y', 'm-d');

                    // Output search result structure
                    echo '
                    <div class="card mb-2">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center mb-2">
                                <img src="' . $avatarSrc . '" class="rounded-circle me-2" style="height:40px; width:40px;" alt="User Avatar">
                                <div>
                                    <h5 class="mb-0 fw-bold">' . $username . ' </h5>
                                    <small class="text-muted">' . $timeAgomessage['shortmessagevalue'] . '</small>
                                </div>
                            </div>
                            <p class="small mb-2">
                                ' . $postText . '
                            </p>
                            <!-- Actions -->
                            <div class="d-flex justify-content-between align-items-center">

<div class="g-5">
   <span class="icon-container pe-5">
                        <i class="bi bi-hand-thumbs-up-fill icon"></i>
                        <span class="interaction-count">'.$qik->formatShortNumber(rand(1,10000)).'</span>
                    </span>
                       <span class="icon-container pe-5">
                        <i class="bi bi-bookmark-fill icon"></i>
                        <span class="interaction-count">'.$qik->formatShortNumber(rand(1,10000)).'</span>
                    </span>

                       <span class="icon-container pe-5">
                        <i class="bi bi-share-fill icon"></i>
                        <span class="interaction-count">'.$qik->formatShortNumber(rand(1,10000)).'</span>
                    </span>
  </div>

                                <button class="btn btn-sm btn-link text-muted">View Post</button>
                            </div>
                        </div>
                    </div>';
                }
                ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Search Results Pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                    </li>
                    <li class="page-item active">
                        <a class="page-link" href="#">1</a>
                    </li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php
// Footer includes
$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
