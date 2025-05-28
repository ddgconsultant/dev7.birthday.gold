<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page and header includes
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/header-nav.inc');
?>


<div class="container my-5">
    <div class="row">
<h1>Create a Post</h1>

        <!-- Left Panel: User Library -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Your Library</h5>
                </div>
                <div class="card-body">
                    <h6>Stored Photos</h6>
                    <div class="d-flex flex-wrap mb-3">
                        <img src="/public/images/sample_products/placeholder_9.jpg" class="img-thumbnail me-2 mb-2" style="width: 75px;">
                        <img src="/public/images/sample_products/placeholder_14.jpg" class="img-thumbnail me-2 mb-2" style="width: 75px;">
                        <img src="/public/images/sample_products/placeholder_8.jpg" class="img-thumbnail me-2 mb-2" style="width: 75px;">
                    </div>
                    <h6>Stored Videos</h6>
                    <div class="mb-3">
                        <video class="img-thumbnail w-100 mb-2" controls>
                            <source src="/public/stored_videos/video1.mp4" type="video/mp4">
                        </video>
                    </div>
                    <h6>Stored Soundtracks</h6>
                    <div class="mb-3">
                        <audio controls class="w-100">
                            <source src="/public/stored_audio/soundtrack1.mp3" type="audio/mpeg">
                        </audio>
                    </div>
                    <h6>Stored Hashtags</h6>
                    <div>
                        <span class="badge bg-primary">#birthday</span>
                        <span class="badge bg-primary">#celebration</span>
                        <span class="badge bg-primary">#friends</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Create Post Form -->
        <div class="col-lg-8">
            <form action="post-handler.php" method="POST" enctype="multipart/form-data">
                <!-- Section 1: Post Title -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Post Title</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="post_title" name="post_title" placeholder="Enter post title" required>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Post Description -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Post Description</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="What's on your mind?" required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Upload Media -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Upload Media</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="media" class="form-label">Upload Photos or Video</label>
                            <input class="form-control" type="file" id="media" name="media[]" accept="image/*,video/*" multiple>
                            <small class="text-muted">You can upload multiple photos or a single video.</small>
                        </div>

                        <div class="mb-3">
                            <label for="soundtrack" class="form-label">Upload a Soundtrack</label>
                            <input class="form-control" type="file" id="soundtrack" name="soundtrack" accept="audio/*">
                            <small class="text-muted">Add a soundtrack to your post (optional).</small>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Thumbnail Upload (Collapsible) -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Thumbnail</h5>
                        <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThumbnail" aria-expanded="false" aria-controls="collapseThumbnail">
                            <i class="bi bi-caret-down-fill"></i>
                        </button>
                    </div>
                    <div id="collapseThumbnail" class="collapse">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="thumbnail" class="form-label">Upload a Thumbnail</label>
                                <input class="form-control" type="file" id="thumbnail" name="thumbnail" accept="image/*">
                                <small class="text-muted">Add a thumbnail for your post (optional).</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Add Hashtags (Collapsible) -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Add Hashtags</h5>
                        <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHashtags" aria-expanded="false" aria-controls="collapseHashtags">
                            <i class="bi bi-caret-down-fill"></i>
                        </button>
                    </div>
                    <div id="collapseHashtags" class="collapse">
                        <div class="card-body">
                            <div class="mb-3">
                                <input class="form-control" type="text" id="hashtags" name="hashtags" placeholder="Add hashtags (e.g., #birthday, #celebration)" data-role="tagsinput">
                                <small class="text-muted">Use #hashtags to make your post more discoverable.</small>
                            </div>
                        </div>
                    </div>
                </div>

         <!-- Section 6: Post Settings (Collapsible) -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Post Options</h5>
        <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSettings" aria-expanded="false" aria-controls="collapseSettings">
            <i class="bi bi-caret-down-fill"></i>
        </button>
    </div>
    <div id="collapseSettings" class="collapse">
        <div class="card-body">
            <!-- Visibility -->
            <div class="mb-3">
                <label for="visibility" class="form-label">Post Visibility</label>
                <select class="form-select" id="visibility" name="visibility" required>
                    <option value="public">Public</option>
                    <option value="friends">Friends Only</option>
                    <option value="private">Private</option>
                </select>
            </div>

            <!-- Location Tagging -->
            <div class="mb-3">
                <label for="location" class="form-label">Tag Location (Optional)</label>
                <input class="form-control" type="text" id="location" name="location" placeholder="Tag a location (e.g., New York, Restaurant Name)">
            </div>

            <!-- Allow Comments -->
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="allowComments" name="allow_comments" checked>
                <label class="form-check-label" for="allowComments">Allow Comments</label>
            </div>

            <!-- Allow Sharing -->
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="allowSharing" name="allow_sharing" checked>
                <label class="form-check-label" for="allowSharing">Allow Sharing</label>
            </div>

            <!-- Notifications -->
            <div class="mb-3">
                <label for="notifications" class="form-label">Allow Notifications</label>
                <select class="form-select" id="notifications" name="notifications">
                    <option value="never">Never</option>
                    <option value="every_message">Every Message</option>
                    <option value="hourly">Hourly</option>
                    <option value="daily">Daily</option>
                </select>
            </div>

            <!-- Pin Post -->
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="pinPost" name="pin_post">
                <label class="form-check-label" for="pinPost">Pin this Post</label>
            </div>

            <!-- Schedule Post Publishing Switch -->
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="schedulePostSwitch" name="schedule_post_switch">
                <label class="form-check-label" for="schedulePostSwitch">Schedule Post Publishing</label>
            </div>

            <!-- Schedule Date/Time Picker (Initially Hidden) -->
            <div class="mb-3" id="scheduleDateTime" style="display: none;">
                <label for="schedulePost" class="form-label">Schedule Post</label>
                <input type="datetime-local" class="form-control" id="schedulePost" name="schedule_post">
                <small class="text-muted">Select a date and time to schedule when this post will be published.</small>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript to show/hide the schedule field based on the switch
    document.getElementById('schedulePostSwitch').addEventListener('change', function() {
        var scheduleField = document.getElementById('scheduleDateTime');
        if (this.checked) {
            scheduleField.style.display = 'block';
        } else {
            scheduleField.style.display = 'none';
        }
    });
</script>


                <!-- Submit Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Create Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Footer includes
$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
