<?php
/**
 * Section Close - Closes a card section
 * 
 * This file closes a Bootstrap card section opened by section_open.inc
 * 
 * Expected variables:
 * - $process_handlers: Boolean flag indicating if handlers should process
 */

// Exit if only processing handlers
if (isset($process_handlers) && $process_handlers) {
    return;
}
?>
    </div><!-- End card-body -->
</div><!-- End card -->