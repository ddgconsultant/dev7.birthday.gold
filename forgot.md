for /forgot.php fix "Enter your email to reset it" -- make it dynamic.  Use the class.sms.php class and the send the appropriate message.  (check bg_user_attributes as the profile description)


bg_user_attribute record example:
attribute_id	user_id	type	name	description	status	create_dt	modify_dt	rank	value	string_value	grouping	category	visibility	formatting	start_dt	end_dt
256602	4802	profile	profile_phone_number		active	2025-01-23 16:35:34	2025-01-23 16:35:34									


42S22
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'phone' in 'where clause'

PDOException Object
(
    [message:protected] => SQLSTATE[42S22]: Column not found: 1054 Unknown column 'phone' in 'where clause'
    [string:Exception:private] => PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'phone' in 'where clause' in [WEBSERVER_DIRECTORY]\core\classes\class.database.php:79
Stack trace:
#0 [WEBSERVER_DIRECTORY]\core\classes\class.database.php(79): PDO->prepare()
#1 [WEBSERVER_DIRECTORY]\core\classes\class.account.php(336): Database->prepare()
#2 [WEBSERVER_DIRECTORY]\forgot.php(30): Account->getuserdata()
#3 {main}
    [code:protected] => 42S22
    [file:protected] => [WEBSERVER_DIRECTORY]\core\classes\class.database.php
    [line:protected] => 79
    [trace:Exception:private] => Array
        (
            [0] => Array
                (
                    [file] => [WEBSERVER_DIRECTORY]\core\classes\class.database.php
                    [line] => 79
                    [function] => prepare
                    [class] => PDO
                    [type] => ->
                )

            [1] => Array
                (
                    [file] => [WEBSERVER_DIRECTORY]\core\classes\class.account.php
                    [line] => 336
                    [function] => prepare
                    [class] => Database
                    [type] => ->
                )

            [2] => Array
                (
                    [file] => [WEBSERVER_DIRECTORY]\forgot.php
                    [line] => 30
                    [function] => getuserdata
                    [class] => Account
                    [type] => ->
                )

        )

    [previous:Exception:private] => 
    [errorInfo] => Array
        (
            [0] => 42S22
            [1] => 1054
            [2] => Unknown column 'phone' in 'where clause'
        )