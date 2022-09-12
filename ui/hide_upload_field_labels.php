<?php
//© 2020 Partners HealthCare System, Inc. / Mass General Brigham
//All Rights Reserved.
namespace Partners\esigcert;
global $Proj;

$target_field_array = $this->getProjectSetting('distribution_field');

foreach ($target_field_array as $k => $v) {
    if($Proj->metadata[$v]['form_name'] == $instrument){
        $target_field = $v;
            $script = <<<SCRIPT
	<style type='text/css'>
		 #$target_field-linknew {
		 visibility: hidden;
		 }
	</style>
SCRIPT;

        print $script;
    }
}

?>