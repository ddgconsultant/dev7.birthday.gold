<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
if (!$account->isadmin()) {
  session_tracking('admin_access_failure');
  $system->redirectUser('admin_access_failure');
  exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $presentation = $_POST['presentation'] ?? '';
    $slide_order = $_POST['slide_order'] ?? '';
    $section_class = $_POST['section_class'] ?? '';
    $section_tag = $_POST['section_tag'] ?? '';
    $content = $_POST['content'] ?? '';
    $speech_script = $_POST['speech_script'] ?? '';
    $createby = $_POST['createby'] ?? '';
    $modifyby = $_POST['modifyby'] ?? '';
    $status = $_POST['status'] ?? '';
	
    if ($id) {
        // Update existing slide
        $sql = "UPDATE bg_slides SET presentation = ?, slide_order = ?, section_class = ?, section_tag = ?,  content = ?, speech_script = ?,  modifyby = ?, status = ? WHERE id = ?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$presentation, $slide_order, $section_class,  $section_tag,  $content, $speech_script, $modifyby, $status, $id]);
    } else {
        // Insert new slide
        $sql = "INSERT INTO bg_slides (presentation, slide_order, section_class , section_tag,  content, speech_script, createby, modifyby, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $database->prepare($sql);
        $stmt->execute([$presentation, $slide_order,  $section_class, $section_tag,  $content, $speech_script,  $createby, $modifyby, $status]);
    }
}

// Fetch slides
$sql = "SELECT * FROM bg_slides ORDER BY presentation, slide_order";
$stmt = $database->query($sql);
$slides = $stmt->fetchAll();
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Manage Slides</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
   
  </head>
  <body>
    <div class="container">
      <h1 class="mt-4">Admin Panel - Manage Slides</h1>
      <form method="post" action="admin.php" class="mb-4">
        <input type="hidden" name="id" id="slide-id">
        <div class="form-group">
          <label for="presentation">Presentation</label>
          <input type="text" class="form-control" id="presentation" name="presentation" required>
        </div>
        <div class="form-group">
          <label for="slide_order">Slide Order</label>
          <input type="number" class="form-control" id="slide_order" name="slide_order" required>
        </div>
        <div class="form-group">
          <label for="section_tag">Section Tag</label>
          <input type="text" class="form-control" id="section_tag" name="section_tag" >
        </div>
      
        <div class="form-group">
          <label for="content">Content</label>
          <textarea class="form-control" id="content" name="content" rows="5" ></textarea>
        </div>
        <div class="form-group">
          <label for="speech_script">Speech Script</label>
          <textarea class="form-control" id="speech_script" name="speech_script" rows="5" ></textarea>
        </div>
      
        <div class="form-group">
          <label for="createby">Created By</label>
          <input type="text" class="form-control" id="createby" name="createby" >
        </div>
        <div class="form-group">
          <label for="modifyby">Modified By</label>
          <input type="text" class="form-control" id="modifyby" name="modifyby" >
        </div>
        <div class="form-group">
          <label for="status">Status</label>
          <select class="form-control" id="status" name="status" >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Slide</button>
      </form>

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>Presentation</th>
            <th>Slide Order</th>
            <th>Section Tag</th>
             <th >Content</th>
            <th >Speech Script</th>
            <th>Created  Modified</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
     <?php 
$slideHTML = '';
foreach ($slides as $slide) {
    $id = $slide['id'] ?? '1';
    $presentation = htmlspecialchars($slide['presentation'] ?? '');
    $grouping = $slide['grouping'] ?? '';  
    $slide_order = htmlspecialchars($slide['slide_order'] ?? '');
    $section_tag = htmlspecialchars($slide['section_tag'] ?? '');
    $content = htmlspecialchars($slide['content'] ?? '');
    $speech_script = $slide['speech_script'] ?? '';
    $createby = htmlspecialchars($slide['createby'] ?? '');
    $modifyby = $slide['modifyby'] ?? '';
    $status = htmlspecialchars($slide['status'] ?? '');
    $editButton = '<button class="btn btn-sm btn-warning" onclick="editSlide('.htmlspecialchars(json_encode($slide)).')">Edit</button>';
    $deleteButton = '<button class="btn btn-sm btn-danger" onclick="deleteSlide('.$id.')">Delete</button>';
    $viewButton = '<a class="btn btn-sm btn-primary" href="'.$website['fullurl'].'/presentation?content='.$grouping.'#slide='.$id.'">View</a>';

    $slideHTML .= '<tr>
        <td>'.$id.'</td>
        <td>'.$presentation.'</td>
        <td>'.$slide_order.'</td>
        <td>'.$section_tag.'</td>
        <td >'.$content.'</td>
        <td >'.$speech_script.'</td>
         <td>'.$createby.' '.$modifyby.'</td>
        <td>'.$status.'</td>
        <td>'.$editButton.' '.$viewButton.' '.$deleteButton.'</td>
    </tr>';
}
echo $slideHTML;
?>

        </tbody>
      </table>
    </div>

    <script>
      function editSlide(slide) {
        document.getElementById('slide-id').value = slide.id;
        document.getElementById('presentation').value = slide.presentation;
        document.getElementById('slide_order').value = slide.slide_order;
        document.getElementById('section_tag').value = slide.section_tag;
        document.getElementById('content').value = slide.content;
        document.getElementById('speech_script').value = slide.speech_script;
         document.getElementById('createby').value = slide.createby;
        document.getElementById('modifyby').value = slide.modifyby;
        document.getElementById('status').value = slide.status;
      }

      function deleteSlide(id) {
        if (confirm('Are you sure you want to delete this slide?')) {
          window.location = `delete_slide.php?id=${id}`;
        }
      }
    </script>
  </body>
</html>
