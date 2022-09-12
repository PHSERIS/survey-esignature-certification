<?php

namespace Partners\esigcert;

// respond to POST ONLY
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  return exit('[]');
}

use \REDCap as REDCap;
use \Records as Records;
use \Project as Project;
use \Survey as Survey;
use \System as System;
use \Files as Files;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules as EM;
use Sabre;
use League;
use \Logging as Logging;

// Get the PID
if ( !isset($_GET['pid']) || !is_numeric($_GET['pid']) ) exit('[]');
$pid = trim(strip_tags(html_entity_decode($_GET['pid'], ENT_QUOTES)));

/**
 * GET the rest of the parameters you need
 */

global $Proj;

// Preface settings
//$pdf_econsent_system_enabled = TRUE;
//$pdf_auto_archive = 2;

//$Proj = new Project($pid);
if ( $Proj )
    define("PROJECT_ID", $pid);


// GET the Record ID from the post
$configs = array();
if ( !isset($_POST['ss_rid']) || strlen($_POST['ss_rid']) <= 0 ) exit('[1-no record id]');
$configs[0] = trim(strip_tags(html_entity_decode($_POST['ss_rid'], ENT_QUOTES)));

if ( !isset($_POST['ss_event_id']) || !is_numeric($_POST['ss_event_id']) ) exit('[2]');
$configs[1] = trim(strip_tags(html_entity_decode($_POST['ss_event_id'], ENT_QUOTES)));

if ( !isset($_POST['ss_instrument']) || strlen($_POST['ss_instrument']) <= 0 ) exit('[3]');
$configs[2] = trim(strip_tags(html_entity_decode($_POST['ss_instrument'], ENT_QUOTES)));

if ( !isset($_POST['ss_instance']) || !is_numeric($_POST['ss_instance']) ) exit('[4]');
$configs[3] = trim(strip_tags(html_entity_decode($_POST['ss_instance'], ENT_QUOTES)));

           
/**
 * DO everything else you need to here
 */
function pdfSurveyToREDCapVault2($instrument, $pdf_edoc_id, $record, $event_id, $repeat_instance, $nameDobText, $versionText, $typeText, $filename, $file_contents)
{

//    $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//    $now = date('m.d.y h:i:s A');
//    $logThis = "[$now] >>> ----- Hello from inside the pdfSurveyToREDCapVault2! \n";
//    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

    global $Proj, $pdf_econsent_system_enabled, $pdf_auto_archive, $pdf_econsent_system_ip, $lang;
//    global $Proj, $pdf_econsent_system_enabled, $pdf_auto_archive, $pdf_econsent_system_ip, $lang, $pdf_custom_header_text;
    // Get survey_id from form
//    $survey_id = $Proj->forms[$instrument]['survey_id'];
//    // Add values to redcap_surveys_pdf_archive table
//    $ip = $pdf_econsent_system_ip ? System::clientIpAddress() : "";
//    $sql = "insert into redcap_surveys_pdf_archive (doc_id, record, event_id, survey_id, instance, identifier, version, type, ip) values
//				($pdf_edoc_id, '" . db_escape($record) . "', '" . db_escape($event_id) . "', '" . db_escape($survey_id) . "', '" . db_escape($repeat_instance) . "',
//				" . checkNull($nameDobText) . ", " . checkNull($versionText) . ", " . checkNull($typeText) . ", " . checkNull($ip) . ")";
//    $q = db_query($sql);

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

//        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//        $now = date('m.d.y h:i:s A');
//        $logThis = "[$now] >>> Response from vault:" . print_r($response, TRUE) . "\n";
//        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

        return $response;
    } // ERROR
    catch (Exception $e) {
        return false;
    }
}

// Store in WebDav:
// Note: Survey::archiveResponseAsPDF(record, event, instrument name, instance);
$q= Survey::archiveResponseAsPDF($configs[0], $configs[1], $configs[2], $configs[3]);
// print "<pre>";
// var_dump($q);
// print "</pre>";
if($q){
    $p_files = Survey::getPdfAutoArchiveFiles($Proj, $group_id=null, $doc_id=null);
    foreach($p_files as $row){
        // Get survey_id from form
        $survey_id = $Proj->forms[$configs[2]]['survey_id'];
        if($row['record'] == $configs[0] && $row['event_id'] == $configs[1] &&
            $row['survey_id'] == $survey_id && $row['instance'] == $configs[3]){
            $doc_id = $row['doc_id'];
            $filename_tmp = Files::copyEdocToTemp($doc_id, $prependHashToFilename=false, $prependTimestampToFilename=TRUE);
            $file_contents = file_get_contents($filename_tmp);
            $r = pdfSurveyToREDCapVault2($configs[2], $doc_id, $configs[0], $configs[1], $configs[3], $nameDobText="", $versionText="", $typeText="", $row['doc_name'], $file_contents);
            $esignform = $module->getProjectSetting('esignform', $pid);
            if($r){
                unset($file_contents);
                unlink($filename_tmp);
                $custom_identifier_o = REDCap::getData($pid, 'array',$configs[0], $module->getProjectSetting('custom_identifier')[array_search($configs[2], $esignform)])[$configs[0]][$configs[1]][$module->getProjectSetting('custom_identifier')[array_search($configs[2], $esignform)]];
                $custom_identifier_o = $custom_identifier_o !="" ? $custom_identifier_o : "[survey respondent]";
//                $custom_identifier_o = REDCap::getData($project_id, 'array',$record, $this->getProjectSetting('custom_identifier')[array_search($instrument, $esignform)])[$record][$event_id][$this->getProjectSetting('custom_identifier')[array_search($instrument, $esignform)]];
                Logging::logEvent(NULL, "", "OTHER", $row['record'],
                    "e-Consent document \"". $row['doc_name'] ."\" has been saved to vault.",
                    "e-Consent Certification",
                    "",
                    $custom_identifier_o,
                    $project_id_override="", $useNOW=true, $event_id_override=null, $instance=null, $bulkProcessing=false);
            } else {
                return exit("[no return from pdfSurveytoREDCapVault2]");
            }
            $distribution_field = $module->getProjectSetting('distribution_field', $pid);
            $pk = $Proj->table_pk;

            if (isset($distribution_field[array_search($configs[2], $esignform)])) {

                $temp = $distribution_field[array_search($configs[2], $esignform)];
                $target_form = $Proj->metadata[$distribution_field[array_search($configs[2], $esignform)]]['form_name'];;
                if ($doc_id != 0) {
                    $data_to_save = array(
                        $configs[0] => array(
                            $target_form => array(
                                $pk => $configs[0],
                                $distribution_field[array_search($configs[2], $esignform)] => $doc_id,
                                $target_form . "_complete" => 2)
                        ));

                    // Import the data with REDCap::saveData
                    define("USERID", "SYSTEM");
                    $response = REDCap::saveData(
                        $pid,
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
        }
    }
}



// In the end - you can print something or not - up to you
$result = [
    'status' => 'ERROR',
    'status_message' => 'ERROR - please try again!',
    'record' => '',
    'event' => '',
    'survey' => '',
  ];
// print json_encode($result); // print the JSON

exit();