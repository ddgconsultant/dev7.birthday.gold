<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
if (!$account->isadmin()) {
    session_tracking('admin_access_failure');
    $system->redirectUser('admin_access_failure');
    exit;
  }

$id = $_GET['id'];
if ($id) {
    $sql="DELETE FROM bg_slides WHERE id = :id";
   
	   $stmt = $database->prepare($sql);
    $stmt->execute(['id' => $id]);
		
}

header("Location: admin.php");
exit();
