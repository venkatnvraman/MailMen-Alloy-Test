<?php
set_time_limit(60);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Started.";
    include("../include/db_connect.php");
    include("../include/general_functions.php");
    require_once '../include/swiftmailer/lib/swift_required.php';

    /* WICHTIG: SenderID und Limit festlegen */
    $sender_id = 10;
    $limit = 150;

    $now = date("Y-m-d H:i:s");
    $today = date("Y-m-d");

    $cloudfront_domain = "https://d9pkkqscj1pvg.cloudfront.net";

    //Swift-Mailer Einstellungen
    $configs = include('../include/config.php');
    $transport = Swift_SmtpTransport::newInstance($configs['mail-host'], $configs['mail-port'], $configs['mail-encryption']);
    $transport->setUsername($configs['mail-username']);
    $transport->setPassword($configs['mail-password']);
    $swift = Swift_Mailer::newInstance($transport);

    //TODO: Abfrage Eventalarm (newsletter.type = 1)

    $queue_query = $db->prepare("SELECT
                                    queue.*
                                FROM
                                    ts_mailer.newsletter_queue queue
                                WHERE
                                    queue.send_at LIKE '$today%'
                                    AND queue.send_at <= '$now'
                                    AND queue.sending_started is NULL
                                    AND queue.sender_id=$sender_id
                                LIMIT $limit
                             ");
    //AND schedule.permission=1
    if ($queue_query->execute()) {
        if ($queue_query->rowCount() > 0) {
            while ($queue = $queue_query->fetch(PDO::FETCH_OBJ)) {
                $now = date("Y-m-d H:i:s");
                $update_start_query = $db->prepare("
                                                      UPDATE
                                                          ts_mailer.newsletter_queue
                                                      SET
                                                          sending_started='$now'
                                                      WHERE
                                                          ts_mailer.newsletter_queue.newsletter_queue_id=$queue->newsletter_queue_id
                                                      LIMIT 1
                                                      ");
                if ($update_start_query->execute()) {
                    /* Versenden START */
                    $subject = html_entity_decode($queue->title);
                    $from = array('discover@benefits.me' => $queue->individual_sender);
                    $to = array(
                        $queue->email => $queue->receiver_name //TODO: Namen in Warteschlange!
                    );

                    $message = new Swift_Message($subject);
                    $message->setFrom($from);
                    $message->setBody($queue->body, 'text/html');
                    $message->addPart($queue->plain_text, 'text/plain');
                    //TODO: Plain text
                    $message->setTo($to);
                    //Senden
                    if ($swift->send($message, $failures)) {
                        // Senden erfolgreich -> Eintrag aus Warteschlange entfernen
                        $delete_queue_entry_query = $db->prepare("
                                                                DELETE FROM
                                                                    ts_mailer.newsletter_queue
                                                                WHERE
                                                                    ts_mailer.newsletter_queue.newsletter_queue_id=$queue->newsletter_queue_id
                                                                LIMIT 1
                                                                ");
                        if ($delete_queue_entry_query->execute()) {
                            $update_newsletter_queue_query = $db->prepare("
                                                                        UPDATE
                                                                            ts_mailer.newsletter_schedule schedule
                                                                        SET
                                                                            schedule.emails_sent=(schedule.emails_sent + 1)
                                                                        WHERE
                                                                            schedule.newsletter_schedule_id=$queue->newsletter_schedule_id
                                                                        LIMIT 1
                                                                      ");
                            if ($update_newsletter_queue_query->execute()) {
                                echo "true";
                            } else {
                                print_r($update_newsletter_queue_query->errorInfo());
                            }
                        } else {
                            print_r($update_newsletter_queue_query->errorInfo());
                        }
                    } else {
                        // Senden fehlgeschlagen -> Eintrag in Warteschlange als gesendet und fehlerhaft markieren
                        $update_queue_entry_query = $db->prepare("
                                                                UPDATE
                                                                    ts_mailer.newsletter_queue
                                                                SET
                                                                    failure='1'
                                                                WHERE
                                                                    ts_mailer.newsletter_queue.newsletter_queue_id=$queue->newsletter_queue_id
                                                                LIMIT 1
                                                                ");
                        if ($update_queue_entry_query->execute()) {
                            echo "true";
                        } else {
                            print_r($update_newsletter_queue_query->errorInfo());
                        }
                    }
                    /* Versenden END */
                } else {
                    print_r($update_start_query->errorInfo());
                }
            }
        } else {
            //Keine Einträge mehr da -> Überprüfen, ob Versand beendet wurde
            $check_queues_query = $db->prepare("
                                              SELECT
                                                  newsletters.newsletter_id,
                                                  COUNT(DISTINCT queue.newsletter_queue_id) as 'pending'
                                              FROM
                                                  ts_database.newsletter newsletters,
                                                  ts_mailer.newsletter_queue queue
                                              WHERE
                                                  newsletters.sending_started LIKE '$today%'
                                                  AND newsletters.newsletter_id = queue.newsletter_id
                                                  AND queue.failure = 0
                                                  AND newsletters.sending_finished = '0000-00-00 00:00:00'
                                              GROUP BY
                                                  newsletters.newsletter_id
                                              HAVING
                                                  pending = 0
                                              ");
            if ($check_queues_query->execute()) {
                if ($check_queues_query->rowCount() > 0) {
                    while ($newsletter = $check_queues_query->fetch(PDO::FETCH_OBJ)) {
                        $finish = date('Y-m-d H:i:s');
                        $update_newsletter_query = $db->prepare("
                                                                UPDATE
                                                                    ts_database.newsletter
                                                                SET
                                                                    sending_finished='$finish'
                                                                WHERE
                                                                    newsletter_id=$newsletter->newsletter_id
                                                                LIMIT 1
                                                                ");
                        if ($update_newsletter_query->execute()) {
                            echo "sending_finished updated";
                        }
                    }
                }
            } else {
                print_r($check_queues_query->errorInfo());
            }
        }
    } else {
        print_r($queue_query->errorInfo());
    }
}
