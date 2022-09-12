<?php

namespace Partners\esigcert;
use \REDCap as REDCap;
use \Survey as Survey;
use \Logging as Logging;
use \Files as Files;
use \System  as System;
use Sabre;
use League;
global $pdf_custom_header_text;
global $Proj;

$esignform = $this->getProjectSetting('esignform');
//$enable_esignature = $this->getProjectSetting('enable_esignature');
$enable_logging = $this->getProjectSetting('enable_logging');
$enable_survey_archive = $this->getProjectSetting('enable_survey_archive');
$enable_cron = $this->getProjectSetting('enable_cron_esign');

$distribution_field = $this->getProjectSetting('distribution_field');

$form_complete = REDCap::getData($project_id, 'array',$record, $instrument . "_complete")[$record][$event_id][$instrument."_complete"];
//print "<pre>";
//var_dump($form_complete);
//print "</pre>";
$pk = $Proj->table_pk;
//$form_name = $Proj->metadata[$keys[0]]["form_name"];

// Check attestation popup and logging are enabled
if (strpos($_SERVER['REQUEST_URI'], '/surveys/') !== false
    && strlen(array_search($instrument, $esignform)) >= 1
//    && $enable_esignature[array_search($instrument, $esignform)] == TRUE
    && $enable_logging[array_search($instrument, $esignform)] == TRUE
    && $form_complete == 2) {

    $custom_identifier_o = REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('custom_identifier')[array_search($instrument, $esignform)])[$record][$event_id][$this->getProjectSetting('custom_identifier')[array_search($instrument, $esignform)]];
//    if ($custom_identifier !=""){
//        $_SESSION['username'] = $custom_identifier;
//    }
//    $_SESSION['username'] = $custom_identifier;
    $custom_identifier = $custom_identifier_o !="" ? ", identifier = $custom_identifier_o" : " [survey respondent]";
    $cert_language = "I certify that all the information entered is correct. I understand that clicking 'Submit' will electronically sign the form and that signing this form electronically is the equivalent of signing a physical document.";
    $attestation_setting = $this->getProjectSetting('attestation_setting');
    $attestation_lang = $this->getProjectSetting('attestation_lang');
    $cert_language_log = $attestation_setting == 1 ? "\nElectronic Signature Certification: \"".$attestation_lang."\"" : "\nElectronic Signature Certification: \"".$cert_language."\"";

//    REDCap::logEvent("e-Consent Certification", "e-Consent certification was obtained for: \nrecord_id = $record". $custom_identifier . $cert_language_log, null, $record, $event_id, $project_id);
    Logging::logEvent(NULL, "", "OTHER", $record,
        "e-Consent certification was obtained for: \nrecord_id = $record ". $custom_identifier . $cert_language_log,
        "e-Consent Certification","", $custom_identifier_o,
        $project_id_override="", $useNOW=true, $event_id_override=null, $instance=null, $bulkProcessing=false);
}

// Check PDF survey archive is enabled and Cron is disabled
if (strpos($_SERVER['REQUEST_URI'], '/surveys/') !== false
    && strlen(array_search($instrument, $esignform)) >= 1
    && $enable_survey_archive[array_search($instrument, $esignform)] == TRUE
    && $enable_cron == 0
    && $form_complete == 2) {

//    $fileLocation =  APP_PATH_TEMP . "/ServerSideEsign.txt";
//    $now = date('m.d.y h:i:s A');
//    $logThis = "[$now] >>> Hello from addLog file!!! \n";
//    file_put_contents($fileLocation, $logThis,FILE_APPEND | LOCK_EX);

    $pdf_econsent_system_enabled = TRUE;
    $pdf_auto_archive = 2;
    $compact_display=false;

    $pdf_custom_header_text = REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('participant_last_name'))[$record][$event_id][$this->getProjectSetting('participant_last_name')];
    $pdf_custom_header_text .= ", " . REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('participant_first_name'))[$record][$event_id][$this->getProjectSetting('participant_first_name')];
//    $pdf_custom_header_text .= "; MRN: " . REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('participant_mrn'))[$record][$event_id][$this->getProjectSetting('participant_mrn')];
    if (REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('participant_mrn'))[$record][$event_id][$this->getProjectSetting('participant_mrn')] != ""){
        $pdf_custom_header_text .= "; MRN: " . REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('participant_mrn'))[$record][$event_id][$this->getProjectSetting('participant_mrn')];
    }
    if (REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('participant_dob'))[$record][$event_id][$this->getProjectSetting('participant_dob')] != ""){
        $pdf_custom_header_text .= "; DOB: " . REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('participant_dob'))[$record][$event_id][$this->getProjectSetting('participant_dob')];
    }

//    $pdf_custom_header_text2 = $Proj->pdf_custom_header_text;
    // For WebDav:
//    Survey::archiveResponseAsPDF($record, $_GET['event_id'], $_GET['page'], $_GET['instance']);

    $pdf_content = REDCap::getPDF($record, $instrument, $event_id, $all_records = false, $repeat_instance = $repeat_instance,
        $compact_display = false, $appendToHeader = $pdf_custom_header_text, $appendToFooter = "", $hideSurveyTimestamp = false);

    $filename = "eConsent_" . $project_id . "_" . $record . "_" . date("_Y-m-d_Hi") . ".pdf";
//    $filename_with_path = "/tmp/" . $filename;
    $filename_with_path = APP_PATH_TEMP . $filename; // Consider creating a ternary operation and allow user to determine their temp folder location

    file_put_contents($filename_with_path, $pdf_content);
    // Add PDF to edocs_metadata table
    $pdf_file_details = array(
        'name' => $filename,
        'size' => filesize($filename_with_path),
        'tmp_name' => $filename_with_path,
    );
    $pdf_edoc_id = Files::uploadFile($pdf_file_details);

//    if ($pdf_edoc_id == 0) return false;

    $response = pdfSurveyToREDCapVault($instrument,$pdf_edoc_id, $record, $event_id, $repeat_instance,
        $nameDobText="", $versionText="", $typeText="", $filename, $pdf_content);

    if(isset($distribution_field[array_search($instrument, $esignform)])) {
        $target_form = $Proj->metadata[$distribution_field[array_search($instrument, $esignform)]]['form_name'];;
        if ($pdf_edoc_id != 0) {
            $data_to_save = array(
                $record => array(
                    $target_form => array(
                        $pk => $record,
                        $distribution_field[array_search($instrument, $esignform)] => $pdf_edoc_id,
                        $target_form . "_complete" => 2)
                ));

            // Import the data with REDCap::saveData
            $response = REDCap::saveData(
                $project_id,
                'array', // The format of the data
                $data_to_save, // The Data
                'overwrite', // Overwrite behavior
                'YMD', // date format
                'flat', // type of the data
                null, // Group ID
                null, // data logging
                true, // perform auto calculations
                true, // commit data
                false, // log as auto calc
                true, // skip calc fields
                array(), // change reasons
                false, // return data comparison array
                false, // skip file upload fields - this is what we are actually updating
                false // remove locked fields
            );
        }
    }
//    $fileLocation =  APP_PATH_TEMP . "/ExtModLogs_eSign.txt";
//    $now = date('m.d.y h:i:s A');
//    $logThis = "[$now] >>> $response \n";
////    file_put_contents($fileLocation, $logThis,FILE_APPEND | LOCK_EX);
//    file_put_contents($fileLocation, $logThis);
    unlink($filename);
}

// Check PDF survey archive is enabled and Cron is enabled -> Server Side PDF Generator
if (strpos($_SERVER['REQUEST_URI'], '/surveys/') !== false
    && strlen(array_search($instrument, $esignform)) >= 1
    && $enable_survey_archive[array_search($instrument, $esignform)] == TRUE
    && $enable_cron == 1
    && $form_complete == 2) {

    $url = $this->getUrl('esig_service.php')."&NOAUTH&pid=". $project_id;
    $params = array(
//        'pid' => $project_id,
        'ss_rid' => $record,
        'ss_event_id' => $_GET['event_id'],
        'ss_instrument' => $_GET['page'],
        'ss_instance' => $_GET['instance']); // all params go here
//    http_post($url_to_call, $params, 2);

    $param_string = http_build_query($params, '', '&');

    $curlpost = curl_init();
    curl_setopt($curlpost, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curlpost, CURLOPT_VERBOSE, 0);
    curl_setopt($curlpost, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curlpost, CURLOPT_AUTOREFERER, true);
    curl_setopt($curlpost, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curlpost, CURLOPT_URL, $url);
    curl_setopt($curlpost, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($curlpost, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curlpost, CURLOPT_POSTFIELDS, $param_string);
    if (!sameHostUrl($url)) {
        curl_setopt($curlpost, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
        curl_setopt($curlpost, CURLOPT_PROXYUSERPWD, PROXY_USERNAME_PASSWORD); // If using a proxy
    }
    curl_setopt($curlpost, CURLOPT_FRESH_CONNECT, 1); // Don't use a cached version of the url
    $timeout = 1;
    if (is_numeric($timeout)) {
        curl_setopt($curlpost, CURLOPT_CONNECTTIMEOUT, $timeout); // Set timeout time in seconds
        curl_setopt($curlpost, CURLOPT_TIMEOUT, $timeout);
    }
//    // If using basic authentication = base64_encode(username:password)
//    if ($basic_auth_user_pass != "") {
//        curl_setopt($curlpost, CURLOPT_USERPWD, $basic_auth_user_pass);
//        // curl_setopt($curlpost, CURLOPT_HTTPHEADER, array("Authorization: Basic ".$basic_auth_user_pass));
//    }
//    // If not sending as x-www-form-urlencoded, then set special header
//    if ($content_type != 'application/x-www-form-urlencoded') {
//        curl_setopt($curlpost, CURLOPT_HTTPHEADER, array("Content-Type: $content_type", "Content-Length: " . strlen($param_string)));
//    }
    // If passing headers manually, then add then
    if (!empty($headers) && is_array($headers)) {
        curl_setopt($curlpost, CURLOPT_HTTPHEADER, $headers);
    }
    // Make the call
    $response = curl_exec($curlpost);
    $info = curl_getinfo($curlpost);
    curl_close($curlpost);




    /////////////////////////////////
//    $data_to_save = array(
//        $record => array(
//            'system_record_status' => array(
//                $pk => $record,
//                'ss_rid' => $record,
//                'ss_event_id' => $_GET['event_id'],
//                'ss_instrument' => $_GET['page'],
//                'ss_instance' => $_GET['instance'],
//                $instrument . "_complete" => 0)
//        )
//    );

//    // Import the data with REDCap::saveData
//    $response = REDCap::saveData(
//        $project_id,
//        'array', // The format of the data
//        $data_to_save, // The Data
//        'overwrite', // Overwrite behavior
//        'YMD', // date format
//        'flat', // type of the data
//        null, // Group ID
//        null, // data logging
//        true, // perform auto calculations
//        true, // commit data
//        false, // log as auto calc
//        true, // skip calc fields
//        array(), // change reasons
//        false, // return data comparison array
//        false, // skip file upload fields - this is what we are actually updating
//        false // remove locked fields
//    );

}

function pdfSurveyToREDCapVault($instrument,$pdf_edoc_id, $record, $event_id, $repeat_instance, $nameDobText, $versionText, $typeText, $filename, $file_contents) {

    global $Proj, $pdf_econsent_system_enabled, $pdf_auto_archive, $pdf_econsent_system_ip, $lang, $pdf_custom_header_text;
    // Get survey_id from form
    $survey_id = $Proj->forms[$instrument]['survey_id'];
    // Add values to redcap_surveys_pdf_archive table
    $ip = $pdf_econsent_system_ip ? System::clientIpAddress() : "";
    $sql = "insert into redcap_surveys_pdf_archive (doc_id, record, event_id, survey_id, instance, identifier, version, type, ip) values
				($pdf_edoc_id, '".db_escape($record)."', '".db_escape($event_id)."', '".db_escape($survey_id)."', '".db_escape($repeat_instance)."', 
				".checkNull($nameDobText).", ".checkNull($versionText).", ".checkNull($typeText).", ".checkNull($ip).")";
    $q = db_query($sql);

    try {
        // Add slash to end of root path
        $pathLastChar = substr($GLOBALS['pdf_econsent_filesystem_path'], -1);
        if ($pathLastChar != "/" && $pathLastChar != "\\") {
            $GLOBALS['pdf_econsent_filesystem_path'] .= "/";
        }

        $settings = array(
            'baseUri' => $GLOBALS['pdf_econsent_filesystem_host'],
            'userName' => $GLOBALS['pdf_econsent_filesystem_username'],
            'password' => $GLOBALS['pdf_econsent_filesystem_password']
        );
        $client = new Sabre\DAV\Client($settings);
        $adapter = new League\Flysystem\WebDAV\WebDAVAdapter($client, $GLOBALS['pdf_econsent_filesystem_path']);
        // Instantiate the filesystem
        $filesystem = new League\Flysystem\Filesystem($adapter);
        // Write the file
        $response = $filesystem->write($filename, $file_contents);
        // Return boolean regarding success
        return $response;
    }
        // ERROR
    catch (Exception $e)
    {
        return false;
    }
}

?>