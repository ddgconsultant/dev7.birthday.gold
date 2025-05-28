<?PHP


$additionalstyles .= '
<style>
.business-list {
    height: calc(100vh - 280px);
    overflow-y: auto;
}
.business-item {
    cursor: pointer;
    transition: background-color 0.2s;
}
.business-item:hover {
    background-color: #f8f9fa;
}
.business-item.active {
    background-color: #e9ecef;
}
.enrollment-actions {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 15px;
    border-top: 1px solid #dee2e6;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}
</style>';

$additionalstyles.= '
<style>

/* Style for regular accordion buttons */
.accordion-button.enrollementlist {
    padding: 0.75rem 1rem;
    background-color: #e7f1ff !important;
    color: #0d6efd !important;
}


</style>




   <style>
            .enrollment-hints ul {
                margin: 0;
                padding: 0;
            }
            .enrollment-hints li {
                background: #f8f9fa;
                border-left: 4px solid #0d6efd;
                margin-bottom: 1rem;
                padding: 1rem;
                border-radius: 0 4px 4px 0;
                transition: background-color 0.2s;
            }
            .enrollment-hints li:hover {
                background: #e9ecef;
            }
            </style>
            ';
