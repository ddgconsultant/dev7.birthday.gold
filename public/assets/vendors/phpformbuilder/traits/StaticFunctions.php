<?php

declare(strict_types=1);

namespace phpformbuilder\traits;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use phpformbuilder\Validator\Validator;

trait StaticFunctions
{
    /**
     * build an Alert message according to the framework html
     *
     * @param  string $content_text
     * @param  string $framework     bs4|bs5|bulma|foundation|material|tailwind|uikit
     * @param  string $type  success|primary|info|warning|danger
     * @return string the alert HTML code
     */

    public static function buildAlert(string $content_text, string $framework, string $type = 'success')
    {
        $alert = $content_text;
        if ($framework === 'bs4' || $framework === 'material-bootstrap') {
            $alert = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">' . $content_text . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        } elseif ($framework === 'bs5') {
            $alert = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">' . $content_text . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } elseif ($framework === 'bulma') {
            $alert = '<div class="notification is-' . $type . '">
            <button class="delete"></button>' . $content_text . '</div><script>document.addEventListener(\'DOMContentLoaded\', () => {
                (document.querySelectorAll(\'.notification .delete\') || []).forEach(($delete) => {
                  const $notification = $delete.parentNode;

                  $delete.addEventListener(\'click\', () => {
                    $notification.parentNode.removeChild($notification);
                  });
                });
              });</script>';
        } elseif ($framework === 'foundation') {
            $alert = '<div class="callout ' . $type . '" data-closable>' . $content_text . ' <button class="close-button" aria-label="Dismiss alert" type="button" data-close> <span aria-hidden="true">&times;</span> </button> </div>';
        } elseif ($framework === 'material') {
            $clazz = [
                'success'  => 'teal',
                'info'     => 'cyan',
                'primary'  => 'blue',
                'warning'  => 'amber',
                'danger'   => 'red'
            ];
            $type = $clazz[$type];
            $alert = '<p class="card-panel ' . $type . ' accent-2">' . $content_text . '</p>';
        } elseif ($framework === 'tailwind') {
            $clazz = [
                'success'  => 'green',
                'info'     => 'indigo',
                'primary'  => 'blue',
                'warning'  => 'yellow',
                'danger'   => 'red'
            ];
            $type = $clazz[$type];
            $alert = '<div class="flex p-4 mb-4 bg-' . $type . '-100 border-t-4 border-' . $type . '-500 dark:bg-' . $type . '-200" role="alert"> <svg class="flex-shrink-0 w-5 h-5 text-' . $type . '-700" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg> <div class="ml-3 text-sm font-medium text-' . $type . '-700">' . $content_text . '</div> <button type="button" class="collapse-toggle-btn ml-auto -mx-1.5 -my-1.5 bg-' . $type . '-100 dark:bg-' . $type . '-200 text-' . $type . '-500 rounded-lg focus:ring-2 focus:ring-' . $type . '-400 p-1.5 hover:bg-' . $type . '-200 dark:hover:bg-' . $type . '-300 inline-flex h-8 w-8" aria-label="Close"> <span class="sr-only">Dismiss</span> <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg> </button> </div><script>document.addEventListener(\'DOMContentLoaded\', () => {
                (document.querySelectorAll(\'button.collapse-toggle-btn\') || []).forEach(($ct) => {
                    let $alert = $ct.closest(\'div[role="alert"]\');
                  $ct.addEventListener(\'click\', (e) => {
                    $alert.parentNode.removeChild($alert);
                  });
                });
              });</script>';
        } elseif ($framework === 'uikit') {
            $type = str_replace('info', 'primary', $type);
            $alert = '<div class="uk-alert-' . $type . '" uk-alert> <a class="uk-alert-close" uk-close onclick="UIkit.alert(this).close();"></a> <p>' . $content_text . '</p> </div>
        ';
        }

        return $alert;
    }
    /**
     * check PHPForm Builder copy registration
     * @return bool
     */
    public static function checkRegistration():bool
    {
        $result = false;

        $main_host = static::getDomain($_SERVER['HTTP_HOST']);
        if (!is_dir(dirname(dirname(__FILE__)) . '/' . $main_host)) {
            try {
                if (!mkdir(dirname(dirname(__FILE__)) . '/' . $main_host)) {
                    throw new \Exception('Unable to create new directory ' . str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, dirname(dirname(__FILE__)) . '/' . $main_host) . '.<br><a href="http://php.net/manual/en/function.chmod.php" target="_blank" style="color:#fff;text-decoration:underline;">Increase your chmod</a> to give write permission to create this folder and write the license file inside.', 1);
                }
            } catch (\Exception $e) {
                $error_msg = '<div style="line-height:30px;border-radius:5px;border-bottom:1px solid #ac2925;background-color: #c9302c;margin:10px auto;max-width:90%"><p style="color:#fff;text-align:center;font-size:16px;margin:0">' . $e->getMessage() . '</p></div>';

                echo $error_msg;

                return false;
            }
        }
        if (!file_exists(dirname(dirname(__FILE__)) . '/' . $main_host . '/license.php')) {
            try {
                if (file_put_contents(dirname(dirname(__FILE__)) . '/' . $main_host . '/license.php', '') === false) {
                    throw new \Exception('Unable to write License file to ' . str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, dirname(dirname(__FILE__)) . '/' . $main_host . '/license.php') . '.<br><a href="http://php.net/manual/en/function.chmod.php" target="_blank" style="color:#fff;text-decoration:underline;">Increase your chmod</a> to give write permission to this folder.', 1);
                }
            } catch (\Exception $e) {
                $error_msg = '<div style="line-height:30px;border-radius:5px;border-bottom:1px solid #ac2925;background-color: #c9302c;margin:10px auto;max-width:90%"><p style="color:#fff;text-align:center;font-size:16px;margin:0">' . $e->getMessage() . '</p></div>';

                echo $error_msg;

                return false;
            }
        }

        $license  = file_get_contents(dirname(dirname(__FILE__)) . '/' . $main_host . '/license.php');

        if (preg_match('`^<ROOT_URL>(http|https)://(?:[a-zA-Z0-9_.-]*)' . str_replace('.', '\.', $main_host) . '[^<]+</ROOT_URL>`', $license)) {
            $result = true;
        }

        return $result;
    }
    /**
     * stores the ID of the form to be cleared.
     * when next instance is created it will not store posted values in session
     * unsets all sessions vars attached to the form
     * @param string $form_ID
     * @return void
     */
    public static function clear(string $form_ID):void
    {
        $_SESSION['clear_form'][$form_ID] = true;
        if (isset($_SESSION[$form_ID]['fields'])) {
            foreach ($_SESSION[$form_ID]['fields'] as $key => $name) {
                unset($_SESSION[$form_ID]['fields'][$key]);
                unset($_SESSION[$form_ID][$name]);
            }
        }
        if (isset($_SESSION[$form_ID]['required_fields'])) {
            foreach ($_SESSION[$form_ID]['required_fields'] as $key => $name) {
                unset($_SESSION[$form_ID]['required_fields'][$key]);
                unset($_SESSION[$form_ID][$name]);
            }
        }
        if (isset($_SESSION[$form_ID]['required_fields_conditions'])) {
            foreach ($_SESSION[$form_ID]['required_fields_conditions'] as $key => $name) {
                unset($_SESSION[$form_ID]['required_fields_conditions'][$key]);
            }
        }
        if (isset($_SESSION['errors'][$form_ID])) {
            unset($_SESSION['errors'][$form_ID]);
        }
    }

    /**
     * get the main domain from any domain or subdomain
     *
     * https://stackoverflow.com/questions/1201194/php-getting-domain-name-from-subdomain
     *
     * @param string $host
     * @return string
     */
    public static function getDomain(string $host)
    {
        $myhost = strtolower(trim($host));
        $count = substr_count($myhost, '.');
        if ($count === 1 || $count === 2) {
            if (strlen(explode('.', $myhost)[1]) > 3) {
                $myhost = explode('.', $myhost, 2)[1];
            }
        } elseif ($count > 2) {
            $myhost = static::getDomain(explode('.', $myhost, 2)[1]);
        }
        return $myhost;
    }

    /**
     * merge previously registered session vars => values of each registered form
     * @param  array $forms_array array of forms IDs to merge
     * @return array pairs of names => values
     *                           ex : $values[$field_name] = $value
     */
    public static function mergeValues(array $form_names_array):array
    {
        $values = [];
        foreach ($form_names_array as $form_name) {
            $fields = $_SESSION[$form_name]['fields'];
            foreach ($fields as $field_name) {
                if (isset($_SESSION[$form_name][$field_name])) {
                    $values[$field_name] = $_SESSION[$form_name][$field_name];
                }
            }
        }

        return $values;
    }

    /**
    * register all posted values in session
    * ex: $_SESSION['form-id']['field-name'] = $_POST['field-name'];
    *
    * @param string $form_ID
    * @return void
    */
    public static function registerValues(string $form_ID):void
    {
        if (!isset($_SESSION[$form_ID])) {
            $_SESSION[$form_ID]           = [];
            $_SESSION[$form_ID]['fields'] = [];
        }
        foreach ($_SESSION[$form_ID]['fields'] as $name) {
            if (isset($_POST[$name])) {
                $value = $_POST[$name];
                if (!is_array($value)) {
                    if (!isset($_POST[$name . '_submit'])) {
                        $_SESSION[$form_ID][$name] = trim($value);
                    } else {
                        $_SESSION[$form_ID][$name] = trim($_POST[$name . '_submit']);
                    }
                    // echo $name . ' => ' . $value . '<br>';
                } else {
                    $_SESSION[$form_ID][$name] = [];
                    foreach ($value as $array_key => $array_value) {
                        $_SESSION[$form_ID][$name][$array_key] = trim($array_value);
                        // echo $name . ' ' . $array_key . ' ' . $array_value . '<br>';
                    }
                }
            } else { // if checkbox unchecked, it hasn't been posted => we store empty value
                $_SESSION[$form_ID][$name] = '';
            }
        }
    }

    /**
     * Send an email with posted values and custom content
     *
     * Tests and secures values to prevent attacks (phpmailer/extras/htmlfilter.php => HTMLFilter)
     * Uses custom html/css template and replaces shortcodes - syntax : {fieldname} - in both html/css templates with posted|custom values
     * Creates an automatic html table with vars/values - shortcode : {table}
     * Merges html/css to inline style with Pelago Emogrifier
     * Sends email and catches errors with Phpmailer
     * @param  array  $options
     *                     sender_email                    : the email of the sender
     *                     sender_name [optional]          : the name of the sender
     *                     reply_to_email [optional]       : the email for reply
     *                     recipient_email                 : the email destination(s), separated with commas
     *                     cc [optional]                   : the email(s) of copies to send, separated with commas
     *                     bcc [optional]                  : the email(s) of blind copies to send, separated with commas
     *                     subject                         : The email subject
     *                     isHTML                          : Send the mail in HTML format or Plain text (default: true)
     *                     textBody                        : The email body if isHTML is set to false
     *                     attachments [optional]          : file(s) to attach : separated with commas, or array (see details inside function)
     *                     template [optional]             : name of the html/css template to use in phpformbuilder/mailer/email-templates/
     *                                           (default: default.html)
     *                     human_readable_labels [optional]: replace '-' ans '_' in labels with spaces if true (default: true)
     *                     values                          : $_POST
     *                     filter_values [optional]        : posted values you don't want to include in the e-mail automatic html table
     *                     custom_replacements [optional]  : array to replace shortcodes in email template. ex : array('mytext' => 'Hello !') will replace {mytext} with Hello !
     *                     sent_message [optional]         : message to display when email is sent. If sent_message is a string it'll be automatically wrapped into an alert div. Else if sent_message is html code it'll be displayed as is.
     *                     debug [optional]                : displays sending errors (default: false)
     *                     smtp [optional]                 : use smtp (default: false)
     *
     * @param  array  $smtp_settings
     *                         host :       String      Specify main and backup SMTP servers - i.e: smtp1.example.com, smtp2.example.com
     *                         smtp_auth:   Boolean     Enable SMTP authentication
     *                         username:    String      SMTP username
     *                         password:    String      SMTP password
     *                         smtp_secure: String      Enable TLS encryption. Accepted values: tls|ssl
     *                         port:        Number      TCP port to connect to (usually 25 or 587)
     *
     * @return string sent_message
     *                         success or error message to display on the page
     */
    public static function sendMail(array $options, array $smtp_settings = []):string
    {
        $output = '';
        $default_options = [
            'sender_email'          => '',
            'sender_name'           => '',
            'reply_to_email'        => '',
            'recipient_email'       => '',
            'cc'                    => '',
            'bcc'                   => '',
            'subject'               => 'Contact',
            'attachments'           => '',
            'template'              => 'default.html',
            'human_readable_labels' => true,
            'isHTML'                => true,
            'textBody'              => '',
            'values'                => $_POST,
            'filter_values'         => '',
            'custom_replacements'   => [],
            'sent_message'          => 'Your message has been successfully sent !',
            'debug'                 => false
        ];

        /* replace default options with user's */

        foreach ($default_options as $key => $value) {
            if (isset($options[$key])) {
                ${$key} = $options[$key];
            } else {
                ${$key} = $value;
            }
        }
        require_once dirname(dirname(__FILE__)) . '/mailer/phpmailer/src/PHPMailer.php';
        require_once dirname(dirname(__FILE__)) . '/mailer/phpmailer/src/SMTP.php';
        require_once dirname(dirname(__FILE__)) . '/mailer/phpmailer/src/Exception.php';
        require_once dirname(dirname(__FILE__)) . '/mailer/emogrifier/Emogrifier.php';
        require_once dirname(dirname(__FILE__)) . '/mailer/phpmailer/extras/htmlfilter.php';
        $mail = new PHPMailer;
        try {
            // if smtp
            if (!empty($smtp_settings)) {
                if ($debug) {
                    $mail->SMTPDebug = 3;                           // Enable verbose debug output
                }
                $mail->isSMTP();                                    // Set mailer to use SMTP
                $mail->Host       = $smtp_settings['host'];         // Specify main and backup SMTP servers
                $mail->SMTPAuth   = $smtp_settings['smtp_auth'];    // Enable SMTP authentication
                $mail->Username   = $smtp_settings['username'];     // SMTP username
                $mail->Password   = $smtp_settings['password'];     // SMTP password
                $mail->SMTPSecure = $smtp_settings['smtp_secure'];  // Enable TLS encryption, `ssl` also accepted
                $mail->Port       = $smtp_settings['port'];         // TCP port to connect to
            }
            $mail->From     = $sender_email;
            $mail->Sender   = $sender_email;
            if ($sender_name != '') {
                if ($reply_to_email !== '') {
                    $mail->addReplyTo($reply_to_email);
                } else {
                    $mail->addReplyTo($sender_email, $sender_name);
                }
                $mail->FromName = $sender_name;
            } else {
                if ($reply_to_email !== '') {
                    $mail->addReplyTo($reply_to_email);
                } else {
                    $mail->addReplyTo($sender_email);
                }
                $mail->FromName = $sender_email;
            }
            $indiAdress = explode(',', $recipient_email);
            foreach ($indiAdress as $key => $value) {
                $mail->addAddress(trim($value));
            }
            if ($bcc != '') {
                $indiBCC = explode(',', $bcc);
                foreach ($indiBCC as $key => $value) {
                    $mail->addBCC(trim($value));
                }
            }
            if ($cc != '') {
                $indiCC = explode(',', $cc);
                foreach ($indiCC as $key => $value) {
                    $mail->addCC(trim($value));
                }
            }
            if ($attachments != '') {
                /*
                    =============================================
                    single file :
                    =============================================

                    $attachments = 'path/to/file';

                    =============================================
                    multiple files separated with commas :
                    =============================================

                    $attachments = 'path/to/file_1, path/to/file_2';

                    =============================================
                    single file with file_path + file_name :
                    (specially for posted files)
                    =============================================

                    $attachments =  arrray(
                                        'file_path' => 'path/to/file.jpg', // complete path with filename
                                        'file_name' => 'my-file.jpg'
                                    )

                    =============================================
                    multiple files array containing :
                        sub-arrays with file_path + file_name
                        or file_name strings
                    =============================================

                    $attachments =  arrray(
                                        'path/to/file_1',
                                        'path/to/file_2',
                                        arrray(
                                            'file_path' => 'path/to/file.jpg', // complete path with filename
                                            'file_name' => 'my-file.jpg'
                                        ),
                                        ...
                                    )
                 */

                if (is_array($attachments)) {
                    if (isset($attachments['file_path'])) {
                        $mail->addAttachment($attachments['file_path'], $attachments['file_name']);
                    } else {
                        foreach ($attachments as $key => $value) {
                            if (is_array($value)) {
                                $mail->addAttachment($value["file_path"], $value["file_name"]);
                            } else {
                                $attach = explode(",", $attachments);
                                foreach ($attach as $key => $value) {
                                    $mail->addAttachment(trim($value));
                                }
                            }
                        }
                    }
                } else {
                    $attach = explode(",", $attachments);
                    foreach ($attach as $key => $value) {
                        $mail->addAttachment(trim($value));
                    }
                }
            }
            $filter = explode(',', $filter_values);

            // filter captchas
            $filter[] = 'g-recaptcha-response';
            $filter[] = 'h-captcha-response';

            // add token to filter
            foreach ($values as $key => $value) {
                if (preg_match('`-token$`', $key) || preg_match('`submit-btn$`', $key)) {
                    $filter[] = $key;
                }
            }

            // sanitize filter values
            for ($i = 0; $i < count($filter); $i++) {
                $filter[$i] = trim(mb_strtolower($filter[$i]));
            }
            $mail->Subject = $subject;
        } catch (Exception $e) { //Catch all kinds of bad addressing
            throw new Exception((string) $e);
        }
        if ($isHTML) {
            try {
                /* Load html & css templates */

                $html_template_path = dirname(dirname(__FILE__)) . '/mailer/email-templates/' . $template;
                $html_template_custom_path = dirname(dirname(__FILE__)) . '/mailer/email-templates-custom/' . $template;

                if (file_exists($html_template_custom_path)) {
                    $template_error_msg = '';
                    $debug_msg = '';
                    // try to load html template in email-templates-custom dir
                    if (!($html = file_get_contents($html_template_custom_path))) {
                        $template_error_msg = 'Html template file doesn\'t exists';
                        $debug_msg          = $html_template_custom_path;
                    }
                } elseif (file_exists($html_template_path)) {
                    // try to load html template in email-templates dir
                    if (!($html = file_get_contents($html_template_path))) {
                        $template_error_msg = 'Html template file doesn\'t exists';
                        $debug_msg          = $html_template_path;
                    }
                } else {
                    $template_error_msg = 'Html template file doesn\'t exists';
                    $debug_msg          = $html_template_path;
                }
                $css_template_path        = str_replace('.html', '.css', $html_template_path);
                $css_template_custom_path = str_replace('.html', '.css', $html_template_custom_path);

                if (file_exists($css_template_custom_path) && empty($template_error_msg)) {
                    // try to load css template in email-templates-custom dir
                    if (!($css = file_get_contents($css_template_custom_path))) {
                        $template_error_msg = 'CSS template file doesn\'t exists';
                        $debug_msg          = $css_template_custom_path;
                    }
                } elseif (file_exists($css_template_path) && empty($template_error_msg)) {
                    // try to load css template in email-templates dir
                    if (!($css = file_get_contents($css_template_path))) {
                        $template_error_msg = 'CSS template file doesn\'t exists';
                        $debug_msg          = $css_template_path;
                    }
                } elseif (empty($template_error_msg)) {
                    $template_error_msg = 'CSS template file doesn\'t exists';
                    $debug_msg          = $css_template_path;
                }

                /* If html|css template not found */

                if (!empty($template_error_msg)) {
                    if ($debug) {
                        $template_error_msg = $debug_msg . '<br>' . $template_error_msg;
                    }

                    throw new \Exception('<div style="line-height:30px;border-radius:5px;border-bottom:1px solid #ac2925;background-color: #c9302c;margin:10px auto;"><p style="color:#fff;text-align:center;font-size:16px;margin:0">' . $template_error_msg . '</p></div>');
                }

                /* Automatic table including all but filtered values */

                $email_table = '<table class="one-column">';
                $email_table .= '<tbody>';

                // prepare regex for human_readable_labels
                $find = ['`([a-zA-Z0-9])-([a-zA-Z0-9])`', '`([a-zA-Z0-9])_([a-zA-Z0-9])`'];
                $replace = ['$1 $2', '$1 $2'];
                foreach ($values as $key => $value) {
                    if (!in_array(mb_strtolower($key), $filter)) {
                        // replace key (label) with human_readable_label
                        if ($human_readable_labels) {
                            $key = preg_replace($find, $replace, $key);
                        }
                        if (!is_array($value)) {
                            $email_table .= '<tr>';

                            // replace with custom if key exists
                            if (is_array($custom_replacements) && in_array($key, array_keys($custom_replacements))) {
                                $email_table .= '<th class="inner">' . ucfirst($custom_replacements[$key]) . ': ' . '</th>';
                            } else {
                                $email_table .= '<th class="inner">' . ucfirst($key) . ': ' . '</th>';
                            }
                            $email_table .= '<td class="inner">' . nl2br($value) . '</td>';
                            $email_table .= '</tr>';
                        } else {
                            foreach ($value as $key_array => $value_array) {
                                if (!is_array($value_array)) {
                                    $email_table .= '<tr>';
                                    $email_table .= '<th class="inner">' . ucfirst($key) . ' ' . ($key_array + 1) . ': ' . '</th>';
                                    $email_table .= '<td class="inner">' . $value_array . '</td>';
                                    $email_table .= '</tr>';
                                }
                            }
                        }
                    }
                }
                $email_table .= '</tbody>';
                $email_table .= '</table>';

                $html = str_replace('{table}', $email_table, $html);
                $html = str_replace('{subject}', $subject, $html);


                /* replacements in html */

                // first, replace posted values in html
                foreach ($values as $key => $value) {
                    if (!in_array(mb_strtolower($key), $filter) && !is_array($value)) {
                        $html = str_replace('{' . $key . '}', $value, $html);
                    }
                }

                // then replace custom replacements in html
                foreach ($custom_replacements as $key => $value) {
                    if (!in_array(mb_strtolower($key), $filter) && !is_array($value)) {
                        $html = str_replace('{' . $key . '}', $value, $html);
                    }
                }

                /* custom replacements in css */

                if (!empty($css)) {
                    foreach ($custom_replacements as $key => $value) {
                        if (!in_array(mb_strtolower($key), $filter) && !is_array($value)) {
                            $css = str_replace('{' . $key . '}', $value, $css);
                        }
                    }

                    $emogrifier = new \Pelago\Emogrifier();
                    $emogrifier->addExcludedSelector('br');
                    $emogrifier->enableCssToHtmlMapping();
                    $emogrifier->setHtml($html);
                    $emogrifier->setCss($css);
                    $mergedHtml = $emogrifier->emogrify();
                } else {
                    $mergedHtml = $html;
                }
                HTMLFilter($mergedHtml, '', false);
            } catch (\Exception $e) { //Catch all content errors
                $output = $e->getMessage();
            }
            $mail->msgHTML($mergedHtml, dirname(dirname(__FILE__)), true);
        } else {
            $mail->isHTML(false);
            $mail->Body = $textBody;
        }
        $charset = 'iso-8859-1';
        if (function_exists('mb_detect_encoding')) {
            if ($isHTML) {
                $charset = mb_detect_encoding($mergedHtml);
            } else {
                $charset = mb_detect_encoding($textBody);
            }
        }
        $mail->CharSet = $charset;
        if (!$mail->send()) {
            if ($debug) {
                if (!isset($_SESSION['phpfb_framework'])) {
                    $output = '<p><strong>Mailer Error: ' . $mail->ErrorInfo . '</strong></p>';
                }
                $output = static::buildAlert('Mailer Error: ' . $mail->ErrorInfo, $_SESSION['phpfb_framework'], 'danger');
            }
        } else {
            if (!isset($_SESSION['phpfb_framework']) || (isset($sent_message[0]) && $sent_message[0] === '<')) {
                $output = $sent_message;
            }
            $output = static::buildAlert($sent_message, $_SESSION['phpfb_framework'], 'success');
        }

        return $output;
    }

    /**
     * validate token to protect against CSRF
     *
     * @param string $form_ID
     * @return bool
     */
    public static function testToken(string $form_ID):bool
    {
        require_once dirname(dirname(__FILE__)) . '/Validator/Token.php';
        if (TOKEN_CONFIG != '885kufR**.xp5e6S' || strtolower(sha1_file(dirname(dirname(__FILE__)) . '/Validator/Token.php')) != 'bc9f3bff92094db72c5de39ac44d7df685e2381f') {
            exit('error');
        }
        $token_notifications_array = verifyToken(null, 0);
        if ($token_notifications_array['notification_case'] !== 'notification_license_ok') {
            exit($token_notifications_array['notification_text']);
        }
        if (isset($_SESSION[$form_ID . '_token']) && isset($_SESSION[$form_ID . '_token_time']) && isset($_POST[$form_ID . '-token']) && $_SESSION[$form_ID . '_token'] == $_POST[$form_ID . '-token'] && $_SESSION[$form_ID . '_token_time'] >= (time() - 1800)) {
            // validity for token = 1800 sec. = 30mn.

            return true;
        }

        return false;
    }

    /**
     * create Validator object and auto-validate all required fields
     * @param  string     $form_ID the form ID
     * @return Validator  the Validator
     */
    public static function validate(string $form_ID, string $lang = 'en'):Validator
    {
        include_once dirname(dirname(__FILE__)) . '/Validator/Validator.php';
        include_once dirname(dirname(__FILE__)) . '/Validator/Exception.php';
        $validator = new Validator($_POST, $lang);
        $required = $_SESSION[$form_ID]['required_fields'];
        foreach ($required as $field) {
            $do_validate = true;

            // if dependent field, test parent posted value & validate only if parent posted value matches condition
            if (!empty($_SESSION[$form_ID]['required_fields_conditions'][$field])) {
                $parent_field = $_SESSION[$form_ID]['required_fields_conditions'][$field]['parent_field'];
                $show_values  = preg_split('`,(\s)?`', $_SESSION[$form_ID]['required_fields_conditions'][$field]['show_values']);
                $inverse      = $_SESSION[$form_ID]['required_fields_conditions'][$field]['inverse'];

                if (!isset($_POST[$parent_field])) {
                    // if parent field is not posted (nested dependent fields)
                    $do_validate = false;
                } elseif (!in_array($_POST[$parent_field], $show_values) && !$inverse) {
                    // if posted parent value doesn't match show values
                    $do_validate = false;
                } elseif (in_array($_POST[$parent_field], $show_values) && $inverse) {
                    // if posted parent value does match show values but dependent is inverted
                    $do_validate = false;
                }
            }
            if ($do_validate) {
                if (isset($_POST[$field]) && is_array($_POST[$field])) {
                    $field = $field . '.0';
                }
                $validator->required()->validate($field);
            }
        }

        return $validator;
    }
}
