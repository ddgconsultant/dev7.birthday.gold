<?PHP

class admin {


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function admineditor($blockId) {
        // Get the current page (the one being displayed)
        $currentPage = $_SERVER['SCRIPT_FILENAME'];
        // Read the contents of the current page
        $fileContent = file_get_contents($currentPage);

        // Pattern to find the ADMIN PAGE EDITOR block with the given blockId
        $pattern = '/### ADMIN PAGE EDITOR: START-' . preg_quote($blockId) . ' ###(.*?)### ADMIN PAGE EDITOR: END-' . preg_quote($blockId) . ' ###/s';
      
        // Check if the block is found
        if (preg_match($pattern, $fileContent, $matches)) {
             $blockContent = $matches[1];  // The content within the block

            // Output the edit button and modal with the parsed content
            $this->outputEditorModal($blockId, $blockContent, $currentPage);
        }
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Function to output the edit button and modal
    private function outputEditorModal($blockId, $blockContent, $currentPage) {
        $escapedContent = htmlspecialchars($blockContent);  // Escape the content for HTML
        echo '<div style="position: relative; z-index: 999;" id="pageeditorbuttons">';
        // Output the edit button
        echo '<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal-' . $blockId . '">
                <i class="bi bi-pencil me-2"></i> Edit
              </button>';

              echo '<a href="/admin/manage-pageeditor?toggle_editor=0" class="btn btn-sm btn-outline-danger ms-2">
        <i class="bi bi-x-circle" data-bs-toggle="tooltip" title="Disable Editor"></i>
      </a>';

echo '</div>';

        // Output the modal for editing the content
        echo '
        <div class="modal fade" id="editModal-' . $blockId . '" tabindex="-1" aria-labelledby="editModalLabel-' . $blockId . '" aria-hidden="true">
          <div class="modal-dialog modal-lg" >
            <div class="modal-content"  style="min-height: 60vh;">
              <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel-' . $blockId . '">Edit Section: ' . $blockId . '</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="POST" action="/admin_actions/content-pageeditor">
                  <input type="hidden" name="page" value="' . $currentPage . '">
                  <input type="hidden" name="block" value="' . $blockId . '">
                  <textarea name="editedContent" class="form-control" rows="20"  style="min-height: 50vh;">' . $escapedContent . '</textarea>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>';
    }
}
