<?php
/**
 * Section Open - Opens a new card section
 * 
 * This file opens a new Bootstrap card section for form grouping.
 * Can be configured with custom title and info modal.
 * 
 * Expected variables:
 * - $section_title: The title for the section (required)
 * - $section_info_modal: ID of the info modal (optional)
 * - $section_icon: Bootstrap icon class (optional, defaults to bi-info-circle)
 * - $process_handlers: Boolean flag indicating if handlers should process
 */

// Exit if only processing handlers
if (isset($process_handlers) && $process_handlers) {
    return;
}

// Set defaults
$section_icon = $section_icon ?? 'bi-info-circle';
?>
<!-- <?php echo htmlspecialchars($section_title); ?> Section -->
<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo htmlspecialchars($section_title); ?></h5>
        <?php if (!empty($section_info_modal)): ?>
        <i class="<?php echo htmlspecialchars($section_icon); ?> text-muted" 
           style="font-size: 1rem; cursor: pointer;" 
           data-bs-toggle="modal" 
           data-bs-target="#<?php echo htmlspecialchars($section_info_modal); ?>"
           title="Click for more information"></i>
        <?php endif; ?>
    </div>
    <div class="card-body">