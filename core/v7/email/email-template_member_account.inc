<?PHP
global $website;
$output=trim('<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <style rel="stylesheet" media="all">
        /* Media Queries */
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
        
        /* Account notification specific styles */
        .security-alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .action-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px 5px;
        }
        
        .action-button.secondary {
            background-color: #6c757d;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100%; background-color: #F2F4F6;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 100%; margin: 0; padding: 0; background-color: #F2F4F6;" align="center">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <!-- Logo -->
                    <tr>
                        <td style="padding: 25px 0; text-align: center;">
                            <a style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 24px; font-weight: bold; color: #2F3133; text-decoration: none; text-shadow: 0 1px 0 white;" href="'.$website['fullurl'].'" target="_blank">
                                <img src="https://birthday.gold/public/images/logo/bg-logo-small.png" alt="Birthday.Gold" style="height: 40px; width: auto; vertical-align: middle; margin-right: 10px;">
                                Birthday.Gold
                            </a>
                        </td>
                    </tr>

        <!-- Email Body -->
        <tr>
            <td style="width: 100%; margin: 0; padding: 0; border-top: 1px solid #EDEFF2; border-bottom: 1px solid #EDEFF2; background-color: #FFF;" width="100%">
                <table style="width: auto; max-width: 570px; margin: 0 auto; padding: 0;" align="center" width="570" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; padding: 35px;">
                            
                            <!-- Account Security Badge -->
                            <div style="text-align: center; margin-bottom: 20px;">
                                <img src="https://birthday.gold/public/images/icons/shield-check.png" alt="Security" style="width: 48px; height: 48px;">
                                <h2 style="color: #333; margin: 10px 0;">Account Security Notification</h2>
                            </div>

{{MESSAGE_CONTENT}}

                          </td>
                    </tr>
                </table>
            </td>
        </tr>

          <!-- Member Account Footer -->
            <tr>
                <td>
                    <table style="width: auto; max-width: 570px; margin: 0 auto; padding: 0; text-align: center;" align="center" width="570" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; color: #74787E; padding: 35px; text-align: center;">
                                
                                <!-- Quick Actions -->
                                <div style="margin-bottom: 25px; padding: 20px; background-color: #f8f9fa; border-radius: 6px;">
                                    <p style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #333;">Quick Actions:</p>
                                    <a href="https://birthday.gold/myaccount/" style="display: inline-block; padding: 8px 16px; margin: 5px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 20px; font-size: 13px;">My Account</a>
                                    <a href="https://birthday.gold/myaccount/notifications" style="display: inline-block; padding: 8px 16px; margin: 5px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 20px; font-size: 13px;">Manage Notifications</a>
                                    <a href="https://birthday.gold/myaccount/security-settings" style="display: inline-block; padding: 8px 16px; margin: 5px; background-color: #dc3545; color: #ffffff; text-decoration: none; border-radius: 20px; font-size: 13px;">Security Settings</a>
                                </div>
                                
                                <!-- Footer Text -->
                                <p style="margin-top: 0; color: #74787E; font-size: 12px; line-height: 1.5em;">
                                    You\'re receiving this notification because of recent activity on your Birthday.Gold account.
                                </p>
                                
                                <!-- Notification Tracking Pixel -->
                                {{TRACKING_PIXEL}}
                                
                                <p style="margin: 20px 0 10px 0; color: #74787E; font-size: 11px;">
                                    <a href="https://birthday.gold/myaccount/notifications" style="color: #007bff;">Manage Notification Preferences</a> | 
                                    <a href="https://birthday.gold/help" style="color: #007bff;">Get Help</a>
                                </p>
                                
                                <hr style="border: none; border-top: 1px solid #e9ecef; margin: 20px 0;">
                                
                                <p style="margin: 10px 0; color: #999; font-size: 11px;">
                                    &copy; '.date('Y').' Birthday.Gold. All rights reserved.<br>
                                    Parker, Colorado 80134 - USA
                                </p>
                                
                                <p style="margin: 10px 0; color: #999; font-size: 10px;">
                                    This is an automated security notification. Please do not reply to this email.<br>
                                    For support, contact us at <a href="mailto:support@birthday.gold" style="color: #007bff;">support@birthday.gold</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>
</table>
</body>
</html>
');