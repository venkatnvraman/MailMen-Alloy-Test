<?php
function auto_version($file)
{
    if (strpos($file, '/') !== 0 || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file))
        return $file;

    $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
    return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $file);
}

function get_subdomain(){
    $subdomain = array_shift((explode(".",$_SERVER['HTTP_HOST'])));
    if($subdomain != "www"){
        return $subdomain;
    } else {
        return false;
    }
}

function format_date($date)
{
    $old = explode(".", $date);
    $new = "$old[2]-$old[1]-$old[0]";
    return $new;
}

function format_date_back($date)
{
    $old = explode("-", $date);
    $new = "$old[2].$old[1].$old[0]";
    return $new;
}

function format_time($time)
{
    $old = explode(":", $time);
    $new = "$old[0]:$old[1]";
    return $new;
}

function format_date_time($date_time)
{
    $old = explode(" ", $date_time);
    $date = $old[0];
    $time = $old[1];
    $old_date = explode("-", $date);
    $old_time = explode(":", $time);
    $new = "$old_date[2].$old_date[1].$old_date[0] $old_time[0]:$old_time[1] Uhr";
    return $new;
}


function format_currency($currency)
{
    $old = explode(".", $currency);
    if (!isset($old[1]))
        return "$old[0],00";
    else if (strlen($old[1]) == 1)
        return "$old[0],$old[1]0";
    else
        return "$old[0],$old[1]";
}

function showErrorPage($subdomain, $location)
{
    return '<script>window.location="/not_found/' . $location . '"</script>';
}

function inactiveEvent()
{
    return '<script>$("#modalBackground").css("background-color","rgba(0,0,0,0.97)");$("#modal_content").html(\'Dieses Event ist derzeit inaktiv. Bitte geben Sie das Schlüsselwort ein, um es sich anzusehen.<br><br><input type="password" id="inactive_password"/><p id="wrong_password" style="color:red; display:none;">Falsches Schlüsselwort</p><br><br><a class="button" href="javascript:checkInactivePassword()">Prüfen</a>\');$(\'#close_button\').hide();openModal();</script>';
}

function sortmulti($array, $index, $order, $natsort = FALSE, $case_sensitive = FALSE)
{
    if (is_array($array) && count($array) > 0) {
        foreach (array_keys($array) as $key)
            $temp[$key] = $array[$key][$index];
        if (!$natsort) {
            if ($order == 'asc')
                asort($temp);
            else
                arsort($temp);
        } else {
            if ($case_sensitive === true)
                natsort($temp);
            else
                natcasesort($temp);
            if ($order != 'asc')
                $temp = array_reverse($temp, TRUE);
        }
        foreach (array_keys($temp) as $key)
            if (is_numeric($key))
                $sorted[] = $array[$key];
            else
                $sorted[$key] = $array[$key];
        return $sorted;
    }
    return $sorted;
}

function getDayShort($date)
{
    $trans = array(
        'Monday' => 'Montag',
        'Tuesday' => 'Dienstag',
        'Wednesday' => 'Mittwoch',
        'Thursday' => 'Donnerstag',
        'Friday' => 'Freitag',
        'Saturday' => 'Samstag',
        'Sunday' => 'Sonntag',
        'Mon' => 'Mo',
        'Tue' => 'Di',
        'Wed' => 'Mi',
        'Thu' => 'Do',
        'Fri' => 'Fr',
        'Sat' => 'Sa',
        'Sun' => 'So',
        'January' => 'Januar',
        'February' => 'Februar',
        'March' => 'März',
        'May' => 'Mai',
        'June' => 'Juni',
        'July' => 'Juli',
        'October' => 'Oktober',
        'December' => 'Dezember'
    );

    $d_obj = strtotime($date);
    $day = strtr(date("D", $d_obj), $trans);
    return $day;
}


/******************************************************************************
 * Copyright (c) 2010 Jevon Wright and others.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Jevon Wright - initial API and implementation
 ****************************************************************************/

/**
 * Tries to convert the given HTML into a plain text format - best suited for
 * e-mail display, etc.
 *
 * <p>In particular, it tries to maintain the following features:
 * <ul>
 *   <li>Links are maintained, with the 'href' copied over
 *   <li>Information in the &lt;head&gt; is lost
 * </ul>
 *
 * @param html the input HTML
 * @return the HTML converted, as best as possible, to text
 */
function convert_html_to_text($html)
{
    $html = fix_newlines($html);

    $doc = new DOMDocument();
    if (!$doc->loadHTML($html))
        throw new Html2TextException("Could not load HTML - badly formed?", $html);

    $output = iterate_over_node($doc);

    // remove leading and trailing spaces on each line
    $output = preg_replace("/[ \t]*\n[ \t]*/im", "\n", $output);

    // remove leading and trailing whitespace
    $output = trim($output);

    return $output;
}

/**
 * Unify newlines; in particular, \r\n becomes \n, and
 * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
 * all become \ns.
 *
 * @param text text with any number of \r, \r\n and \n combinations
 * @return the fixed text
 */
function fix_newlines($text)
{
    // replace \r\n to \n
    $text = str_replace("\r\n", "\n", $text);
    // remove \rs
    $text = str_replace("\r", "\n", $text);

    return $text;
}

function next_child_name($node)
{
    // get the next child
    $nextNode = $node->nextSibling;
    while ($nextNode != null) {
        if ($nextNode instanceof DOMElement) {
            break;
        }
        $nextNode = $nextNode->nextSibling;
    }
    $nextName = null;
    if ($nextNode instanceof DOMElement && $nextNode != null) {
        $nextName = strtolower($nextNode->nodeName);
    }

    return $nextName;
}

function prev_child_name($node)
{
    // get the previous child
    $nextNode = $node->previousSibling;
    while ($nextNode != null) {
        if ($nextNode instanceof DOMElement) {
            break;
        }
        $nextNode = $nextNode->previousSibling;
    }
    $nextName = null;
    if ($nextNode instanceof DOMElement && $nextNode != null) {
        $nextName = strtolower($nextNode->nodeName);
    }

    return $nextName;
}

function iterate_over_node($node)
{
    if ($node instanceof DOMText) {
        return preg_replace("/\\s+/im", " ", $node->wholeText);
    }
    if ($node instanceof DOMDocumentType) {
        // ignore
        return "";
    }

    $nextName = next_child_name($node);
    $prevName = prev_child_name($node);

    $name = strtolower($node->nodeName);

    // start whitespace
    switch ($name) {
        case "hr":
            return "------\n";

        case "style":
        case "head":
        case "title":
        case "meta":
        case "script":
            // ignore these tags
            return "";

        case "h1":
        case "h2":
        case "h3":
        case "h4":
        case "h5":
        case "h6":
            // add two newlines
            $output = "\n";
            break;

        case "p":
        case "div":
            // add one line
            $output = "\n";
            break;

        default:
            // print out contents of unknown tags
            $output = "";
            break;
    }

    // debug
    //$output .= "[$name,$nextName]";

    for ($i = 0; $i < $node->childNodes->length; $i++) {
        $n = $node->childNodes->item($i);

        $text = iterate_over_node($n);

        $output .= $text;
    }

    // end whitespace
    switch ($name) {
        case "style":
        case "head":
        case "title":
        case "meta":
        case "script":
            // ignore these tags
            return "";

        case "h1":
        case "h2":
        case "h3":
        case "h4":
        case "h5":
        case "h6":
            $output .= "\n";
            break;

        case "p":
        case "br":
            // add one line
            if ($nextName != "div")
                $output .= "\n";
            break;

        case "div":
            // add one line only if the next child isn't a div
            if ($nextName != "div" && $nextName != null)
                $output .= "\n";
            break;

        case "a":
            // links are returned in [text](link) format
            $href = $node->getAttribute("href");
            if ($href == null) {
                // it doesn't link anywhere
                if ($node->getAttribute("name") != null) {
                    $output = "[$output]";
                }
            } else {
                if ($href == $output) {
                    // link to the same address: just use link
                    $output;
                } else {
                    // replace it
                    $output = "[$output]($href)";
                }
            }

            // does the next node require additional whitespace?
            switch ($nextName) {
                case "h1":
                case "h2":
                case "h3":
                case "h4":
                case "h5":
                case "h6":
                    $output .= "\n";
                    break;
            }

        default:
            // do nothing
    }

    return $output;
}

class Html2TextException extends Exception
{
    var $more_info;

    public function __construct($message = "", $more_info = "")
    {
        parent::__construct($message);
        $this->more_info = $more_info;
    }
}

/**
 * Class IncentPUIDDecoder
 * Handles PUIDs from Incent SSO
 */

class IncentPUIDDecoder
{
    const KEY = 'UDJ0ekpyMFMwZnNUSUZjRm5BaDltU01j';
    /**
     * Decodes, extracts and verifies PUID received from Incent SSO
     * 
     * @param $puid
     * @return array
     * @throws \Exception
     */
    public static function decodeData($puid)
    {
        $puid = base64_decode($puid);
        $data = explode('.', $puid);
        if (count($data) < 3) {
            return false;
        }
        $userID = $data[0];
        $subdomain = $data[1];
        $givenHash = $data[2];
        $expectedHash = hash('sha256', sprintf("%s.%s.%s", $userID, $subdomain, self::KEY));
        if ($givenHash != $expectedHash) {
            return false;
        } else {
            return array(
                'userID' => $userID,
                'subdomain' => $subdomain
            );
        }
    }
}

//Domain zu Cloudfront Storage
$cloudfront_domain = "https://d9pkkqscj1pvg.cloudfront.net";

function encrypt_decrypt($action, $string) {
    $output = false;

    $encrypt_method = "AES-256-CBC";
    $secret_key = 'PutzerEvent';
    $secret_iv = '7a 33 ed 02 29 99 29 eb fe 1e 96 09 11 da b6 35';

    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if( $action == 'decrypt' ){
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

function clear_reservations(){
    include("db_connect.php");
    //Suche reservierte Tickets
    $now = date("Y-m-d H:i:s");
    $update_prices_query = $db->prepare("
                                        UPDATE
                                            prices,
                                            bookingprices,
                                            bookings
                                        SET
                                            prices.contingent = (prices.contingent + bookingprices.number),
                                            bookingprices.expires = '0000-00-00 00:00:00'
                                        WHERE
                                            bookingprices.expires != '0000-00-00 00:00:00'
                                            AND bookingprices.expires < '$now'
                                            AND bookingprices.booking_id=bookings.booking_id
                                            AND bookings.canceled=1
                                            AND bookings.aborted=1
                                            AND prices.price_id=bookingprices.price_id
                                            AND prices.contingent > -1
                                        ");
    if($update_prices_query->execute()){
        return true;
    } else {
        print_r($update_prices_query->errorInfo());
    }
}

function delete_payment_data(){
    include("db_connect.php");
    //Suche reservierte Tickets
    $now = date("Y-m-d H:i:s");
    $delete_payment_data_query = $db->prepare("
                                        DELETE FROM
                                            payment_data
                                        WHERE
                                            expires != '0000-00-00 00:00:00'
                                            AND expires < '$now'
                                        ");
    if($delete_payment_data_query->execute()){
        return true;
    } else {
        print_r($delete_payment_data_query->errorInfo());
    }
}

function send_newsletter_mail($db,$sender_id,$i,$limit){
    $now = date("Y-m-d H:i:s");
    $today = date("Y-m-d");
    
    $cloudfront_domain = "https://d9pkkqscj1pvg.cloudfront.net";
    
    if($i <= $limit){
        $newsletter_query = $db->prepare("
                                        LOCK TABLES ts_mailer.newsletter_queue WRITE;
                                        SELECT
                                            queue.newsletter_queue_id,
                                            newsletters.newsletter_id,
                                            newsletters.cannot_read_text,
                                            newsletters.title,
                                            newsletters.type as 'type',
                                            companies.company_id,
                                            companies.name as 'company',
                                            companies.use_hyphen,
                                            companies.subdomain,
                                            companies.logo_url,
                                            companies.color_basic,
                                            companies.registration_type,
                                            emails.newsletter_email_id,
                                            emails.email,
                                            CONCAT(users.first_name,' ',users.last_name) as 'user_name',
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
                                            END) as 'registration_code',
                                            schedule.newsletter_schedule_id,
                                            regions.cr_id as 'group_id'
                                        FROM
                                            ts_mailer.newsletter_queue queue,
                                            ts_mailer.newsletter_schedule schedule,
                                            ts_database.newsletter_emails emails
                                                LEFT JOIN ts_database.regions regions ON regions.newsletter_region_id=emails.newsletter_region_id AND regions.newsletter_main_region=1,
                                            ts_database.newsletter newsletters,
                                            ts_database.users users,
                                            ts_database.companies companies
                                        WHERE
                                            queue.sending_started='0000-00-00 00:00:00'
                                            AND queue.sending_finished='0000-00-00 00:00:00'
                                            AND queue.newsletter_email_id=emails.newsletter_email_id
                                            AND queue.newsletter_id=newsletters.newsletter_id
                                            AND schedule.newsletter_id=queue.newsletter_id
                                            AND schedule.send_at <= '$now'
                                            AND schedule.send_at LIKE '$today%'
                                            AND schedule.permission=1
                                            AND queue.sender_id=$sender_id
                                            AND emails.user_id=users.user_id
                                            AND companies.company_id=users.company_id
                                        GROUP BY
                                            queue.newsletter_queue_id
                                        LIMIT 1
                                        UNLOCK TABLES;
                                     ");
        if($newsletter_query->execute()){
            if($newsletter_query->rowCount()>0){
                $newsletter = $newsletter_query->fetch(PDO::FETCH_OBJ);

                //Empfänger "blocken"
                $start = date("Y-m-d H:i:s");
                $update_newsletter_queue_query = $db->prepare("
                                                                UPDATE
                                                                    ts_mailer.newsletter_queue queue
                                                                SET
                                                                    queue.sending_started='$start'
                                                                WHERE
                                                                    queue.newsletter_queue_id=$newsletter->newsletter_queue_id
                                                                LIMIT 1
                                                              ");
                if($update_newsletter_queue_query->execute()){
                    //Link zur Messung von Newsletteröffnungen
                    $metrics_open_str = base64_encode("open-$newsletter->newsletter_id-$newsletter->newsletter_email_id");

                    $error = "";

                    include("https://d9pkkqscj1pvg.cloudfront.net/newsletter/templates/basic.php");

                    /* Versenden START */

                    //Swift-Mailer Einstellungen
                    $transport = Swift_SmtpTransport::newInstance("email-smtp.eu-west-1.amazonaws.com", 587, "tls");
                    $transport->setUsername('AKIAJN74ETSDEXXOY6LQ');
                    $transport->setPassword('AqH5XR3+siWmZSUTgD4U0ST4SH3IK3/1Mdl2ImrNC6DG');
                    $swift = Swift_Mailer::newInstance($transport);

                    $subject = html_entity_decode($newsletter_title);
                    $from = array('entdecke@mitarbeiteraktionen.de' => 'Mitarbeiteraktionen Newsletter');
                    $to = array(
                        $newsletter->email => $newsletter->user_name
                    );

                    $message = new Swift_Message($subject);
                    $message->setFrom($from);
                    $message->setBody($html, 'text/html');
                    //TODO: Plain text
                    $message->setTo($to);

                    if ($recipients = $swift->send($message, $failures)) {
                        $error = "";
                        //Email als versendet markieren
                        $finish = date("Y-m-d H:i:s");

                        $update_newsletter_queue_query = $db->prepare("
                                                                    UPDATE
                                                                        ts_mailer.newsletter_queue queue
                                                                    SET
                                                                        queue.sending_finished='$finish'
                                                                    WHERE
                                                                        queue.newsletter_queue_id=$newsletter->newsletter_queue_id
                                                                    LIMIT 1
                                                                  ");
                        if($update_newsletter_queue_query->execute()){
                            $update_schedule_queue = $db->prepare("
                                                                    UPDATE
                                                                        ts_mailer.newsletter_schedule schedule
                                                                    SET
                                                                        schedule.emails_sent=(schedule.emails_sent + 1)
                                                                    WHERE
                                                                        schedule.newsletter_schedule_id=$newsletter->newsletter_schedule_id
                                                                    LIMIT 1
                                                                  ");
                            if($update_schedule_queue->execute()){
                                send_newsletter_mail($db,$sender_id,($i+1),$limit);
                            } else {
                                print_r($update_schedule_queue->errorInfo());
                            }
                        } else {
                            print_r($update_newsletter_queue_query->errorInfo());
                        }
                    } else {
                        //TODO: Error speichern
                        return false;
                    }
                    /* Versenden END */
                } else {
                    print_r($update_newsletter_queue_query->errorInfo());
                }
            } else {
                //Ende TODO: in newsletter_schedule markieren?
                return false;
            }
        } else {
            print_r($newsletter_query->errorInfo());
        }
    }
}

function get_top_user_interests($user_id){
    $db = $GLOBALS['db'];
    $user_interests_query = $db->prepare("SELECT
                                                GROUP_CONCAT(tag_id SEPARATOR ',') AS array
                                            FROM (SELECT
                                                    tag_id
                                                FROM
                                                    user_interests
                                                WHERE
                                                    user_id=:user_id
                                                ORDER BY
                                                    rating DESC
                                                LIMIT 20
                                            ) results
    ");
    $user_interests_query->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if($user_interests_query->execute()){
        if($user_interests_query->rowCount()>0){
            $user_interests = $user_interests_query->fetch(PDO::FETCH_OBJ);
            if($user_interests->array != ''){
                return $user_interests->array;
            } else {
                return 'null';
            }
        } else {
            return 'null';
        }
    } else {
        print_r($user_interests_query->errorInfo());
    }
}
?>