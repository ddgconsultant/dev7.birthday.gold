<?php

if (empty($linkprefix)) $linkprefix='';


if (!empty($wizardmode) && !empty($wizard)) {
    // Wizard Menu
    $output='<style>
    .step-container {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    
    .round-tab {
      border-radius: 50%; /* Makes the tabs round */
      width: 50px; /* Adjust size as needed */
      height: 50px; /* Adjust size as needed */
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 10px; /* Space between the circle and the text */
    }
    
    .connecting-line {
      flex-grow: 1;
      height: 2px;
      background: #ddd; /* Line color */
      margin: 0 20px; /* Adjust the spacing around the lines */
    }
    </style>

    <div class="container mt-3 mb-2">
    <div class="row">
    <div class="col-12 text-center">
    <h2>'.strtoupper($wizard['section']).'</h2>
    </div>
    </div>
    <div class="row">
    <div class="col-12">
    <div class="d-flex justify-content-between align-items-center">';


    $steps = [
        ['label' => 'Step 1', 'icon' => 'bi bi-pencil', 'title' => 'Details', 'link' => 'profile'],
        ['label' => 'Step 2', 'icon' => 'bi bi-calendar', 'title' => 'Schedule', 'link' => 'profile'],
        ['label' => 'Step 3', 'icon' => 'bi bi-cart', 'title' => 'Businesses', 'link' => 'select'],
    ];

    foreach ($steps as $key => $step) {
        $stepNumber = $key + 1;
        $stepColor = ($stepNumber == $wizard['step']) ? 'success' : 'secondary';
        $stepTitleA = $stepTitleB =  ($stepNumber == $wizard['step']) ? 'fw-bold' : '';
        $stepLinkStart = ($stepNumber < $wizard['step']) ? '<a href="/myaccount/'.$step['link'].'?review">' : '';
        $stepLinkEnd = ($stepNumber < $wizard['step']) ? '</a>' : '';

if ($stepNumber < $wizard['step'])  {$step['label'] .=': DONE';      $stepTitleA = 'fw-bold';}

        $output .= '<div class="step-container">';
        $output .= '<div class="step-label ' . $stepTitleA . '">' . $step['label'] . '</div>';
        $output .= $stepLinkStart.'<div class="round-tab bg-' . $stepColor . ' text-white">';
        $output .= '<i class="' . $step['icon'] . '"></i>';
        $output .= '</div>'. $stepLinkEnd;
        $output .= '<div class="step-title ' . $stepTitleB . ' ">' . $step['title'] . '</div>';
        $output .= '</div>';
        
        if ($key < count($steps) - 1) {
            $output .= '<div class="connecting-line"></div>';
        }
    


    }
    $output .= '</div>
            </div>
        </div>
    </div>
</div>';
    echo $output;
    return;
}



# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class MenuBuilder {
    private $categories;
    private $currentPage;
    private $isAdmin;
    private $accountPlan;
    private $wizard;



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct($currentPage, $isAdmin, $accountPlan, $wizard = null) {
        $this->currentPage = $currentPage;
        $this->isAdmin = $isAdmin;
        $this->accountPlan = $accountPlan;
        $this->wizard = $wizard;

        $this->categories = [
            'Account' => ['myaccount'],
            'Freebies' => ['myaccount-select'], 
            'Enrollment' => ['myaccount-enrollment', 'myaccount-profile', 'myaccount-enrollment-schedule', 'myaccount-enrollmenthistory', 'myaccount-select'], 
            'Celebrate' => ['myaccount-celebrate', 'myaccount-goldmine'],
            'Settings' => ['myaccount-account'],
            'Admin' => ['backoffice'=>'//backoffice.birthday.gold', 'myaccount-admin-charts', 'myaccount-admin-stats', 'myaccount-admin-users', 'myaccount-admin-companies', 'myaccount-admin-locations']
        ];

        if ($this->accountPlan === 'free') {
            unset($this->categories['Celebrate']);
            unset($this->categories['Enrollment']);
        } else {
            unset($this->categories['Freebies']); 
        }

        if (!$this->isAdmin) {
            unset($this->categories['Admin']);
        }

global $account , $current_user_data;

    $response = $account->getUserAttribute($current_user_data['user_id'], 'minor_allow_account');
    if (!empty( $response['description']) &&  $response['description']=='1')    unset($this->categories['Account']);
    }



   # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function buildMenu() {
        global $linkprefix;
        $output = '<div class="card border h-100 border-primary mt-5">
        <div class="card-body m-0 py-0">
        <nav class="navbar navbar-expand-lg ">';
        $output .= '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">';
        $output .= '<span class="navbar-toggler-icon"></span>';
        $output .= '</button>';
        $output .= '<div class="collapse navbar-collapse" id="navbarNavDropdown">';
        $output .= '<ul class="navbar-nav">';

        foreach ($this->categories as $category => $pages) {
            if ($category === 'My Home') {
                $isActive = ($this->currentPage === 'myaccount');
                $activeClass = $isActive ? 'underline text-primary fw-bold' : '';
                $output .= '<li class="nav-item pe-4"><a  class="btn btn-secondary nav-link '.$activeClass.'" href="'.$linkprefix.'/myaccount/"><i class="bi bi-house-door-fill"></i> My Home</a></li>';
            } elseif ($category === 'Admin' && $this->isAdmin) {
                $output .= $this->buildAdminDropdown($pages);
            } else {
                $output .= $this->buildMenuItem($category, $pages);
            }
        }

        $output .= '</ul></div></nav></div></div>';
        return $output;
    }



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function buildMenuItem($category, $pages) {
        $isActive = in_array($this->currentPage, $pages);
        $activeClass = $isActive ? 'underline  fw-bold' : '';
        $search=array('myaccount-admin-', 'myaccount-');
$replace=array('admin/', 'myaccount/');
global $linkprefix;
$pages[0]=str_replace($search, $replace, $pages[0]);
        $url = $linkprefix.'/' . $pages[0];
        return '<li class="nav-item pe-4"><a class="btn btn-secondary nav-link '.$activeClass.'" href="'.$url.'">'.$category.'</a></li>';
    }



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function buildAdminDropdown($pages) {
        global $linkprefix;
        $isActive = in_array($this->currentPage, $pages);
        $activeClass = $isActive ? 'underline  fw-bold' : '';
        $dropdownItems = '';
$search=array('myaccount-admin-', 'myaccount-');
$replace=array('admin/', 'myaccount/');

        foreach ($pages as $key => $page) {

            $page = str_replace($search, $replace, $page);
            if (is_numeric($key)) {  // It's a string
                $dropdownItems .= '<a class="dropdown-item" href="'. $linkprefix.'/' . $page . '">' . basename($page, "myaccount-admin-") . '</a>';
            } else {  // It's an associative array
                $dropdownItems .= '<a class="dropdown-item" href="' .  $linkprefix.$page . '">' . $key . '</a>';
            }
            
        }
    

        return "
         <style>
        .dropdown-menu {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3); /* Adjust the values to change the shadow appearance */
        }
    </style>
    <li class='nav-item dropdown'>
                    <a class='btn btn-secondary nav-link dropdown-toggle $activeClass' href='#' data-bs-toggle='dropdown'>Admin</a>
                    <div class='dropdown-menu'>$dropdownItems</div>
                </li>";
    }


    
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function buildWizardMenu() {
        // Your wizard menu logic here
        $steps = $this->wizard['steps'];
        $currentStep = $this->wizard['currentStep'];
        $output = '<div class="wizard-menu">';

        foreach ($steps as $step) {
            $isActive = ($step['id'] === $currentStep);
            $activeClass = $isActive ? 'active-step' : '';
            $output .= "<div class='wizard-step $activeClass'>{$step['label']}</div>";
        }

        $output .= '</div>';
        return $output;
    }
}

// Usage
$wizard = null;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdmin = $account->isadmin();  // Assuming $account->isadmin() returns a boolean
$accountPlan = $current_user_data['account_plan']??'';  // Assuming this is how you get the account plan
/* 

if (!empty($wizardmode) && !empty($wizard)) {
    $wizard = [
        'steps' => [
            ['id' => 1, 'label' => 'Step 1'],
            ['id' => 2, 'label' => 'Step 2'],
            // Add more steps as needed
        ],
        'currentStep' => $wizard['step']  // Assuming this is how you get the current step
    ];
}
 */
$menuBuilder = new MenuBuilder($currentPage, $isAdmin, $accountPlan, $wizard);
echo $menuBuilder->buildMenu();
