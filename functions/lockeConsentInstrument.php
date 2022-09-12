<?php

namespace Partners\esigcert;
use \REDCap as REDCap;
use ExternalModules\ExternalModules as EM;

global $Proj;

if (is_numeric(array_search($instrument,$this->getProjectSetting('esignform'))) && array_search($instrument,$this->getProjectSetting('esignform'))>= 0 )
{
    $fields_in_form = [];
    $q_field = [];
    foreach($Proj->metadata as $field => $property)
    {
        if($property["form_name"] == $instrument)
        {
            $fields_in_form[$field] = $field;
        }
    }

    foreach ($fields_in_form as $k => $v)
    {
        if($v != "record_id" && $v != $instrument."_complete")
        {
            $q_field[$k] = $v;
        }
    }

    $survey_timestamp = REDCap::getData($project_id, 'array', $record, array_values($q_field),
        NULL, NULL, FALSE, FALSE, TRUE, NULL, FALSE, FALSE)[$record][$event_id][$instrument."_timestamp"];

    if($survey_timestamp != "[not completed]" && strlen($survey_timestamp) > 0){
        $survey_timestamp = date( "m/d/Y g:ia", strtotime( $survey_timestamp ) );
        $app_version = explode("/",$_SERVER['PHP_SELF'])[2];
//    $lock_eConsent = "<div class= \\\"darkgreen\\\" id=\\\"form_response_header\\\" style=\\\"display: block;\\\"><!-- Survey response is not editable -->					<img src=\\\"/redcap/$app_version/Resources/images/lock.png\\\">					<b style=\\\"color:#A00000;\\\">Survey response is read-only because it was completed via the e-Consent Framework.</b>                    <span style=\\\"color:#A00000;\\\">However, because you have 'Lock/Unlock Records' privileges, you will still be able to lock this form at the bottom.</span>				&nbsp;&nbsp;<span>	<button id=\\\"SurveyActionDropDown\\\" onclick=\\\"showBtnDropdownList(this,event,'SurveyActionDropDownDiv');return false;\\\" class=\\\"jqbuttonmed ui-button ui-corner-all ui-widget ui-button-disabled ui-state-disabled\\\" disabled=\\\"\\\">		<img src=\\\"/redcap/$app_version/Resources/images/blog_arrow.png\\\" style=\\\"vertical-align:middle;position:relative;top:-1px;margin-left:2px;\\\">		<span style=\\\"vertical-align:middle;margin-right:40px;\\\">Survey options</span><img src=\\\"/redcap/$app_version/Resources/images/arrow_state_grey_expanded.png\\\" style=\\\"margin-left:2px;vertical-align:middle;position:relative;top:-1px;\\\">	</button>	<div id=\\\"SurveyActionDropDownDiv\\\" style=\\\"display:none;position:absolute;z-index:1000;\\\">		<ul id=\\\"SurveyActionDropDownUl\\\" role=\\\"menu\\\" tabindex=\\\"0\\\" class=\\\"ui-menu ui-widget ui-widget-content\\\">			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-openSurvey\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; color: green; cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><img src=\\\"/redcap/$app_version/Resources/images/arrow_right_curve.png\\\">				Open survey</a>			</li>			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-openSurvey\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><span class=\\\"fas fa-sign-out-alt\\\" style=\\\"margin-left:2px;\\\"></span><span style=\\\"margin-left:4px;\\\">Log out</span><span style=\\\"margin:0 2px 0 5px;\\\">+</span><span style=\\\"color:green;\\\">					<img src=\\\"/redcap/$app_version/Resources/images/arrow_right_curve.png\\\">					Open survey				</span></a>			</li>			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-composeInvite\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><img src=\\\"/redcap/$app_version/Resources/images/email.png\\\">				Compose survey invitation</a>			</li>			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-accessCode\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; color: rgb(0, 0, 0); cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><img src=\\\"/redcap/$app_version/Resources/images/ticket_arrow.png\\\">				Survey Access Code&nbsp;and<br><img src=\\\"/redcap/$app_version/Resources/images/qrcode.png\\\">				QR Code</a>			</li>		</ul>	</div></span>				<br><br>				<img src=\\\"/redcap/$app_version/Resources/images/circle_green_tick.png\\\"> <b>Response was completed on {$survey_timestamp}</b>.  Survey responses are not able to be edited once a participant has completed a survey. They are read-only.<div style=\\\"padding:10px 0 0;color:#000066;\\\">							Record ID <b>$_GET[id]</b></div>			</div>";
        $lock_eConsent = "<div class= \\\"darkgreen\\\" id=\\\"form_response_header\\\" style=\\\"display: block;\\\"><!-- Survey response is not editable -->					<img src=\\\"/redcap/$app_version/Resources/images/lock.png\\\">					<b style=\\\"color:#A00000;\\\">Survey response is read-only because it was completed via the e-Consent Framework.</b>                    &nbsp;&nbsp;<span>	<button id=\\\"SurveyActionDropDown\\\" onclick=\\\"showBtnDropdownList(this,event,'SurveyActionDropDownDiv');return false;\\\" class=\\\"jqbuttonmed ui-button ui-corner-all ui-widget ui-button-disabled ui-state-disabled\\\" disabled=\\\"\\\">		<img src=\\\"/redcap/$app_version/Resources/images/blog_arrow.png\\\" style=\\\"vertical-align:middle;position:relative;top:-1px;margin-left:2px;\\\">		<span style=\\\"vertical-align:middle;margin-right:40px;\\\">Survey options</span><img src=\\\"/redcap/$app_version/Resources/images/arrow_state_grey_expanded.png\\\" style=\\\"margin-left:2px;vertical-align:middle;position:relative;top:-1px;\\\">	</button>	<div id=\\\"SurveyActionDropDownDiv\\\" style=\\\"display:none;position:absolute;z-index:1000;\\\">		<ul id=\\\"SurveyActionDropDownUl\\\" role=\\\"menu\\\" tabindex=\\\"0\\\" class=\\\"ui-menu ui-widget ui-widget-content\\\">			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-openSurvey\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; color: green; cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><img src=\\\"/redcap/$app_version/Resources/images/arrow_right_curve.png\\\">				Open survey</a>			</li>			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-openSurvey\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><span class=\\\"fas fa-sign-out-alt\\\" style=\\\"margin-left:2px;\\\"></span><span style=\\\"margin-left:4px;\\\">Log out</span><span style=\\\"margin:0 2px 0 5px;\\\">+</span><span style=\\\"color:green;\\\">					<img src=\\\"/redcap/$app_version/Resources/images/arrow_right_curve.png\\\">					Open survey				</span></a>			</li>			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-composeInvite\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><img src=\\\"/redcap/$app_version/Resources/images/email.png\\\">				Compose survey invitation</a>			</li>			<li class=\\\"ui-menu-item\\\">				<a id=\\\"surveyoption-accessCode\\\" href=\\\"javascript:;\\\" style=\\\"display: block; text-decoration: none; color: rgb(0, 0, 0); cursor: default;\\\" onclick=\\\"\\\" tabindex=\\\"-1\\\" role=\\\"menuitem\\\" class=\\\"ui-menu-item-wrapper opacity35\\\"><img src=\\\"/redcap/$app_version/Resources/images/ticket_arrow.png\\\">				Survey Access Code&nbsp;and<br><img src=\\\"/redcap/$app_version/Resources/images/qrcode.png\\\">				QR Code</a>			</li>		</ul>	</div></span>				<br><br>				<img src=\\\"/redcap/$app_version/Resources/images/circle_green_tick.png\\\"> <b>Response was completed on {$survey_timestamp}</b>.  Survey responses are not able to be edited once a participant has completed a survey. They are read-only.<div style=\\\"padding:10px 0 0;color:#000066;\\\">							Record ID <b>$_GET[id]</b></div>			</div>";

        $script = <<<SCRIPT
<script type="text/javascript"> 
                    $(document).ready(function() {
                        $("#form_response_header").hide();
                        $("#dataEntryTopOptions").after("$lock_eConsent");
                        });
            </script>
SCRIPT;
        print $script;

    }
}
?>