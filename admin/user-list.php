<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$p_displaylength = 180;
$searchTerm = '';

#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if (isset($_POST['formtype']) && ($_POST['formtype'] == 'changedisplaylength')) {
        $p_displaylength = $_POST['displaylength'];
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
switch ($p_displaylength) {
    case 'all':
        $userlimitsql = '';
        break;
    default:
        $userlimitsql = " and u.create_dt >= CURDATE() - INTERVAL $p_displaylength DAY";
        break;
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_admin_leftpanel.inc');


?>

<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->

<section class="mt-0 pt-0 main-content container">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">User List</h2>
  <a href="/admin/" class="btn btn-sm btn-outline-secondary">Back to Admin</a>
</div>

    <div class="card mb-3 border-0 px-0 mx-0">
        <div class="card-body px-0 mx-0">
            <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-5" id="all-users-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button" role="tab" aria-controls="all-users" aria-selected="true">Real Users</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-5" id="test-users-tab" data-bs-toggle="tab" data-bs-target="#test-users" type="button" role="tab" aria-controls="test-users" aria-selected="false">Test Users</button>
                </li>
            </ul>


            <div class="tab-content" id="userTabsContent">
                <div class="tab-pane fade show active" id="all-users" role="tabpanel" aria-labelledby="all-users-tab">
                    <?php include('user_components/user-list_allusers.inc'); ?>
                </div>


                <div class="tab-pane fade" id="test-users" role="tabpanel" aria-labelledby="test-users-tab">
                    <?php include('user_components/user-list_testusers.inc'); ?>
                </div>


            </div>
        </div>
    </div>
</section>

</div>
</div>
</div>
<script>
    document.getElementById('displayLengthSelect').addEventListener('change', function() {
        document.getElementById('displayLengthForm').submit();
    });
</script>

<?php
echo "
<script>
$(document).ready(function() {
    $('#searchBar').on('input', function() {
        if ($(this).val().length > 0) {
            $('.clear-icon').show();
        } else {
            $('.clear-icon').hide();
        }
    });

    $('.clear-icon').click(function() {
        $('#searchBar').val('').focus();
        $(this).hide();
        $('#searchBar').trigger('keyup');
    });

    $('#searchBar').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.bglist-group-item').each(function() {
            var itemText = $(this).data('full-context').toLowerCase();
            if (itemText.includes(value)) {
                $(this).css('display', '');
            } else {
                $(this).attr('style', 'display: none !important;');
            }
        });
    });
});

var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
  return new bootstrap.Dropdown(dropdownToggleEl, {
    boundary: 'viewport' // Ensures the dropdown isn't clipped by any parent containers
  })
})
</script>
";

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
