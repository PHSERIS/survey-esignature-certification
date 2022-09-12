<?php
namespace Partners\esigcert;
use \REDCap as REDCap;
use MultiLanguageManagement\MultiLanguage;
use REDCap\Context;


include_once dirname(__FILE__)."/classes/common.php";
Global $Proj;

class esigcert extends \ExternalModules\AbstractExternalModule
{
    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        $main_ulr =  __DIR__.'/functions/main.php';
        if(!@include($main_ulr));
        $main_ulr =  __DIR__.'/ui/hide_upload_field_labels.php';
        if(!@include($main_ulr));
    }

    function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        $main_ulr =  __DIR__.'/functions/addLog.php';
        if(!@include($main_ulr));
    }

    function hook_every_page_top($project_id)
	{
        if(PAGE == 'FileRepository/index.php') {
            $flag = TRUE;
            foreach($this->getPDFAutoArchiveSettings($project_id) as $k=>$v){
                if($v != 0){
                    $flag = FALSE;
                }
            }
            if($flag){
                $main_ulr =  __DIR__.'/ui/ui_enhancements.php';
                if(!@include($main_ulr));
            }
        }
	}

    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
    {
        $main_ulr =  __DIR__.'/functions/lockeConsentInstrument.php';
            if(!@include($main_ulr));
        $main_ulr =  __DIR__.'/ui/hide_upload_field_labels.php';
        if(!@include($main_ulr));
    }

	function server_side_pdf_generator()
    {
        $main_ulr =  __DIR__.'/functions/server_side_pdf_generator.php';
        if(!@include($main_ulr));
    }

    function createSimpleLanguageModal($inst_submit,$cert_title,$cert_language,$cert_review_text,$cert_review_btn,$cert_submit_btn)
    {
        $modal = "<!-- Button trigger modal -->
<div class=\"text-center\">
<button type=\"button\" class=\"btn btn-primary align-self-center\" data-toggle=\"modal\" data-target=\"#exampleModalCenter\">
  {$inst_submit}
</button>

<!-- Modal -->
<div class=\"modal fade\" id=\"exampleModalCenter\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"exampleModalCenterTitle\" aria-hidden=\"true\">
  <div class=\"modal-dialog modal-dialog-centered\" role=\"document\">
    <div class=\"modal-content\">
      <div class=\"modal-header\">
        <h5 class=\"modal-title\" id=\"exampleModalLongTitle1\">{$cert_title}</h5>        
        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
          <span aria-hidden=\"true\">&times;</span>
        </button>
      </div>
      <div class=\"modal-body\" style='font-size: 14px;'>
      {$cert_language}
       </div>
      <div  class=\"modal-footer\">
      </div>
      <div class=\"row\">
        
        <div class=\"modal-body\" style='font-size: 14px;'>
        {$cert_review_text}
        </div>
        </div>
        <div class=\"text-center\">
        <div class=\"row\" style='margin-bottom: 15px;'>
        <div class=\"col-sm\"><button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">{$cert_review_btn}</button></div>
        <div class=\"col-sm\"></div>
        <div class=\"col-sm\"><button type=\"button\" class=\"btn btn-primary\" onclick=\"dataEntrySubmit(this);return false;\">{$cert_submit_btn}</button></div>
        </div>
        </div>
      
    </div>
  </div>
</div>
</div>
";

        print $modal;

        $script = <<<SCRIPT
            <script type="text/javascript">
                    $(window).on("load", function () {
                         $('button[name=submit-btn-saverecord]').remove();                         
                        });
            </script>
SCRIPT;
        print $script;

    }

    function createAttestationLanguage(){
        // Set the institution wide languages configured in the module's Control Center configuration window
        foreach($this->getSubSettings('set-language') as $k=>$v){
            $inst_submit .= "<span class='{$v['sw-lan-name']}' style='display: none'>{$v['sw-submit-btn']}</span>";
            $cert_title .= "<span class='{$v['sw-lan-name']}' style='display: none'>{$v['sw-attestation-title']}</span>";
            $cert_language .= "<span class='{$v['sw-lan-name']}' style='display: none'>{$v['sw-attestation-lang']}</span>";
            $cert_review_text .= "<span class='{$v['sw-lan-name']}' style='display: none'>{$v['sw-attestation_review_text']}</span>";
            $cert_review_btn .= "<span class='{$v['sw-lan-name']}' style='display: none'>{$v['sw-attestation_review_button']}</span>";
            $cert_submit_btn .= "<span class='{$v['sw-lan-name']}' style='display: none'>{$v['sw-attestation_submit_button']}</span>";
            if($k >0){
                $mlm_langs .= ", '{$v['sw-lan-name']}'";
            } else {
                $mlm_langs .= "'{$v['sw-lan-name']}'";
            }

        }

        // check for user defined settings
        if($this->getProjectSetting('attestation_setting') == '2'){
            $inst_submit .= "<span class='{$this->getProjectSetting('user-define-language-id')}' style='display: none'>{$this->getProjectSetting('instrument_submit_btn')}</span>";
            $cert_title .= "<span class='{$this->getProjectSetting('user-define-language-id')}' style='display: none'>{$this->getProjectSetting('attestation_title')}</span>";
            $cert_language .= "<span class='{$this->getProjectSetting('user-define-language-id')}' style='display: none'>{$this->getProjectSetting('attestation_lang')}</span>";
            $cert_review_text .= "<span class='{$this->getProjectSetting('user-define-language-id')}' style='display: none'>{$this->getProjectSetting('attestation_review_text')}</span>";
            $cert_review_btn .= "<span class='{$this->getProjectSetting('user-define-language-id')}' style='display: none'>{$this->getProjectSetting('attestation_review_button')}</span>";
            $cert_submit_btn .= "<span class='{$this->getProjectSetting('user-define-language-id')}' style='display: none'>{$this->getProjectSetting('attestation_submit_button')}</span>";
            $mlm_langs .= ", '$this->getProjectSetting('user-define-language-id')'";
        }

        // create output array
        $definedLangs = array($inst_submit,
            $cert_title,
            $cert_language,
            $cert_review_text,
            $cert_review_btn,
            $cert_submit_btn);

        return $definedLangs;
    }

    function createMLMModal($inst_submit,$cert_title,$cert_language,$cert_review_text,$cert_review_btn,$cert_submit_btn,$mlm_langs){

        $modal = "<!-- Button trigger modal -->
<div class=\"text-center\">
<button type=\"button\" class=\"btn btn-primary align-self-center\" data-toggle=\"modal\" data-target=\"#exampleModalCenter\">
  {$inst_submit}
</button>

<!-- Modal -->
<div class=\"modal fade\" id=\"exampleModalCenter\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"exampleModalCenterTitle\" aria-hidden=\"true\">
  <div class=\"modal-dialog modal-dialog-centered\" role=\"document\">
    <div class=\"modal-content\">
      <div class=\"modal-header\">
        <h5 class=\"modal-title\" id=\"exampleModalLongTitle1\">{$cert_title}</h5>        
        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
          <span aria-hidden=\"true\">&times;</span>
        </button>
      </div>
      <div class=\"modal-body\" style='font-size: 14px;'>
      {$cert_language}
       </div>
      <div  class=\"modal-footer\">
      </div>
      <div class=\"row\">
        
        <div class=\"modal-body\" style='font-size: 14px;'>
        {$cert_review_text}
        </div>
        </div>
        <div class=\"text-center\">
        <div class=\"row\" style='margin-bottom: 15px;'>
        <div class=\"col-sm\"><button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">{$cert_review_btn}</button></div>
        <div class=\"col-sm\"></div>
        <div class=\"col-sm\"><button type=\"button\" class=\"btn btn-primary\" onclick=\"dataEntrySubmit(this);return false;\">{$cert_submit_btn}</button></div>
        </div>
        </div>
      
    </div>
  </div>
</div>
</div>
";
        print $modal;

        $script = <<<SCRIPT
            <script type="text/javascript">
                    $(window).on("load", function () {
                         $('button[name=submit-btn-saverecord]').remove();                        
                        
                         var langs = [{$mlm_langs}];
                         var current_lan = langs[0];
                         
                         setInterval(function() {                             
                             if(current_lan == getCookie('redcap-multilanguage-survey')){                                     
                                     current_lan = getCookie('redcap-multilanguage-survey');                                 
                                     $('.' + current_lan).css("display", "block"); 
                             }    
                             if(current_lan != getCookie('redcap-multilanguage-survey')){
                                     // Hide old lang                         
                                     $('.' + current_lan).css("display", "none");
                                     // then show current lang on the cookie
                                     current_lan = getCookie('redcap-multilanguage-survey');                                 
                                     $('.' + current_lan).css("display", "block"); 
                             }
                         }, 250);
                        });
                    
                    function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
            </script>
SCRIPT;
        print $script;

    }

    function getPDFAutoArchiveSettings($project_id){
        $query = $this->createQuery();
        $query->add('
   select pdf_auto_archive
    from redcap_surveys
    where
      project_id = ?
', $project_id);
        $result = $query->execute();

        while($row = $result->fetch_assoc()){
            $pdfAutoArchiveSetting[] = $row['pdf_auto_archive'];
        }
        return $pdfAutoArchiveSetting;
    }
}
