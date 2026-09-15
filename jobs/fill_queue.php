<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_time_limit(60);
    include("../include/db_connect.php");
    include("../include/general_functions.php");
    require_once('../include/constant.php');
    $sender_id = 10;
    $import_limit = 300;

    $week_ago = date('Y-m-d', strtotime('-3 days'));
    if (isset($_GET['type']) && $_GET['type'] == 'evening') {
        $today = date("Y-m-d", strtotime('+1 day'));
        $send_at = date("Y-m-d 12:00:00", strtotime('+1 day'));
    } else {
        $today = date("Y-m-d");
        $send_at = date("Y-m-d 12:00:00");
    }

    function removeAlreadyQueuedReceivers($newsletter_email_id)
    {
        include("../include/db_connect.php");

        //Empfänger markieren, damit Import nicht noch einmal versucht wird
        $update_email_query = $db->prepare("UPDATE
                                                ts_database.newsletter_emails
                                            SET
                                                sender_id = NULL
                                            WHERE
                                                newsletter_email_id=:newsletter_email_id
                                            LIMIT 1
                                            ");
        $update_email_query->bindParam(':newsletter_email_id', $newsletter_email_id, PDO::PARAM_INT);
        if (!$update_email_query->execute()) {
            print_r($update_email_query->errorInfo());
        }
    }
    //    Schedule holen
    $newsletter_schedule_query = $db->prepare("SELECT
                                                        newsletter_schedule.newsletter_schedule_id,
                                                        newsletter_schedule.newsletter_id,
                                                        newsletter_schedule.newsletter_region_id,
                                                        newsletter_schedule.send_at,
                                                        newsletter.cannot_read_text,
                                                        newsletter.title,
                                                        newsletter.type as 'type'
                                                    FROM
                                                        ts_mailer.newsletter_schedule
                                                            INNER JOIN
                                                        ts_database.newsletter ON newsletter_schedule.newsletter_id=newsletter.newsletter_id
                                                    WHERE
                                                        newsletter_schedule.send_at LIKE '$today%'
                                                        AND newsletter_schedule.send_at<=:send_at
                                                        AND newsletter_schedule.in_queue=0
                                                        AND newsletter_schedule.permission=1
                                                    ORDER BY
                                                        newsletter_schedule.newsletter_schedule_id ASC
    ");
    $newsletter_schedule_query->bindParam(':send_at', $send_at, PDO::PARAM_STR);
    if (!$newsletter_schedule_query->execute()) {
        print_r($newsletter_schedule_query->errorInfo());
    } else {
        if ($newsletter_schedule_query->rowCount() > 0) {
            $queue_counter = 1;
            while ($newsletter_schedule = $newsletter_schedule_query->fetch(PDO::FETCH_OBJ)) {
                // echo "NewsletterRegion ".$newsletter_schedule->newsletter_region_id." ";

                // process only one newsletter_schedule per session
                // However, if a server has crashed, do not wait for the crashed server, but process the next schedule
                if ($queue_counter <= $import_limit) {

                    //Empfänger holen
                    $branding_subscription_condition_query = "companies.is_public=1
                                            OR (SELECT
                                                    1
                                                FROM
                                                    company_subscriptions
                                                WHERE
                                                    company_id=companies.company_id
                                                    AND (end_date>:today OR end_date IS NULL)
                                                    AND product_id=:product_id LIMIT 1)
                                                    OR (CASE
                                                            WHEN companies.company_id IN ('" . implode("','", NOTIFIED_COMPANIES_FOR_BRANDING) . "') THEN '2024-08-01'
                                                            ELSE '" . BRANDING_START_DATE . "'
                                                        END) > :today";
                    $receivers_query = $db->prepare("SELECT
                                                            $newsletter_schedule->newsletter_id AS newsletter_id,
                                                            '$newsletter_schedule->cannot_read_text' AS cannot_read_text,
                                                            :title AS title,
                                                            $newsletter_schedule->type AS 'type',
                                                            companies.company_id,
                                                            companies.name as 'company',
                                                            companies.use_hyphen,
                                                            companies.subdomain,
                                                            (CASE
                                                                WHEN $branding_subscription_condition_query
                                                                THEN companies.logo_url
                                                                ELSE :basic_logo_url
                                                            END) AS 'logo_url',
                                                            (CASE
                                                                WHEN $branding_subscription_condition_query
                                                                THEN companies.color_basic
                                                                ELSE :basic_color
                                                            END) AS 'color_basic',
                                                            companies.registration_type,
                                                            emails.newsletter_email_id,
                                                            emails.newsletter_region_id,
                                                            emails.email,
                                                            users.user_id,
                                                            users.first_name as 'user_name',
                                                            (
                                                            SELECT
                                                                COUNT(nl_rows.newsletter_row_id)
                                                            FROM
                                                                ts_database.newsletter_rows nl_rows
                                                            WHERE
                                                                nl_rows.newsletter_id=:newsletter_id
                                                                AND nl_rows.type='header'
                                                            ) as 'nl_header',
                                                            (CASE WHEN (companies.registration_type = 3 OR companies.registration_type = 5)
                                                            THEN
                                                                (SELECT
                                                                    codes.code
                                                                FROM
                                                                    ts_database.company_codes codes
                                                                WHERE
                                                                    codes.company_id=companies.company_id
                                                                    AND codes.type='EQ'
                                                                ORDER BY
                                                                    codes.company_code_id ASC
                                                                LIMIT 1
                                                                )
                                                            ELSE ''
                                                            END) as 'registration_code'
                                                        FROM
                                                            ts_database.newsletter_emails emails
                                                                INNER JOIN
                                                            ts_database.users users ON emails.user_id=users.user_id
                                                                INNER JOIN
                                                            ts_database.companies companies ON companies.company_id=users.company_id
                                                        WHERE
                                                            emails.sender_id=:sender_id
                                                            AND emails.email!=''
                                                            AND users.active=1
                                                            AND users.deactivation_datetime IS NULL
                                                            AND emails.active=1
                                                            AND companies.registration_type!=0
                                                            AND emails.newsletter_region_id=:newsletter_region_id
                                                            AND NOT EXISTS (SELECT 1 FROM ts_mailer.newsletter_queue WHERE newsletter_queue.email=emails.email AND newsletter_queue.newsletter_id=:newsletter_id LIMIT 1)
                                                        GROUP BY
                                                            emails.email
                                                        LIMIT $import_limit
                ");
                    //TODO: Selbe Email, verschiedene Newsletter
                    $receivers_query->bindParam(":title", $newsletter_schedule->title, PDO::PARAM_STR);
                    $receivers_query->bindParam(':newsletter_region_id', $newsletter_schedule->newsletter_region_id, PDO::PARAM_INT);
                    $receivers_query->bindParam(':newsletter_id', $newsletter_schedule->newsletter_id, PDO::PARAM_INT);
                    $receivers_query->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
                    $receivers_query->bindParam(':today', $today, PDO::PARAM_STR);
                    $receivers_query->bindValue(':basic_color', BASIC_COLOR, PDO::PARAM_STR);
                    $receivers_query->bindValue(':product_id', COMPANY_BRANDING_PRODUCT_ID, PDO::PARAM_STR);
                    $receivers_query->bindValue(':basic_logo_url', BASIC_LOGO_URL_WITHOUT_CLOUDFRONT_URL, PDO::PARAM_STR);
                    if ($receivers_query->execute()) {
                        if ($receivers_query->rowCount() > 0) {
                            while ($newsletter = $receivers_query->fetch(PDO::FETCH_OBJ)) {

                                //Link zur Messung von Newsletteröffnungen
                                $metrics_open_str = base64_encode("open-$newsletter->newsletter_id-$newsletter->newsletter_email_id");
                                $error = "";

                                //Signalisieren, dass es sich um einen Import handelt (Wichtig für Zählung der Newsletterplatzierungen einzelner Angebote)
                                $newsletter_import = true;

                                // include("../postoffice/basic.php");
                                include("https://d9pkkqscj1pvg.cloudfront.net/newsletter/templates/basic.php");

                                //Bounce-List-Check
                                $bounce_check_query = $db->prepare("SELECT
                                                                            1
                                                                        FROM
                                                                            ts_database.email_bounces
                                                                        WHERE
                                                                            email LIKE :email
                                                                            AND (type='Permanent' OR (type = 'Transient' AND last_bounce > :week_ago))
                                                                        LIMIT 1
                                ");
                                $bounce_check_query->bindParam(':week_ago', $week_ago, PDO::PARAM_STR);
                                $bounce_check_query->bindParam(':email', $newsletter->email, PDO::PARAM_STR);
                                if ($bounce_check_query->execute()) {
                                    if ($bounce_check_query->rowCount() > 0) {
                                        removeAlreadyQueuedReceivers($newsletter->newsletter_email_id);
                                    } else {

                                        //Alles klar, kann rein!
                                        $check_entry_and_insert_query = $db->prepare("INSERT INTO
                                            ts_mailer.newsletter_queue(
                                                email,
                                                receiver_name,
                                                title,
                                                individual_sender,
                                                body,
                                                plain_text,
                                                newsletter_email_id,
                                                newsletter_schedule_id,
                                                newsletter_id,
                                                sender_id,
                                                send_at
                                            )
                                            VALUES(
                                                :email,
                                                :user_name,
                                                :newsletter_title,
                                                :individual_sender,
                                                :html,
                                                :plain_text,
                                                :newsletter_email_id, 
                                                :newsletter_schedule_id, 
                                                :newsletter_id,
                                                :sender_id,
                                                :send_at)
                                        ");
                                        $check_entry_and_insert_query->bindParam(':email', $newsletter->email, PDO::PARAM_STR);
                                        $check_entry_and_insert_query->bindParam(':user_name', $newsletter->user_name, PDO::PARAM_STR);
                                        $check_entry_and_insert_query->bindParam(':newsletter_title', $newsletter_title, PDO::PARAM_STR);
                                        $check_entry_and_insert_query->bindParam(':individual_sender', $individual_sender, PDO::PARAM_STR);
                                        $check_entry_and_insert_query->bindParam(':html', $html, PDO::PARAM_STR);
                                        $check_entry_and_insert_query->bindParam(':plain_text', $plain_text, PDO::PARAM_STR);
                                        $check_entry_and_insert_query->bindParam(':newsletter_email_id', $newsletter->newsletter_email_id, PDO::PARAM_INT);
                                        $check_entry_and_insert_query->bindParam(':newsletter_schedule_id', $newsletter_schedule->newsletter_schedule_id, PDO::PARAM_INT);
                                        $check_entry_and_insert_query->bindParam(':newsletter_id', $newsletter->newsletter_id, PDO::PARAM_INT);
                                        $check_entry_and_insert_query->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
                                        $check_entry_and_insert_query->bindParam(':send_at', $newsletter_schedule->send_at, PDO::PARAM_STR);
                                        if ($check_entry_and_insert_query->execute()) {
                                            removeAlreadyQueuedReceivers($newsletter->newsletter_email_id);
                                        } else {
                                            print_r($check_entry_and_insert_query->errorInfo());
                                        }
                                    }
                                } else {
                                    print_r($bounce_check_query->errorInfo());
                                }
                                $queue_counter++;
                            }
                            // echo "bearbeitet<br>";
                        } else {
                            $check_open_schedule = $db->prepare("SELECT
                                                                    1
                                                                FROM
                                                                    ts_database.newsletter_emails emails
                                                                        INNER JOIN
                                                                    ts_database.users users ON emails.user_id=users.user_id
                                                                        INNER JOIN
                                                                    ts_database.companies companies ON companies.company_id=users.company_id
                                                                WHERE
                                                                    emails.sender_id IS NOT NULL
                                                                    AND emails.email!=''
                                                                    AND users.active=1
                                                                    AND users.deactivation_datetime IS NULL
                                                                    AND emails.active=1
                                                                    AND companies.registration_type!=0
                                                                    AND emails.newsletter_region_id=:newsletter_region_id
                                                                    AND NOT EXISTS (SELECT 1 FROM ts_mailer.newsletter_queue WHERE newsletter_queue.email=emails.email AND newsletter_queue.newsletter_id=:newsletter_id LIMIT 1)
                                                                LIMIT 1
                            ");
                            $check_open_schedule->bindParam(':newsletter_region_id', $newsletter_schedule->newsletter_region_id, PDO::PARAM_INT);
                            $check_open_schedule->bindParam(':newsletter_id', $newsletter_schedule->newsletter_id, PDO::PARAM_INT);
                            if (!$check_open_schedule->execute()) {
                                print_r($check_open_schedule->errorInfo());
                            } else {
                                if ($check_open_schedule->rowCount() == 0) {
                                    $update_schedule = $db->prepare("UPDATE ts_mailer.newsletter_schedule SET in_queue=1 WHERE in_queue=0 AND permission=1 AND newsletter_schedule_id=$newsletter_schedule->newsletter_schedule_id");
                                    if (!$update_schedule->execute()) {
                                        print_r($update_schedule->errorInfo());
                                    }
                                }
                            }
                            // echo " für diesen Server abgeschlossen<br>";
                        }
                    } else {
                        print_r($receivers_query->errorInfo());
                    }
                } else {
                    // echo " übersprungen<br>";
                }
            }
        }
    }
    echo "true";
}