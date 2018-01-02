<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('ik_dcefrontendediting', 'Configuration/TypoScript', 'Additions to frontend editing');

    }
);
