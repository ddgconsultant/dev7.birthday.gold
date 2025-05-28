<?PHP
  echo '<a href="#" class="btn button btn-danger" data-bs-toggle="modal" data-bs-target="#reEncryptModal">ReEncrypt All</a>';
  
  $stmt = $database->prepare("SELECT value FROM am_config WHERE name = 'path'");
  $stmt->execute();
  $path = $stmt->fetchColumn();


  // Modal HTML
  echo '
  <div class="modal fade" id="reEncryptModal" tabindex="-1" aria-labelledby="reEncryptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reEncryptModalLabel">Re-Encrypt Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="' . $_SERVER['PHP_SELF'] . '" id="recryptform" name="recryptform" method="POST">          
'.$display->inputcsrf_token().'
<input type="hidden" name="act" value="reEncyptAll">
          <div class="modal-body">
            <div class="mb-3">
              <label for="newPathInput" class="form-label">New Path</label>
              <input type="text" class="form-control" id="newPathInput" name="newpath" value="'.$path.'" placeholder="XXX_X/ZZZ_Z">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  ';