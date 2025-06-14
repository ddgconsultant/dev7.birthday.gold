<?PHP
if (!isset($headerattribute['additionalcss'])) $headerattribute['additionalcss'] ='';
if (!isset($columnsize)) $columnsize=50;

$additionalstyles.=' 
<!-- Google Font -->
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;600&display=swap" rel="stylesheet">

<!-- DataGrid library CSS -->
<link href="/public/css/myaccount-datagrid.min.css" rel="stylesheet" />
<!-- Demo page CSS -->
<link href="/public/css/myaccount-select.css" rel="stylesheet" />

<style>
.img-responsive {
max-width: 100% !important;
height: auto !important;
}

.col-lg-2-4 {
flex: 0 0 ' . $columnsize . '%;
max-width: ' . $columnsize . '%;
}

.business-card {
transition: none !important;
animation: none !important;
}

.selected-card {
border: 2px solid #f39c12;
}

.btn-disabled {
opacity: 0.5;
cursor: not-allowed;
}

span.btn-sm {
cursor: pointer;
}
</style>


<style>
/* HAVE button - normal state (green border with white background) */
.have-label {
margin-left: 7px;
}
.have-button {
    background-color: white;
    color: #28a745; /* Green text */
    border: 1px solid #0056b3; /* Green border */
}

/* HAVE button - hover state (light blue) */
.have-button:hover {
    background-color: #daedfc; /* Light blue */
    border-color: #0056b3;
    color: white;
}

/* HAVE button - selected state (green border with white background) */
.have-button.btn-success {
    background-color: white;
    border: 2px solid #28a745; /* Green */
    color: #28a745; /* Green text */
}

/* HAVE button - disabled state (light gray border and text) */
.have-button.btn-disabled {
    background-color: white; /* Keep white background */
    border-color: #d3d3d3; /* Light gray border */
    color: #d3d3d3; /* Light gray text */
    cursor: not-allowed; /* Show disabled cursor */
}

/* ENROLL button - normal state (dark blue) */
.enroll-button {
    background-color: #0056b3; /* Dark blue */
    color: white;
    border-color: #0056b3;
}

/* ENROLL button - hover state (light blue) */
.enroll-label {
margin-left: 7px;
}
.enroll-button:hover {
    background-color: #007bff; /* Light blue */
    border-color: #007bff;
    color: white;
}

/* ENROLL button - Remove state (gold) */
.enroll-button.btn-warning {
    background-color: #ffc107; /* Gold */
    color: white;
}

/* Disable focus outline and active state to prevent color change */
.enroll-button:focus, .enroll-button:active {
    background-color: #0056b3; /* Keep dark blue */
    outline: none; /* Remove focus outline */
}

/* Disabled state for any button */
.btn-disabled {
    background-color: white; /* Keep background white */
    border-color: #d3d3d3; /* Light gray border */
    color: #d3d3d3; /* Light gray text */
    cursor: not-allowed;
}

</style>

';