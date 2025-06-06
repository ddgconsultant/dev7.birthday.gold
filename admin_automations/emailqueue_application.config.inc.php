<?php

        namespace Emailqueue;

        define("API_KEY", "254841OmN1W_L_Xa");  // The secret key to access Emailqueue via API. Change it to a random string of your choice.

        define("FRONTEND_USER", "admin"); // The user name for the frontend. Change it to your desired user.
        define("FRONTEND_PASSWORD", "Hvm!7644"); // The password for the frontend. Change it to a strong password of your choice.

        define("QUEUED_MESSAGES", 40); // Number of last queued messages to show in frontend
        define("LATEST_DELIVERED_MESSAGES", 10); // Number of last delivered messages to show in frontend
        define("LATEST_CANCELLED_MESSAGES", 10); // Number of last cancelled messages to show in frontend
        define("MAXIMUM_DELIVERY_TIMEOUT", 50); // Maximum seconds Emailqueue should spend sending queued emails everytime it's called. Keep this smaller than the amount of time between calls to the delivery script (e.g. If you're calling delivery every 60 seconds, keep this at something like 50 to avoid accumulating instances of Emailqueue eating resources)
        define("DELIVERY_INTERVAL", 10); // Hundredths of a second between each email send. Use this to be more friendly to SMTP servers, should be a balance between being friendly and the number of emails you need to send per seconds in order to keep your queue clean.
        define("MAX_DELIVERS_A_TIME", 1000); // Number of maximum messages to deliver every time delivery script is called. Anyway, this is better controlled with the MAXIMUM_DELIVERY_TIMEOUT configuration above.
        define("SENDING_RETRY_MAX_ATTEMPTS", 3); // Maximum number of attemps to send a message if error is found.
        define("PURGE_OLDER_THAN_DAYS", 5); // Purge messages older than this days from the database. Depending on the amount of emails you send, use this setting to keep your sent emails database to grow too big. It should be a balance between how big would you like to keep your sent emails history and how responsive would you like emailqueue to be. Smaller database will make emailqueue run faster when specially inserting new emails.

        define("SEND_METHOD", "smtp"); // Set it to either "smtp" or "sendmail" to choose the method for delivering emails. If "smtp" is choosen, at least the SMTP_SERVER below must be set.
        define("SMTP_SERVER", "mail.birthday.gold"); // The IP or hostname of the SMTP server
        define("SMTP_PORT", 587); // The port of the SMTP server
        define("SMTP_IS_AUTHENTICATION", true); // True to use SMTP server Authentication
        define("SMTP_AUTHENTICATION_USERNAME", "postmaster@birthday.gold");
        define("SMTP_AUTHENTICATION_PASSWORD", "Hvm!7644");

        define("CHARSET", "utf-8"); //Used in Content-Type Email Header
        define("CONTENT_TRANSFER_ENCODING", "8bit"); //Content-Transfer-Encoding Email Header. May be 7bit, 8bit, quoted-printable or base64

        define("PHPMAILER_LANGUAGE", "en");

        define("DEFAULT_TIMEZONE", "America/Denver");

        define("LOGS_DIR", "logs"); // The directory to store logs
        define("LOGS_FILENAME_DATEFORMAT", "Y-m-d"); // The file name format for log files, as a parameter for the PHP date() function
        define("LOGS_DATA_DATEFORMAT", "Y-m-d H:i:s"); // The format of the date as stored inside log files, as a parameter for the PHP date() function

    define("IS_DEVEL_ENVIRONMENT", false); // When set to true, only emails addressed to emails into $devel_emails array are sent

    $devel_emails = [
        "me@birthday.gold"
    ];
