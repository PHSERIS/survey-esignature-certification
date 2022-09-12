<?php

namespace Partners\esigcert;
use \REDCap as REDCap;
use ExternalModules\ExternalModules as EM;
global $Proj;

// Getting user-define inputs
$esignform = $this->getProjectSetting('esignform');
//$enable_esignature = $this->getProjectSetting('enable_esignature');
$indexSetting = array_search($instrument, $esignform);
if (strlen($indexSetting) >= 1 ) {

    // ** Settings default values
    if($this->getProjectSetting('attestation-language')[0] != NULL){

        // Language selected for this instrument setting
        $languageSelected = $this->getProjectSetting('attestation-language')[$indexSetting];
        // Now... map it to the system setting and get its corresponding system-wide values
        $systemIndex = array_search($languageSelected, $this->getSystemSetting('sw-lan-name'));

        /***
         * These default values are the values set by the REDCap admin and are meant
         * to reflect the institution's suggested language.
         */
        // Get the respective system wide language
        $inst_submit = $this->getSystemSetting('sw-submit-btn')[$systemIndex];
        $cert_title = $this->getSystemSetting('sw-attestation-title')[$systemIndex];
        $cert_language = $this->getSystemSetting('sw-attestation-lang')[$systemIndex];
        $cert_review_text = $this->getSystemSetting('sw-attestation_review_text')[$systemIndex];
        $cert_review_btn = $this->getSystemSetting('sw-attestation_review_button')[$systemIndex];
        $cert_submit_btn = $this->getSystemSetting('sw-attestation_submit_button')[$systemIndex];

    } else {
        /***
         * Add a check for backward compatibility:
         * - if the module is not configured in a project, but it has configurations from an old version:
         * --   then, use the old configuration settings until it is configured with the new configuration version
         */
        // use old default values
        $inst_submit = "Submit";
        $cert_title = "Electronic Signature";
        $cert_language = "I certify that all the information entered is correct. I understand that clicking 'Submit' will electronically sign the form and that signing this form electronically is the equivalent of signing a physical document.";
        $cert_review_text = "To review or modify your signatures, click 'Review'.";
        $cert_review_btn = "Review";
        $cert_submit_btn = "Submit";
    }


    // ** Getting user-defined settings
    if($this->getProjectSetting('attestation-language')[$indexSetting] == 'usr_defined'){
        $inst_submit = $this->getProjectSetting('instrument_submit_btn');
        $cert_title = $this->getProjectSetting('attestation_title');
        $cert_language = $this->getProjectSetting('attestation_lang');
        $cert_review_text = $this->getProjectSetting('attestation_review_text');
        $cert_review_btn = $this->getProjectSetting('attestation_review_button');
        $cert_submit_btn = $this->getProjectSetting('attestation_submit_button');
    }

    // check to see if the language setting is set to MLM controlled
    if($languageSelected == 'mlm_c'){
        // if so, then generate the respective element for each language
//        $this->createMLMModal();
        list($inst_submit,$cert_title,$cert_language,$cert_review_text,$cert_review_btn,$cert_submit_btn,$mlm_langs) = $this->createAttestationLanguage();
        $this->createMLMModal($inst_submit,$cert_title,$cert_language,$cert_review_text,$cert_review_btn,$cert_submit_btn,$mlm_langs);
    } else {
        // do simple modal
        $this->createSimpleLanguageModal($inst_submit, $cert_title, $cert_language, $cert_review_text, $cert_review_btn, $cert_submit_btn);
    }
}
?>