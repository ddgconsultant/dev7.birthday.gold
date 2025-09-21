<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

if (!$account->isadmin()) {
    echo '
    <div class="container main-content py-12">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-12 mt-5">
                    <img src="/public/images/logo/bg_icon.png">
                    <h1 class="display-1">Coming Soon</h1>
                    <h1 class="mb-4">Our Community Social Network Feature</h1>
                    <p class="mb-4">This big dessert isn\'t quite ready to come out of the oven. Check back soon.</p>
                    <a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
                </div>
            </div>
        </div>
    </div>';
    exit();
}
?>

<div class="container main-content mt-5">
  <div class="row">
    <!-- Left Column: Create Post and Comments -->
    <div class="col-md-4">
      <h3>Create a Post</h3>
      <form id="postForm" enctype="multipart/form-data">
        <div class="mb-3">
          <textarea class="form-control" id="postContent" rows="3" placeholder="What's on your mind?" required></textarea>
        </div>

        <div class="mb-3">
          <input type="file" class="form-control" id="media" name="media">
        </div>

        <div class="mb-3">
          <input type="text" class="form-control" id="tags" placeholder="Add tags (comma separated)">
        </div>

        <div class="mb-3">
          <input type="text" class="form-control" id="location" placeholder="Add location (Optional)">
        </div>

        <button type="submit" class="btn btn-primary">Post</button>
      </form>

      <div id="result" class="mt-4"></div>

      <!-- Comment Section -->
      <div class="mt-5">
        <h3>Comments</h3>
        <div class="comment mb-3">
          <div class="d-flex">
            <img src="/public/images/avatar.jpg" alt="User" class="rounded-circle me-3" style="width: 50px;">
            <div>
              <strong>John Doe:</strong> This looks amazing!
            </div>
          </div>
        </div>

        <div class="comment mb-3">
          <div class="d-flex">
            <img src="/public/images/avatar2.jpg" alt="User" class="rounded-circle me-3" style="width: 50px;">
            <div>
              <strong>Jane Smith:</strong> Can't wait to try this feature!
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Posts -->
    <div class="col-md-8">
      <h3>Recent Posts</h3>

      <!-- Post 1 -->
      <div class="post mb-4">
        <div class="d-flex">
          <img src="/public/images/avatar.jpg" alt="User" class="rounded-circle me-3" style="width: 50px;">
          <div>
            <h5>John Doe</h5>
            <p>Just checking out the new social features on Birthday.Gold!</p>
            <div class="post-image mb-2">
              <img src="/public/images/sample-post.jpg" alt="Post Image" class="img-fluid">
            </div>
            <div class="d-flex align-items-center">
              <button onclick="likePost(1)" class="btn btn-outline-primary me-2">Like</button>
              <span class="me-2">275 Likes</span>
              <button class="btn btn-outline-secondary">Comment</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Post 2 -->
      <div class="post mb-4">
        <div class="d-flex">
          <img src="/public/images/avatar2.jpg" alt="User" class="rounded-circle me-3" style="width: 50px;">
          <div>
            <h5>Jane Smith</h5>
            <p>Celebrating my birthday with some amazing rewards!</p>
            <div class="post-image mb-2">
              <img src="/public/images/sample-post2.jpg" alt="Post Image" class="img-fluid">
            </div>
            <div class="d-flex align-items-center">
              <button onclick="likePost(2)" class="btn btn-outline-primary me-2">Like</button>
              <span class="me-2">312 Likes</span>
              <button class="btn btn-outline-secondary">Comment</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  $('#postForm').submit(function(e) {
    e.preventDefault();

    var formData = new FormData();
    formData.append('content', $('#postContent').val());
    formData.append('tags', $('#tags').val());
    formData.append('location', $('#location').val());

    var file = $('#media')[0].files[0];
    if (file) {
      formData.append('media', file);
    }

    // AJAX request to handle form submission using the updated path
    $.ajax({
      url: '/myaccount/myaccount-actions/social-handler-backend.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#result').html('<div class="alert alert-success">Post Created!</div>');
        $('#postForm')[0].reset(); // Reset form
      },
      error: function() {
        $('#result').html('<div class="alert alert-danger">Error creating post!</div>');
      }
    });
  });
});

function likePost(postId) {
  $.ajax({
    url: '/myaccount/myaccount-actions/social-handler-like.php',
    type: 'POST',
    data: { post_id: postId },
    success: function(response) {
      if (response.success) {
        alert('Post liked!');
      } else {
        alert(response.error);
      }
    },
    error: function() {
      alert('Error liking post!');
    }
  });
}
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
