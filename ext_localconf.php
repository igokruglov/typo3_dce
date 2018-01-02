<?php
defined('TYPO3_MODE') or die();

// AfterSave hook (when DCE is being saved)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['dce'] =
'IgorKruglov\\IkDcefrontendediting\\Hooks\\DceAfterSave';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\FrontendEditing\\Service\\ContentEditableWrapperService'] = array(
   'className' => 'IgorKruglov\\IkDcefrontendediting\\Hooks\\FrontendEditingWrapperService'
);



