<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';
$pagetitle = "Notifications";
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');


$additionalstyles .= '
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        /* Clean modern tab navigation - Mobile-first design */
        .nav-tabs-clean {
            display: flex !important;
            border: none !important;
            border-bottom: 1px solid #e0e0e0 !important;
            margin-bottom: 0 !important;
            padding: 0 !important;
            list-style: none !important;
            background: white !important;
            justify-content: space-around !important;
        }
        
        .nav-tabs-clean .nav-item {
            flex: 1 !important;
            text-align: center !important;
            position: relative !important;
            border: none !important;
            margin: 0 !important;
        }
        
        .nav-tabs-clean .nav-link {
            display: block !important;
            padding: 12px 16px !important;
            text-decoration: none !important;
            color: #666 !important;
            font-weight: 400 !important;
            font-size: 15px !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            transition: all 0.2s ease !important;
            border: none !important;
            background: none !important;
            border-radius: 0 !important;
            position: relative !important;
        }
        
        .nav-tabs-clean .nav-link:hover {
            color: #000 !important;
            border: none !important;
            background: rgba(0,0,0,0.05) !important;
        }
        
        .nav-tabs-clean .nav-link.active {
            color: #000 !important;
            font-weight: 500 !important;
            background: none !important;
            border: none !important;
            border-color: transparent !important;
        }
        
        /* The underline indicator - positioned under the link */
        .nav-tabs-clean .nav-link.active::after {
            content: "" !important;
            position: absolute !important;
            bottom: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 3px !important;
            background-color: #007bff !important;
        }
        
        /* Remove focus outlines */
        .nav-tabs-clean .nav-link:focus {
            box-shadow: none !important;
            outline: none !important;
        }
        
        /* Tab badge styling - positioned to the right */
        .tab-badge {
            display: inline-block !important;
            min-width: 18px !important;
            padding: 2px 5px !important;
            margin-left: 8px !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            line-height: 1 !important;
            color: #fff !important;
            text-align: center !important;
            white-space: nowrap !important;
            vertical-align: middle !important;
            background-color: #007bff !important;
            border-radius: 10px !important;
            position: relative !important;
            top: -1px !important;
        }
        
        /* Desktop styles - left aligned with spacing */
        @media (min-width: 768px) {
            .nav-tabs-clean {
                justify-content: flex-start !important;
                padding-left: 20px !important;
            }
            
            .nav-tabs-clean .nav-item {
                flex: none !important;
                text-align: left !important;
                margin-right: 32px !important;
            }
            
            .nav-tabs-clean .nav-item:last-child {
                margin-right: 0 !important;
                margin-left: auto !important;
                margin-right: 20px !important;
            }
            
            .nav-tabs-clean .nav-link {
                padding: 16px 0 !important;
                font-size: 16px !important;
            }
            
            .nav-tabs-clean .nav-link:hover {
                background: none !important;
                color: #007bff !important;
            }
            
            /* Wider underline on desktop */
            .nav-tabs-clean .nav-link.active::after {
                height: 3px !important;
                left: -8px !important;
                width: calc(100% + 16px) !important;
            }
        }
        
        .notification-item {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: start;
            gap: 12px;
            transition: background-color 0.2s;
            background: white;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 15px;
        }
        
        .notification-text {
            color: #666;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
        
        .notification-time {
            color: #999;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .tab-content {
            background: #f8f9fa;
            min-height: 400px;
        }
        
        .tab-pane {
            background: white;
        }
        
        /* Additional mobile adjustments */
        @media (max-width: 576px) {
            .nav-tabs-clean .nav-link {
                font-size: 14px !important;
                padding: 10px 8px !important;
            }
        }
    </style>
';

$additionalstyles .= '
<style>
.nav-tabs-clean {
    border-bottom: 1px solid #e0e0e0;
}

.nav-tabs-clean .nav-link {
    border: none !important;
    background: none !important;
    color: #9e9e9e !important;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 16px; /* Larger label */
    padding: 10px 0 !important;
    margin-right: 24px;
    position: relative;
    border-radius: 0 !important; /* Remove rounded top corners */
}

.nav-tabs-clean .nav-link:hover {
    color: #000 !important;
}

.nav-tabs-clean .nav-link::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    width: 0;
    background-color: #3f51b5;
    transition: width 0.2s ease;
}

.nav-tabs-clean .nav-link:hover::after {
    width: 100%; /* Wider underline on hover */
}

.nav-tabs-clean .nav-link.active {
    color: #000 !important;
    font-weight: 700 !important;
}

.nav-tabs-clean .nav-link.active::after {
    width: 100%;
    height: 2px;
    background-color: #3f51b5;
}
</style>
';
$additionalstyles .= '
<style>
/* Make the label larger */
.nav-tabs-clean .nav-link {
    font-size: 17px !important;
    padding: 12px 24px !important; /* wider tabs */
}

/* Keep the badge inline with label */
.nav-tabs-clean .tab-badge {
    display: inline-block !important;
    vertical-align: middle !important;
    margin-left: 6px !important;
    margin-top: 0 !important;
    position: static !important;
}

/* If you want the tabs to be even wider on desktop */
@media (min-width: 768px) {
    .nav-tabs-clean .nav-link {
        padding: 16px 32px !important;
        font-size: 18px !important;
    }
}
</style>
';
$additionalstyles .= '
<style>
/* 1. Remove Bootstrap active tab background and border */
.nav-tabs-clean .nav-link.active,
.nav-tabs-clean .nav-link.active:focus,
.nav-tabs-clean .nav-link.active:hover {
    background-color: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

/* 2. Remove all default tab borders */
.nav-tabs-clean .nav-link,
.nav-tabs-clean .nav-link:hover,
.nav-tabs-clean .nav-link:focus {
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

/* 3. Keep only the clean underline for the active tab */
.nav-tabs-clean .nav-link.active::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: #007bff;
}

/* 4. Make the tab label font larger and add spacing */
.nav-tabs-clean .nav-link {
    font-size: 18px !important;
    padding: 14px 24px !important;
}

/* 5. Keep the badge inline to the right of the label */
.nav-tabs-clean .tab-badge {
    display: inline-block !important;
    vertical-align: middle !important;
    margin-left: 8px !important;
    margin-top: 0 !important;
    position: relative !important;
    top: auto !important;
}

/* 6. Remove hover background effect */
.nav-tabs-clean .nav-link:hover {
    background-color: transparent !important;
    color: #000 !important;
}

/* 7. Make tabs visually wider */
.nav-tabs-clean .nav-item {
    min-width: 120px !important;
}

/* 8. Remove any background highlight from active */
.nav-tabs-clean .nav-link.active {
    background: none !important;
}
</style>
';

$additionalstyles .= '
<style>
/* Remove the background and rounded corners more forcefully */
.nav-tabs-clean .nav-item .nav-link.active,
.nav-tabs-clean .nav-item .nav-link.active:focus,
.nav-tabs-clean .nav-item .nav-link.active:hover {
    background: none !important;
    background-color: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

/* Keep underline indicator clean and centered */
.nav-tabs-clean .nav-item .nav-link.active::after {
    content: "";
    display: block;
    width: 100%;
    height: 3px;
    background-color: #007bff;
    position: absolute;
    bottom: 0;
    left: 0;
}

/* Make label font larger */
.nav-tabs-clean .nav-link {
    font-size: 18px !important;
    padding: 14px 24px !important;
}

/* Badge to the right of label, aligned vertically */
.nav-tabs-clean .tab-badge {
    display: inline-block !important;
    vertical-align: middle !important;
    margin-left: 8px !important;
    margin-top: 0 !important;
}

/* Wider tabs */
.nav-tabs-clean .nav-item {
    min-width: 120px !important;
}

/* Remove hover background */
.nav-tabs-clean .nav-link:hover {
    background-color: transparent !important;
    color: #000 !important;
}
</style>
';

?>
<div class="container main-content my-5 pt-lg-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Notifications</h2>
        </div>
        
        <!-- Clean tab navigation -->
        <ul class="nav nav-tabs nav-tabs-clean">
            <li class="nav-item">
                <a class="nav-link active" href="#unread" data-bs-toggle="tab">
                    Unread
                    <span class="tab-badge">3</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#read" data-bs-toggle="tab">
                    Read
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#all" data-bs-toggle="tab">
                    All
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#settings" data-bs-toggle="tab">
                    <i class="bi bi-gear"></i>
                </a>
            </li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="unread">
                <?php 
                $_GET['notification_filter'] = 'unread';
                include($dir['core_components'] . '/user_notifications_display.inc'); 
                ?>
            </div>

            <div class="tab-pane fade" id="read">
                <?php 
                $_GET['notification_filter'] = 'read';
                include($dir['core_components'] . '/user_notifications_display.inc'); 
                ?>
            </div>
            
            <div class="tab-pane fade" id="all">
                <?php 
                $_GET['notification_filter'] = 'all';
                include($dir['core_components'] . '/user_notifications_display.inc'); 
                ?>
            </div>

            <div class="tab-pane fade" id="settings">
                <div class="p-4">
                    <?php include($dir['core_components'] . '/user_notification_settings.inc'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>