<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$p_displaylength = 30;
$p_sessionid = 0;

if (isset($_REQUEST['id'])) {
  $p_sessionid = $_REQUEST['id'];

}



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->
<div class="container main-content">


  <?PHP

  $sql = "SELECT 	
bg_sessiontracking.id, 
bg_sessiontracking.create_dt, 
IFNULL(bg_sessiontracking.ip, '') AS ip, 
bg_sessiontracking.user_id, 
IFNULL(bg_sessiontracking.username, '') AS username, 
IFNULL(bg_sessiontracking.`name`, '') AS `name`, 
IFNULL(bg_sessiontracking.sessionid, '') AS sessionid, 
IFNULL(bg_sessiontracking.page, '') AS page, 
IFNULL(bg_sessiontracking.site, '') AS site, 
IFNULL(bg_sessiontracking.request_data, '-none-') AS request_data, 
IFNULL(bg_sessiontracking.tracking_data, '-none-') AS tracking_data, 
IFNULL(bg_sessiontracking.session_data, '-none-') AS session_data, 
IFNULL(bg_sessiontracking.server_data, '-none-') AS server_data 
FROM   bg_sessiontracking WHERE   id = " . $p_sessionid . "";

  // Prepare the statement
  $stmt = $database->prepare($sql);
  $stmt->execute();

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $row = $result[0];
  

  $additionalstyles .= '
<style>
.dark-tab {
background-color: #f8f9fa; /* Darker shade based on #f8f9fa */
color: #495057; /* Darker text color for contrast */
}

.dark-tab:hover, .dark-tab:focus {
background-color: #ced4da; /* Even darker for hover/focus state */
color: #343a40; /* Optional: even darker text color for better contrast on hover/focus */
}
</style>
';


  echo '
<div class="d-flex justify-content-end mb-3">
<a href="' . $_SERVER['HTTP_REFERER'] . '" class="btn btn-primary">Return to User Details</a>
</div>
<div class="card">
<div class="card-body">
<div class="row">
<div class="col-lg-12 mb-4 mb-lg-0">
<h2>Session Elements</h2>
<table class="table fs-10 mt-3">
<tbody>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">id:</td>
<td>' . $row['id'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">create_dt:</td>
<td>' . $row['create_dt'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">ip:</td>
<td>' . $row['ip'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">user_id:</td>
<td>' . $row['user_id'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">username:</td>
<td>' . $row['username'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">name:</td>
<td>' . $row['name'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">sessionid:</td>
<td>' . $row['sessionid'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">page:</td>
<td>' . $row['page'] . '</td>
</tr>
<tr>
<td class="bg-100 fw-bold h5" style="width: 30%;">site:</td>
<td>' . $row['site'] . '</td>
</tr>
</tbody>
</table>
';


  echo '
</div>
</div>
<div class="row">
<div class="col-12">
<div class="mt-4">
<h2>Data Elements</h2>
<ul class="nav nav-tabs" id="myTab" role="tablist">
<li class="nav-item"><a class="nav-link px-2 px-md-3 active" id="request-tab" data-bs-toggle="tab" href="#tab-request" role="tab" aria-controls="tab-request" aria-selected="true"><h4>Request</h4></a></li>
<li class="nav-item"><a class="nav-link px-2 px-md-3" id="specifications-tab" data-bs-toggle="tab" href="#tab-specifications" role="tab" aria-controls="tab-specifications" aria-selected="false"><h4>Tracking</h4></a></li>
<li class="nav-item"><a class="nav-link px-2 px-md-3" id="reviews-tab" data-bs-toggle="tab" href="#tab-reviews" role="tab" aria-controls="tab-reviews" aria-selected="false"><h4>Session</h4></a></li>
<li class="nav-item"><a class="nav-link px-2 px-md-3" id="server-tab" data-bs-toggle="tab" href="#tab-server" role="tab" aria-controls="tab-server" aria-selected="false"><h4>Server</h4></a></li>

<li class="nav-item"><a class="nav-link px-2 px-md-3 dark-tab" id="reviewsf-tab" data-bs-toggle="tab" href="#tab-reviewsf" role="tab" aria-controls="tab-reviewsf" aria-selected="false"><h4>Session Formatted</h4></a></li>
<li class="nav-item"><a class="nav-link px-2 px-md-3 dark-tab" id="serverf-tab" data-bs-toggle="tab" href="#tab-serverf" role="tab" aria-controls="tab-serverf" aria-selected="false"><h4>Server Formatted</h4></a></li>
</ul>
';

  echo '
<div class="tab-content fs-4" id="myTabContent">
<div class="tab-pane fade show active" id="tab-request" role="tabpanel" aria-labelledby="request-tab">
<div class="mt-3 px-2">
<code>' . $row['request_data'] . '</code>
</div>
</div>
<div class="tab-pane fade" id="tab-specifications" role="tabpanel" aria-labelledby="specifications-tab">
<div class="mt-3 px-2">
<code>' . $row['tracking_data'] . '</code>
</div>
</div>
<div class="tab-pane fade" id="tab-reviews" role="tabpanel" aria-labelledby="reviews-tab">
<div class=" mt-3 px-2">
<code>' . $row['session_data'] . '</code>
</div>
</div>
<div class="tab-pane fade" id="tab-server" role="tabpanel" aria-labelledby="server-tab">
<div class="mt-3 px-2">
<code>' . $row['server_data'] . '</code>
</div>
</div>

<div class="tab-pane fade" id="tab-reviewsf" role="tabpanel" aria-labelledby="reviewsf-tab">
<div class=" mt-3 px-2">
<pre>' . $row['session_data'] . '</pre>
</div>
</div>
<div class="tab-pane fade" id="tab-serverf" role="tabpanel" aria-labelledby="serverf-tab">
<div class=" mt-3 px-2">
<pre>' . $row['server_data'] . '</pre>
</div>
</div>
</div>
';
  ?>

</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<?PHP
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
