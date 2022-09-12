<?php

namespace Partners\esigcert;

use \REDCap as REDCap;
use \Records as Records;
use \Project as Project;
//use ExternalModules\FrameworkVersion\Project as Project;
use ExternalModules\ExternalModules as EM;
use \Survey as Survey;
use \System as System;
use \Files as Files;
use Sabre;
use League;

global $Proj;
//global $pdf_custom_header_text;
global $project_id;

foreach ($this->framework->getProjectsWithModuleEnabled() as $localProjectId) {
    $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
    $now = date('m.d.y h:i:s A');
    $logThis = "[$now] >>>>>>>> --- eSign Module Cron job started! ---- <<<<<<<\n";
    $logThis .= "[$now] >>> --- Project ID: $localProjectId  \n";
    $logThis .= "[$now] >>> --- Is CRON enabled for this project?: $this->getProjectSetting('enable_cron_esign', $localProjectId) ---- \n";
    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);


//    session_start();
    try {
        // ** Check on each project CRON setting is still enabled
        if ($enable_cron = $this->getProjectSetting('enable_cron_esign', $localProjectId) == 1) {

//            $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//            $now = date('m.d.y h:i:s A');
//            $logThis = "[$now] >>> --- Yes, CRON flag is enabled for this project!! \n";
//            file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

            $_GET['pid'] = $localProjectId;
//            $this->setProjectSetting('var_status_esign', 'busy', $localProjectId);
//            $pending_jobs = json_decode($this->getProjectSetting("pending_jobs_esign", $localProjectId), TRUE); // decode json list of pending jobs

//           Get the records form localProjectId
            $pending_jobs = REDCap::getData($localProjectId,
                'array',
                NULL, // All records
                array('ss_rid', 'ss_event_id', 'ss_instrument', 'ss_instance'),
                NULL, // Events
                NULL, // Groups
                FALSE, // combine_checkbox_values
                FALSE, // exportDataAccessGroups
                FALSE, // exportSurveyFields
                '[system_record_status_complete] = "0"', // Filter
                FALSE, // exportAsLabels
                FALSE // exportCsvHeadersAsLabels
            );

            // Preface settings
            $pdf_econsent_system_enabled = TRUE;
            $pdf_auto_archive = 2;
            define("PROJECT_ID", $localProjectId);
            $Proj = new Project($localProjectId);

            $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
            $now = date('m.d.y h:i:s A');
            $logThis = "[$now] >>> --- Yes, CRON flag is enabled for this project!! \n";
            $logThis .= "[$now] >>> --- localProjectID:  $localProjectId \n";
            $logThis .= "[$now] >>> ------------------------ Pending jobs\n";
            $logThis .= "[$now] >>> --- Pending jobs:" . print_r($pending_jobs, TRUE)." \n";
//            $logThis .= "[$now] >>> --- Pending jobs:" . print_r($pending_jobs, TRUE)." \n";
            file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
            // ** Begin processing of pending jobs for current project ($localProjectId)

            $configs = [];
            foreach ($pending_jobs as $record => $eid) {
                foreach($eid as $keid => $v) {
                    $configs[0] = $pending_jobs[$record][$keid]['ss_rid'];
                    $configs[1] = $pending_jobs[$record][$keid]['ss_event_id'];
                    $configs[2] = $pending_jobs[$record][$keid]['ss_instrument'];
                    $configs[3] = $pending_jobs[$record][$keid]['ss_instance'];

                    if(isset($configs[0]) && isset($configs[1]) &&
                        isset($configs[2]) && isset($configs[3])) {


                        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
                        $now = date('m.d.y h:i:s A');
                        $logThis .= "[$now] >>> ----- record: " . print_r($eid, TRUE) . "! \n";
                        $logThis .= "[$now] >>> ----- record: $configs[0]! \n";
                        $logThis .= "[$now] >>> ----- event id: $configs[1]! \n";
                        $logThis .= "[$now] >>> ----- instrument: $configs[2]! \n";
                        $logThis .= "[$now] >>> ----- instance: $configs[3]! \n";
                        $logThis .= "[$now] >>> ----- Job number: $record! \n";
                        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

                        $instrument = $configs[2];

                        $pdf_custom_header_text = REDCap::getData($localProjectId, 'array', $configs[0], $this->getProjectSetting('participant_last_name', $localProjectId))[$configs[0]][$configs[1]][$this->getProjectSetting('participant_last_name', $localProjectId)];
                        $pdf_custom_header_text .= ", " . REDCap::getData($localProjectId, 'array', $configs[0], $this->getProjectSetting('participant_first_name', $localProjectId))[$configs[0]][$configs[1]][$this->getProjectSetting('participant_first_name', $localProjectId)];
//                $pdf_custom_header_text .= "; MRN: " . REDCap::getData($localProjectId, 'array', $configs[0], $this->getProjectSetting('participant_mrn', $localProjectId))[$configs[0]][$configs[1]][$this->getProjectSetting('participant_mrn', $localProjectId)];
                        if (REDCap::getData($localProjectId, 'array', $configs[0], $this->getProjectSetting('participant_mrn', $localProjectId))[$configs[0]][$configs[1]][$this->getProjectSetting('participant_mrn', $localProjectId)] != "") {
                            $pdf_custom_header_text .= "; MRN: " . REDCap::getData($localProjectId, 'array', $configs[0], $this->getProjectSetting('participant_mrn', $localProjectId))[$configs[0]][$configs[1]][$this->getProjectSetting('participant_mrn', $localProjectId)];
                        }
                        if (REDCap::getData($localProjectId, 'array', $configs[0], $this->getProjectSetting('participant_dob', $localProjectId))[$configs[0]][$configs[1]][$this->getProjectSetting('participant_dob', $localProjectId)] != "") {
                            $pdf_custom_header_text .= "; DOB: " . REDCap::getData($localProjectId, 'array', $configs[0], $this->getProjectSetting('participant_dob', $localProjectId))[$configs[0]][$configs[1]][$this->getProjectSetting('participant_dob', $localProjectId)];
                        }

                        // Customize PDF header with this:
//                        $_GET['appendToHeader'] = $pdf_custom_header_text;

                        // Store in WebDav:
                        // Note: Survey::archiveResponseAsPDF(record, event, instrument name, instance);
                         $q= Survey::archiveResponseAsPDF($configs[0], $configs[1], $configs[2], $configs[3]);

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
                                    if($r){
                                        unset($file_contents);
                                        unlink($filename_tmp);
                                    }
                                 }
                             }
                         }

                        // Store in Vault:
//                $pdf_content = REDCap::getPDF($configs[0], $configs[2], $configs[1], $all_records = false, $repeat_instance = $configs[3],
//                    $compact_display = false, $appendToHeader = $pdf_custom_header_text, $appendToFooter = "", $hideSurveyTimestamp = false);

//                $hash = Survey::checkSurveyHash();

//                $pdf_content = getPDF_9523_mod($configs[0], $configs[2], $configs[1], $all_records = false, $repeat_instance = $configs[3],
//                    $compact_display = false, $appendToHeader = $pdf_custom_header_text, $appendToFooter = "", $hideSurveyTimestamp = false, $localProjectId);

//                $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//                $now = date('m.d.y h:i:s A');
//                $logThis = "[$now] >>> ----- pdf content:  $pdf_content  \n";
//                file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

//                        // Adding renderPDF
//                        $pathToPdfUtf8Fonts = APP_PATH_WEBTOOLS . "pdf" . DS . "font" . DS . "unifont" . DS;
//                        if (function_exists('mb_convert_encoding') && is_dir($pathToPdfUtf8Fonts)) {
//                            // Define the UTF-8 PDF fonts' path
//                            define("FPDF_FONTPATH", APP_PATH_WEBTOOLS . "pdf" . DS . "font" . DS);
//                            define("_SYSTEM_TTFONTS", APP_PATH_WEBTOOLS . "pdf" . DS . "font" . DS);
//                            // Set contant
//                            define("USE_UTF8", true);
//                            // Use tFPDF class for UTF-8 by default
//                            if ($project_encoding == 'chinese_utf8') {
//                                require_once APP_PATH_LIBRARIES . "PDF_Unicode.php";
//                            } else {
//            require_once APP_PATH_LIBRARIES . "tFPDF.php";
//                            }
//                        } else {
//                            // Set contant
//                            define("USE_UTF8", false);
//                            // Use normal FPDF class
//        require_once APP_PATH_LIBRARIES . "FPDF.php";
//                        }
//                        // If using language 'Japanese', then use MBFPDF class for multi-byte string rendering
//                        if ($project_encoding == 'japanese_sjis') {
//                            require_once APP_PATH_LIBRARIES . "MBFPDF.php"; // Japanese
//                            // Make sure mbstring is installed
//                            if (!function_exists('mb_convert_encoding')) {
//                                exit("ERROR: In order for multi-byte encoded text to render correctly in the PDF, you must have the PHP extention \"mbstring\" installed on your web server.");
//                            }
//                        }
////                        require_once APP_PATH_DOCROOT . "PDF/functions.php"; // This MUST be included AFTER we include the FPDF class
//
//                        // Check for ERIS custom library
//                        if (file_exists(APP_PATH_LIBRARIES . "load_tFPDF_ERIS.php")) {
//                            require_once APP_PATH_LIBRARIES . "load_tFPDF_ERIS.php";
//                            $logThis = "[$now] >>> ----- loading ERIS tFPDF \n";
//                            file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//                        } else {
//                            define("USE_UTF8", false);
//                            // Use normal FPDF class
//                            require_once APP_PATH_LIBRARIES . "FPDF.php";
//                            $logThis = "[$now] >>> ----- loading FPDF.php \n";
//                            file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//                        }
//
//                        require_once APP_PATH_DOCROOT . "PDF/functions.php"; // This MUST be included AFTER we include the FPDF class
//
////                require_once APP_PATH_LIBRARIES . "load_tFPDF_ERIS.php";
//
//                        $acknowledgement = "em42 test";
////                        $acknowledgement = getAcknowledgement($_GET['pid'], (isset($_GET['instrument'])) ? $_GET['instrument'] : '');
//                        $metadata2 = getMetadataForPdf_modified();
////                $metadata = $Proj->metadata;
//                        $mtdata = [];
//                        $fields = [];
//                        foreach ($Proj->metadata as $field => $v) {
//                            if ($v["form_name"] == $configs[2]) {
//                                $mtdata[$field] = $v;
//                                $fields[]= $field;
//                            }
//                        }
//                        $metadata = $mtdata;
//
//                        $data = REDCap::getData($localProjectId, 'array', array($configs[0]), NULL, NULL, array($configs[1]), FALSE, FALSE, NULL, FALSE, FALSE);
//                        ob_start();
//                        $now = date('m.d.y h:i:s A');
//                        $logThis = "[$now] >>> ----- Before rednerPDF \n";
//                        $logThis .= "[$now] >>> ----- metadata: ". print_r($metadata,TRUE). " \n";
//                        $logThis .= "[$now] >>> ----- acknowledgement: $acknowledgement \n";
//                        $logThis .= "[$now] >>> ----- title: ". ucwords(str_replace("_", " ", strip_tags(label_decode($configs[2])))) ."\n";
//                        $logThis .= "[$now] >>> ----- data: ". print_r($data,TRUE) ." \n";
//                        $logThis .= "[$now] >>> ----- library path: ". APP_PATH_LIBRARIES ." \n";
//                        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//                        renderPDF($metadata, $acknowledgement, ucwords(str_replace("_", " ", strip_tags(label_decode($configs[2])))), $data, false);
//                        $now = date('m.d.y h:i:s A');
//                        $logThis = "[$now] >>> ----- AFter RenderPDF\n";
//                        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//
//                        $pdf_content = ob_get_contents();
//                        ob_get_clean();
//                        // End of block
//
//
//                        $filename = "eConsent_" . $localProjectId . "_" . $configs[0] . "_" . date("_Y-m-d_Hi") . ".pdf";
////    $filename_with_path = "/tmp/" . $filename;
//                        $filename_with_path = APP_PATH_TEMP . $filename; // Consider creating a ternary operation and allow user to determine their temp folder location
//
//                        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//                        $now = date('m.d.y h:i:s A');
//                        $logThis = "[$now] >>> ----- file name: $filename! \n";
//                        $logThis .= "[$now] >>> ----- file name: $filename_with_path! \n";
//                        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//
//                        file_put_contents($filename_with_path, $pdf_content);
//                        // Add PDF to edocs_metadata table
//                        $pdf_file_details = array(
//                            'name' => $filename,
//                            'size' => filesize($filename_with_path),
//                            'tmp_name' => $filename_with_path,
//                        );
//                        $pdf_edoc_id = Files::uploadFile($pdf_file_details, $localProjectId);
//
//                        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//                        $now = date('m.d.y h:i:s A');
//                        $logThis = "[$now] >>> ----- edoc_id: $pdf_edoc_id! \n";
//                        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//
//                        pdfSurveyToREDCapVault2($configs[2], $pdf_edoc_id, $configs[0], $configs[1], $configs[3], $nameDobText = "", $versionText = "", $typeText = "", $filename, $pdf_content);

                        $distribution_field = $this->getProjectSetting('distribution_field', $localProjectId);
                        $esignform = $this->getProjectSetting('esignform', $localProjectId);
                        $pk = $Proj->table_pk;
//
//                        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//                        $now = date('m.d.y h:i:s A');
//                        $logThis = "[$now] >>> ----- distribution field:" . print_r($distribution_field, TRUE) . "\n";
//                        $logThis .= "[$now] >>> ----- from instrument: $instrument! \n";
//                        $logThis .= "[$now] >>> ----- pk: $pk! \n";
////                        $logThis .= "[$now] >>> ----- edoc_id: $pdf_edoc_id! \n";
//                        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//
                        if (isset($distribution_field[array_search($instrument, $esignform)])) {

                            $temp = $distribution_field[array_search($instrument, $esignform)];
                            $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
                            $now = date('m.d.y h:i:s A');
                            $logThis = "[$now] >>> ----- Distribution field is set to: $temp! \n";
                            file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

                            $target_form = $Proj->metadata[$distribution_field[array_search($instrument, $esignform)]]['form_name'];;
                            if ($doc_id != 0) {
                                $data_to_save = array(
                                    $configs[0] => array(
                                        $target_form => array(
                                            $pk => $configs[0],
                                            $distribution_field[array_search($instrument, $esignform)] => $doc_id,
                                            $target_form . "_complete" => 2)
                                    ));

                                // Import the data with REDCap::saveData
                                $response = REDCap::saveData(
                                    $localProjectId,
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
                                $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
                                $now = date('m.d.y h:i:s A');
                                $logThis = "[$now] >>> ----- Target form: " . $target_form . "\n";
                                $logThis .= "[$now] >>> ----- Upload resonse: " . print_r($response, TRUE) . "\n";
                                file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
                            }
                        }
//                        if ($pdf_edoc_id != 0) {
//                            $data_to_save = array(
//                                $configs[0] => array(
//                                    'system_record_status' => array(
//                                        $pk => $configs[0],
//                                        'ss_notes' => 'Job was completed successfully.',
//                                        'system_record_status' . "_complete" => 2)
//                                )
//                            );
//
//                            // Import the data with REDCap::saveData
//                            $response = REDCap::saveData(
//                                $localProjectId,
//                                'array', // The format of the data
//                                $data_to_save, // The Data
//                                'overwrite', // Overwrite behavior
//                                'YMD', // date format
//                                'flat', // type of the data
//                                null, // Group ID
//                                null, // data logging
//                                true, // perform auto calculations
//                                true, // commit data
//                                false, // log as auto calc
//                                true, // skip calc fields
//                                array(), // change reasons
//                                false, // return data comparison array
//                                false, // skip file upload fields - this is what we are actually updating
//                                false // remove locked fields
//                            );
////                        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
////                        $now = date('m.d.y h:i:s A');
////                        $logThis = "[$now] >>> ----- Target form: " . $target_form . "\n";
////                        $logThis .= "[$now] >>> ----- Upload resonse: " . print_r($response, TRUE) . "\n";
////                        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//                        } else {
//                            $data_to_save = array(
//                                $configs[0] => array(
//                                    'system_record_status' => array(
//                                        $pk => $configs[0],
//                                        'ss_notes' => "ERROR: Job didn't complete.",
//                                        'system_record_status' . "_complete" => 1)
//                                )
//                            );
//
//                            // Import the data with REDCap::saveData
//                            $response = REDCap::saveData(
//                                $localProjectId,
//                                'array', // The format of the data
//                                $data_to_save, // The Data
//                                'overwrite', // Overwrite behavior
//                                'YMD', // date format
//                                'flat', // type of the data
//                                null, // Group ID
//                                null, // data logging
//                                true, // perform auto calculations
//                                true, // commit data
//                                false, // log as auto calc
//                                true, // skip calc fields
//                                array(), // change reasons
//                                false, // return data comparison array
//                                false, // skip file upload fields - this is what we are actually updating
//                                false // remove locked fields
//                            );
//                        }
                    }
                }

            }

//            // ** Get buffer and assign it to pending jobs
//            $buffer = json_decode($this->getProjectSetting("buffer_esign", $localProjectId), TRUE);
//            if (isset($buffer) and sizeof($buffer) > 0) {
//                $this->setProjectSetting('pending_jobs_esign', strval($buffer), $localProjectId);
//                $this->removeProjectSetting('buffer_esign', $localProjectId);
//            } else {
//                $this->removeProjectSetting('pending_jobs_esign', $localProjectId);
//            }
//            $this->setProjectSetting('var_status_esign', 'ready', $localProjectId);

        }
    } catch (Exception $e) {
        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
        $now = date('m.d.y h:i:s A');
        $logThis = "[$now] ################TRY-CATCH-EXCEPTION######################\n";
        $logThis .= "[$now] >>> Exception: \n";
        $logThis .= "[$now] >>> --------- The cron method for this module  did not complete, but a specific cause could not be detected. \n";
        $logThis .= "[$now] #########################################################\n";
        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
        return false;
    }
//    session_destroy();
}

//}

// Use this for confirming PDF archive was successful.
//getPdfAutoArchiveFiles(&$Proj, $group_id=null, $doc_id=null)

function pdfSurveyToREDCapVault2($instrument, $pdf_edoc_id, $record, $event_id, $repeat_instance, $nameDobText, $versionText, $typeText, $filename, $file_contents)
{

    $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
    $now = date('m.d.y h:i:s A');
    $logThis = "[$now] >>> ----- Hello from inside the pdfSurveyToREDCapVault2! \n";
    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

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

        $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
        $now = date('m.d.y h:i:s A');
        $logThis = "[$now] >>> Response from vault:" . print_r($response, TRUE) . "\n";
        file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);

        return $response;
    } // ERROR
    catch (Exception $e) {
        return false;
    }
}

//function getPDF_9523_mod($record=null, $instrument=null, $event_id=null, $all_records=false, $repeat_instance=1,
//                              $compact_display=false, $appendToHeader="", $appendToFooter="", $hideSurveyTimestamp=false, $local_project_id)
//{
//    global $Proj, $table_pk, $table_pk_label, $longitudinal, $custom_record_label, $surveys_enabled,
//           $salt, $__SALT__, $user_rights, $lang, $ProjMetadata, $ProjForms, $project_encoding;
//
////    $object = new REDCap();
////    $reflector = ReflectionObject($object);
////    $method = $reflector->getMethod('checkProjectContext');
////    $method->setAccessible(TRUE);
////    $method->invoke($object, __METHOD__);
//
//    $fileLocation = APP_PATH_TEMP . "/eSignModLog.txt";
//    $now = date('m.d.y h:i:s A');
//    $logThis = "[$now] >>> --- Hello from inside getPDF Mod function ---- \n";
//    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//
//    // Make sure we are in the Project context
////    REDCap::checkProjectContext(__METHOD__);
//    // If a longitudinal project and no event_id is provided, then manually set to null
//    if ($longitudinal && $record != null && $event_id != null && !isset($Proj->eventInfo[$event_id])) {
//        exit("ERROR: Event ID \"$event_id\" is not a valid event_id for this project!");
//        // If a non-longitudinal project, then set event_id automatically
//    } elseif (!$longitudinal) {
//        $event_id = $Proj->firstEventId;
//    }
//    // If instrument is not null and does not exist, then return error
//    if ($instrument != null && !isset($Proj->forms[$instrument])) {
//        exit("ERROR: \"$instrument\" is not a valid unique instrument name for this project!");
//    }
//    // If record is not null and does not exist, then return error
//    if ($record != null && !Records::recordExists($local_project_id, $record)) {
//        exit("ERROR: \"$record\" is not an existing record in this project!");
//    }
//    // Capture original $_GET params since we're manipulating them here in order to use the existing PDF script
//    $get_orig = $_GET;
//    $logThis = "[$now] >>> --- This is _GET before: ". print_r($_GET,TRUE) ."  ---- \n";
//    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//    unset($_GET['s']);
////    $_GET['s'] = $hash;
//    $_GET['__noLogPDFSave'] = 1;
//    // Set export rights to max to ensure PDF exports fully
//    $export_rights_orig = $user_rights['data_export_tool'];
//    $user_rights['data_export_tool'] = '1';
//    // Append text to header/footer?
//    if ($appendToHeader != "") $_GET['appendToHeader'] = $appendToHeader;
//    if ($appendToFooter != "") $_GET['appendToFooter'] = $appendToFooter;
//    if ($hideSurveyTimestamp)  $_GET['hideSurveyTimestamp'] = 1;
//    // Compact display
//    if ($compact_display) $_GET['compact'] = 1;
//    // Set event_id
//    if (is_numeric($event_id)) {
//        $_GET['event_id'] = $event_id;
//    }
//    // Output PDF of all forms (ALL records)
//    if ($all_records) {
//        $_GET['allrecords'] = '1';
//        $_GET['page'] = null;
//    }
//    // Output PDF of single form (blank)
//    elseif ($instrument != null && $record == null) {
//        $_GET['page'] = $instrument;
//    }
//    // Output PDF of single form (single record's data)
//    elseif ($instrument != null && $record != null) {
//        $_GET['id'] = $record;
//        $_GET['page'] = $instrument;
//        $_GET['instance'] = $repeat_instance;
//    }
//    // Output PDF of all forms (blank)
//    elseif ($instrument == null && $record == null) {
//        $_GET['all'] = '1';
//        $_GET['page'] = null;
//    }
//    // Output PDF of all forms (single record's data)
//    elseif ($instrument == null && $record != null) {
//        $_GET['id'] = $record;
//        $_GET['page'] = null;
//        $_GET['instance'] = $repeat_instance;
//    }
//
//    $project_id = $local_project_id;
//
//    $logThis = "[$now] >>> --- This is _GET after: ". print_r($_GET,TRUE) ."  ---- \n";
//    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//
//    $logThis = "[$now] >>> --- just before ob_start  ---- \n";
//    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//    // Capture PDF output using output buffer
//    ob_start();
//    // Output PDF to buffer
//    include APP_PATH_DOCROOT . "PDF/index.php";
//    $logThis = "[$now] >>> --- after PDF/index.php  ---- \n";
//    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//    // Reset $_GET params
//    $_GET = $get_orig;
//    $user_rights['data_export_tool'] = $export_rights_orig;
//    // Obtain PDF content from buffer and return it
//    $logThis = "[$now] >>> --- after file put content  ---- \n";
//    file_put_contents($fileLocation, $logThis, FILE_APPEND | LOCK_EX);
//    return ob_get_clean();
//
//}

//function getMetadataForPdf_modified() {
//    global $project_id, $Proj, $table_pk;
//
//    // replicating metadata read from PDF/index.php
//    // Save fields into metadata array
//    $draftMode = false;
//    if (isset($_GET['instrument'])) {
//        // Check if we should get metadata for draft mode or not
//        $draftMode = ($status > 0 && isset($_GET['draftmode']));
//        $metadata_table = ($draftMode) ? "redcap_metadata_temp" : "redcap_metadata";
//        // Make sure form exists first
//        if ((!$draftMode && !isset($Proj->forms[$_GET['instrument']])) || ($draftMode && !isset($Proj->forms_temp[$_GET['instrument']]))) {
//            exit('ERROR!');
//        }
//        $Query = "select * from $metadata_table where project_id = $project_id and ((form_name = '{$_GET['instrument']}'
//                                          and field_name != concat(form_name,'_complete')) or field_name = '$table_pk') order by field_order";
//    } else {
//        $Query = "select * from redcap_metadata where project_id = $project_id and
//                                          (field_name != concat(form_name,'_complete') or field_name = '$table_pk') order by field_order";
//    }
//    $QQuery = db_query($Query);
//    $metadata = array();
//    while ($row = db_fetch_assoc($QQuery))
//    {
//        // If field is an "sql" field type, then retrieve enum from query result
//        if ($row['element_type'] == "sql") {
//            $row['element_enum'] = getSqlFieldEnum($row['element_enum'], PROJECT_ID, $_GET['id'], $_GET['event_id'], $_GET['instance'], null, null, $_GET['instrument']);
//        }
//        // If PK field...
//        if ($row['field_name'] == $table_pk) {
//            // Ensure PK field is a text field
//            $row['element_type'] = 'text';
//            // When pulling a single form other than the first form, change PK form_name to prevent it being on its own page
//            if (isset($_GET['instrument'])) {
//                $row['form_name'] = $_GET['instrument'];
//            }
//        }
//        // Store metadata in array
//        $metadata[] = $row;
//    }
//
//
//    // now tweaking the element label and value labels for annotation purposes
//    $annotated = array();
//    foreach ($metadata as $fld => $fieldattr) {
//        $annotation = '';
//
//        if ($fieldattr['element_type']!=='descriptive') {
//            $type = ($fieldattr['element_type']==='select') ? 'dropdown' : $fieldattr['element_type'];
//            $valtype = (!is_null($fieldattr['element_validation_type']) && $fieldattr['element_validation_type']==='int') ? 'integer' : $fieldattr['element_validation_type'];
//
//            $annotation = PHP_EOL.'{['.$fieldattr['field_name'].'] '.$type;
//            if (!is_null($valtype)) { $annotation .= ' '.$valtype; }
//            $annotation .= '}';
//        }
//
//        if (!is_null($fieldattr['branching_logic'])) { $annotation .= ' '.PHP_EOL.'{Branching logic (show if): '.$fieldattr['branching_logic'].'}'; }
//
//        $fieldattr['element_label'] .= ' '.$annotation;
//
//        if (!is_null($fieldattr['element_enum'])) {
//            $choicesannotated = array();
//            $choices = explode('\n', $fieldattr['element_enum']);
//            foreach ($choices as $thischoice) {
//                $vl = explode(', ', $thischoice, 2);
//                $v = trim($vl[0]);
//                $l = trim($vl[1]);
//                $choicesannotated[] = $v.', {'.$v.'} '.$l.' ';
//            }
//            $fieldattr['element_enum'] = implode('\n', $choicesannotated);
//        }
//
//        $annotated[$fld] = $fieldattr;
//    }
//
//    return $annotated;
//}

?>