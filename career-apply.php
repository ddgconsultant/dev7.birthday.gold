<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$content_id = isset($_GET['i']) ? $qik->decodeId($_GET['i']) : 0;
$success_message = '';
$error_message = '';
$editing = isset($_GET['edit']) && $_GET['edit'] == '1';

// Get job description from bg_content
$sql = 'SELECT * FROM bg_content WHERE id = :id';
$stmt = $database->prepare($sql);
$stmt->execute(['id' => $content_id]);
$job_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job_data) {
    header('Location: /careers');
    exit;
}

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if (isset($_POST['action']) && $_POST['action'] == 'edit_job' && $account->isadmin()) {
        // Handle job content update
        $sql = 'UPDATE bg_content SET 
                display_name = :display_name,
                content = :content,
                modify_dt = NOW()
                WHERE id = :id';
        $stmt = $database->prepare($sql);
        $result = $stmt->execute([
            'display_name' => $_POST['display_name'],
            'content' => $_POST['content'],
            'id' => $content_id
        ]);
        
        if ($result) {
            $success_message = 'Job description updated successfully!';
            $job_data['display_name'] = $_POST['display_name'];
            $job_data['content'] = $_POST['content'];
            $editing = false;
        } else {
            $error_message = 'Error updating job description.';
        }
    } elseif (empty($current_user_data['user_id'])) {
        $error_message = 'Please log in to apply for this position.';
    } else {
        $resume_file = $_FILES['resume'] ?? null;
        $cover_letter = $_POST['cover_letter'] ?? '';
        
        if ($resume_file && $resume_file['error'] === UPLOAD_ERR_OK) {
            // Improved file handling
            $allowed_types = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
            ];
            
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $resume_file['tmp_name']);
            finfo_close($file_info);
            
            // Check if file type is allowed
            if (array_key_exists($mime_type, $allowed_types)) {
                $file_extension = $allowed_types[$mime_type];
                
                // Generate a more secure filename with random component
                $new_filename = 'resume_' . bin2hex(random_bytes(16)) . '.' . $file_extension;
                
                // Store outside web root if possible, otherwise use a protected directory
                $upload_base_dir = dirname($_SERVER['DOCUMENT_ROOT']) . '/secure_uploads/';
                if (!is_dir($upload_base_dir)) {
                    // Fall back to a directory within web root, but with proper protection
                    $upload_base_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                }
                
                $upload_dir = $upload_base_dir . 'resumes/';
                
                // Ensure directory exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0750, true);
                }
                
                // Create .htaccess to protect directory if within web root
                if (strpos($upload_dir, $_SERVER['DOCUMENT_ROOT']) === 0) {
                    $htaccess_file = $upload_dir . '.htaccess';
                    if (!file_exists($htaccess_file)) {
                        file_put_contents($htaccess_file, 
                            "# Deny direct access to files\n" .
                            "<FilesMatch \"\\.(pdf|doc|docx)$\">\n" .
                            "  Order deny,allow\n" .
                            "  Deny from all\n" .
                            "</FilesMatch>\n"
                        );
                    }
                }
                
                // Additional file validation - check file size
                if ($resume_file['size'] > 10485760) { // 10MB limit
                    $error_message = 'File size exceeds limit. Please upload a file smaller than 10MB.';
                } else {
                    // Move uploaded file
                    if (move_uploaded_file($resume_file['tmp_name'], $upload_dir . $new_filename)) {
                        // Sanitize cover letter
                        $safe_cover_letter = htmlspecialchars($cover_letter, ENT_QUOTES, 'UTF-8');
                        
                        // Store application in bg_content
                        $application_data = [
                            'resume_file' => $new_filename,
                            'cover_letter' => $safe_cover_letter,
                            'job_id' => $content_id,
                            'application_date' => date('Y-m-d H:i:s'),
                            'upload_path' => $upload_dir // Store path for retrieval
                        ];
                        
                        $sql = 'INSERT INTO bg_content (name, category, type, description, content, create_dt, status) 
                                VALUES (:name, :category, :type, :description, :content, NOW(), "active")';
                        $stmt = $database->prepare($sql);
                        $stmt->execute([
                            'name' => 'job_application_' . $content_id . '_' . time(),
                            'category' => 'job_applications',
                            'type' => 'application',
                            'description' => $current_user_data['user_id'],
                            'content' => json_encode($application_data)
                        ]);
                        
                        $success_message = 'Your application has been submitted successfully!';
                    } else {
                        $error_message = 'There was an error uploading your resume.';
                    }
                }
            } else {
                $error_message = 'Please upload a PDF or Word document.';
            }
        } else {
            $error_message = 'Please upload your resume.';
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
if (!empty($current_user_data['user_id'])) {
    include($dir['core_components'] . '/bg_user_profileheader.inc');
    include($dir['core_components'] . '/bg_user_leftpanel.inc');
}

$additionalstyles .= '
<style>
.job-content { margin-bottom: 2rem; }
.application-form { max-width: 600px; }
.error-message { color: #dc3545; }
.success-message { color: #28a745; }
</style>
';

echo '    
<div class="container main-content ' . (!empty($current_user_data['user_id']) ? 'mt-0 pt-0' : '') . '">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">' . htmlspecialchars($job_data['display_name']) . '</h2>
        <div>
            ' . ($account->isadmin() ? '
                <a href="?i=' . $qik->encodeId($content_id) . '&edit=' . ($editing ? '0' : '1') . '" 
                   class="btn btn-sm btn-' . ($editing ? 'danger' : 'primary') . ' me-2">
                   ' . ($editing ? 'Cancel Edit' : 'Edit Job') . '
                </a>' : '') . '
            <a href="/careers" class="btn btn-sm btn-outline-secondary">Back To Careers</a>
        </div>
    </div>';

if ($error_message) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
}
if ($success_message) {
    echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
}

echo '<div class="card"><div class="card-body">';

if ($editing && $account->isadmin()) {
    // Display edit form
    echo '
    <form method="post" class="edit-job-form">
        <input type="hidden" name="action" value="edit_job">
        <div class="mb-3">
            <label for="display_name" class="form-label">Job Title</label>
            <input type="text" class="form-control" id="display_name" name="display_name" 
                   value="' . htmlspecialchars($job_data['display_name']) . '" required>
        </div>
        <div class="mb-3">
            <label for="content" class="form-label">Job Description</label>
            <textarea class="form-control" id="content" name="content" rows="15" required>' . 
                htmlspecialchars($job_data['content']) . '</textarea>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="?i=' . $qik->encodeId($content_id) . '" class="btn btn-secondary ms-2">Cancel</a>
        </div>
    </form>';
} else {
    // Display job content with fix for the nl2br null issue
    echo '<div class="job-content">';
    if (!empty($job_data['content'])) {
        echo nl2br(htmlspecialchars($job_data['content']));
    } else {
        echo '<em>No job description available.</em>';
    }
    echo '</div>';

    if (!$success_message && !empty($current_user_data['user_id'])) {
        echo '
        <form method="post" enctype="multipart/form-data" class="application-form">
        '.$display->inputcsrf_token().'
            <div class="mb-3">
                <label for="resume" class="form-label">Resume (PDF or Word, max 10MB)</label>
                <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
            </div>
            <div class="mb-3">
                <label for="cover_letter" class="form-label">Cover Letter (Optional)</label>
                <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Application</button>
        </form>';
    } elseif (!$success_message) {
        echo '<hr>
        <div class="login-prompt">
            <h4>Interested in this position?</h4>
            <p>You must have an active account and be logged in to apply for this position.</p>
            <a href="/login" class="btn btn-primary">Log In to Apply</a>
        </div>';
    }
}

echo '</div></div></div>';

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();