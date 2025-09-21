<?PHP
if (!isset($writecomment_included)) {
    $writecomment_included = 1;
    $writecommentincrement = 1;
} else {
    $writecomment_included++;
    $writecommentincrement++;
}

if ($writecomment_included==1) {
$additionalstyles.='
<style>

#submitComment {
    border-left: none;
}

/* Ensure the emoji button and the submit button blend into the input */
.input-group .emoji-btn,
.input-group .btn-outline-secondary {
    border-color: #ced4da; /* Match the border color of the input field */
    border-width: 1px 0 1px 1px; /* Top, right, bottom, left */
    border-radius: 0; /* No rounded corners by default */
    border-left: none; /* Remove left border between buttons */
}

/* Remove right border from the emoji button */
.input-group .emoji-btn {
    border-right: none;
}

/* Remove right border from the input field */
.input-group .form-control {
    border-right: none; /* Remove right border to merge with buttons */
}

/* Ensure the last button has rounded right corners to match the input field */
.input-group .btn-outline-secondary:last-child {
    border-right: 1px solid #ced4da; /* Ensure the last button has a right border */
    border-top-right-radius: .375rem; /* Match input field\'s top right border radius */
    border-bottom-right-radius: .375rem; /* Match input field\'s bottom right border radius */
}

/* Ensure the input field has rounded left corners */
.input-group .form-control {
    border-top-left-radius: .375rem; /* Top left corner rounded */
    border-bottom-left-radius: .375rem; /* Bottom left corner rounded */
}

.bg-info-subtle {
    background-color: rgba(0, 123, 255, 0.1) !important; /* Light blue background */
}

</style>
';
}



// Set the $commentInputID variable
$commentformholderID = 'commentFormHolder' . $writecommentincrement;
$commentFormID = 'commentForm' . $writecommentincrement;
$commentInputID = 'commentInput' . $writecommentincrement;
$maxChars = 500; // Maximum characters allowed in a comment

echo '
<div id="'.$commentformholderID.'" class="write-comment py-4">
    <form id="'.$commentFormID.'" action="/social/post-comment" method="POST">
        '. $display->inputcsrf_token().'
<input type="hidden" name="replyto_userid" id="replyto_userid" value="">

     <!-- Comment input group with emoji and send button -->
        <div class="input-group">
            <input type="text" id="'.$commentInputID.'" name="comment" class="form-control" placeholder="Write a comment..." maxlength="'.$maxChars.'" required>
          <button type="button" class="btn btn-outline-secondary emoji-btn" id="emojiBtn">
           <i class="bi bi-emoji-smile"></i>
            </button>
            <button type="submit" class="btn btn-outline-secondary" id="submitComment">
                <i class="bi bi-send"></i>
            </button>
        </div>
        
        <!-- Character counter, hidden initially -->
        <div id="charCounter" class="form-text text-danger mt-0 text-end small fs-12" style="display: none;"> </div>
    </form>

    <!-- User lookup results -->
    <div id="userLookupResults" class="list-group" style="display: none;"></div>
</div>
';
echo "
<script>
$(document).ready(function() {
    const maxChars = ".$maxChars.";
    const countdownStart = 30;
    let preventRemove = false; // Flag to prevent class removal when needed

    // Character counter
    $('#".$commentInputID."').on('input', function() {
        let charsTyped = $(this).val().length;
        let charsLeft = maxChars - charsTyped;

        if (charsLeft <= countdownStart) {
            $('#charCounter').show(); // Show the counter only when <= 470 characters
            $('#charCounter').text(charsLeft); // Display remaining characters

            if (charsLeft <= 0) {
                $('#charCounter').text('Max Limit'); // When limit reached
            }
        } else {
            $('#charCounter').hide(); // Hide the counter if less than 470 characters are typed
        }

        // Handle @user lookup
        let inputVal = $(this).val();
        if (inputVal.includes('@')) {
            let query = inputVal.split('@').pop(); // Extract query after @

            if (query.length > 1) {
                $.get('/social/components/userlookup.php', { q: query }, function(data) {
                    $('#userLookupResults').html(data).show();
                });
            } else {
                $('#userLookupResults').hide();
            }
        } else {
            $('#userLookupResults').hide();
        }
    });

    // Handle reply functionality
    $('.reply-link').on('click', function(e) {
        e.preventDefault();

        let username = $(this).closest('.comment-item').find('strong').text(); // Get the username being replied to
        // --DEBUG-- console.log('Reply to @' + username + ' clicked.'); // Log for debugging

        // Change the placeholder and set the hidden field
        $('#".$commentInputID."').attr('placeholder', 'Reply to @' + username);
        $('#".$commentInputID."').val(''); // Clear the comment
        $('#replyto_userid').val(username); // Set the hidden field with the username

        // Add the bg-info-subtle class to the write-comment div
        $('#".$commentformholderID."').addClass('bg-info-subtle');

        // Set focus to the input field and place the cursor in it
        $('#".$commentInputID."').focus();

        // Set the flag to prevent removing the class immediately
        preventRemove = true;
        // --DEBUG-- console.log('Prevent remove flag set.');

        // Log to verify focus
        // --DEBUG-- console.log('Input field focused.');
    });

    // Handle form submission via Ajax
    $('#".$commentFormID."').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize(); // Serialize form data including CSRF token and replyto_userid

        $.post('/social/post-comment', formData, function(response) {
            // Handle response here (e.g., display success message or reset the form)
            $('#".$commentInputID."').val(''); // Clear input after successful post
            $('#charCounter').hide(); // Reset character counter visibility
            $('#userLookupResults').hide(); // Hide user lookup results
            $('#replyto_userid').val(''); // Clear the replyto_userid hidden field
            $('#".$commentInputID."').attr('placeholder', 'Write a comment...'); // Reset placeholder
            $('#".$commentformholderID."').removeClass('bg-info-subtle'); // Remove bg-info-subtle after comment is posted

        }).fail(function() {
            // Handle failure
            alert('Error posting the comment.');
        });
    });

    // Handle emoji picker placeholder
    $('#emojiBtn').on('click', function() {
        alert('Emoji picker clicked! (Implement emoji picker)');
    });

    // Remove bg-info-subtle class and reset placeholder if the user clicks outside the commentFormHolder
    $(document).on('click', function(e) {
        setTimeout(function() {
            const writeCommentDiv = $('#".$commentformholderID."');
            const inputField = $('#".$commentInputID."');
            const emojiBtn = $('#emojiBtn');
            const submitBtn = $('#submitComment');

            // Log to verify where the click occurred
            // --DEBUG-- console.log('Clicked element: ', e.target);

            // Prevent removing class if the flag is set (when clicking 'Reply')
            if (preventRemove) {
                preventRemove = false; // Reset the flag for next interaction
                // --DEBUG-- console.log('Prevent remove flag reset.');
                return;
            }

            // Check if the clicked element is outside the writeCommentDiv, emoji button, or submit button
            if (!writeCommentDiv.is(e.target) && writeCommentDiv.has(e.target).length === 0 &&
                !emojiBtn.is(e.target) && emojiBtn.has(e.target).length === 0 &&
                !submitBtn.is(e.target) && submitBtn.has(e.target).length === 0) {
                // Remove bg-info-subtle class
                writeCommentDiv.removeClass('bg-info-subtle');
                // --DEBUG-- console.log('bg-info-subtle class removed.');

                // Reset placeholder to 'Write a comment...' and clear replyto_userid if no reply is active
                $('#".$commentInputID."').attr('placeholder', 'Write a comment...');
                $('#replyto_userid').val(''); // Clear the replyto_userid                  
             $('#".$commentInputID."').val(''); // Clear the comment
            }
        }, 10); // Short delay to prevent instant removal
    });

    // Reset back to 'Write a comment...' when input field loses focus and no reply is active
    $('#".$commentInputID."').on('blur', function() {
         if ($('#replyto_userid').val() === '') {
            $(this).attr('placeholder', 'Write a comment...');
            $('#".$commentformholderID."').removeClass('bg-info-subtle');
            $('#replyto_userid').val(''); // Clear the replyto_userid     
       
        } 
    });
});
</script>
";
