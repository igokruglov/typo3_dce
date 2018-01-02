<?php

namespace IgorKruglov\IkDcefrontendediting\Hooks;

use ArminVieweg\Dce\Utility\DatabaseUtility;
use ArminVieweg\Dce\Utility\File;
use ArminVieweg\Dce\Utility\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;




/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Hook for saving content element "dce"
 */
class DceAfterSave
{
  
   /**
     * Hook action
     *
     * @param $status
     * @param $table
     * @param $id
     * @param array &$fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     *
     * @return void
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array &$fieldArray,
        \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
    ) {
        
               
	       	       
	       if($table == 'tx_dce_domain_model_dce'){
	       
	       	       // status "new" for a new record and "update" for the record being edited
	       // table where DCE records are saved
               $dce_table = $table;
	       $field = '*';
               $where = 'uid = '.$id;
	       $dce = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field,$dce_table,$where);
	       $fileResourceFile = $dce[0]['template_file'];
	        // get template
	       $dceTemplate = '';
	       switch($dce[0]['template_type']){
	    	       case 'inline':
			$dceTemplate = $dce[0]['template_content'];
			$wrappedTemplate = $this->addWrapper($dceTemplate,$status);
			$where = 'uid = '.$id;
	                $fields = array(
                                'template_content' => $wrappedTemplate							
                               );
                        $GLOBALS['TYPO3_DB']->exec_UPDATEquery($dce_table, $where,$fields );			
			break;
		      case 'file':
			$fileResourceFile= File::get($dce[0]['template_file']);
			if($fileResourceFile!=''){
			$dceTemplate = file_get_contents($fileResourceFile);
			$wrappedTemplate = $this->addWrapper($dceTemplate,$status);
			$FilePointer=fopen($fileResourceFile,"wb");
                        $writting_result = fwrite($FilePointer,$wrappedTemplate);
		        fclose($FilePointer);
	                }
			break;
	       }       
	         
	       }
	       
	        if ($table == 'tt_content') {
		 if (GeneralUtility::isFirstPartOfStr($id, 'NEW')) {
                    $uid = $pObj->substNEWwithIDs[$id];		    
               }      
		      if(stripos($fieldArray['CType'],'dce_dce')!==false){
				 
				if( $fieldArray['header']==''){
				 
				  $table = 'tt_content';
                                  $where = 'uid='.$uid;
	                          $fields = array(
                                                  'subheader' => 'sb'							
                                                 );
                                  //$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where,$fields );
	    
				}				 
		      } 
		     		 
	        }
	   }
    
    /**
     *
     */
    public function addWrapper($content,$status){
      $cont = $content;
      $wrapPos = stripos($content,'<core:contentEditable');
      if($wrapPos===false){	
	$sectStartPos = stripos($content,'<f:section name="main">');
	$sectEndPos = stripos($content,'</f:section>');	
	if($sectStartPos!==false && $sectEndPos!==false){
	  $beforeStart = $this->before('<f:section name="main">',$content);
	  
	  	  
	  $afterStart = $this->after('<f:section name="main">',$content);
	  
	  // add wrapper
	  $cont = $beforeStart.'<f:section name="main">'.'<core:contentEditable table="tt_content"  uid="{contentObject.uid}">'.$afterStart;
	  
	  
	  
	  $beforeEnd = $this->before('</f:section>',$cont);
	  
	  $afterEnd = $this->after('</f:section>',$cont);
	  $cont = $beforeEnd.'</core:contentEditable></f:section>'.$afterEnd;	  
	}else{
	    if($status=='update'){
	    \ArminVieweg\Dce\Utility\FlashMessage::add(
                    'Template for this Dce with does not contain \'main\' section!. Please adjust your template.',
                    'DCE template is not completed',\TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE
                    );
	    }
	}
	
      }
      return $cont;
    }
    // next functions derived from biohazard@online.ge
    public function after ($str, $inthat)
    {
        if (!is_bool(strpos($inthat, $str)))
        return substr($inthat, strpos($inthat,$str)+strlen($str));
    }

    public function after_last ($str, $inthat)
    {
        if (!is_bool(strrevpos($inthat, $str)))
        return substr($inthat, strrevpos($inthat, $this)+strlen($str));
    }

    public function before ($str, $inthat)
    {
        return substr($inthat, 0, strpos($inthat, $str));
    }

    public function before_last ($str, $inthat)
    {
        return substr($inthat, 0, strrevpos($inthat, $str));
    }

    public function between ($str, $that, $inthat)
    {
        return $this->before ($that, after($str, $inthat));
    }

    public function between_last ($str, $that, $inthat)
    {
     return $this->after_last($str, before_last($that, $inthat));
    }

// use strrevpos function in case your php version does not include it
   public function strrevpos($instr, $needle)
   {
    $rev_pos = strpos (strrev($instr), strrev($needle));
    if ($rev_pos===false) return false;
    else return strlen($instr) - $rev_pos - strlen($needle);
   }
   
   
    function processDatamap_postProcessFieldArray ($status,$table,$id,&$fieldArray,&$reference) {
	   //print 'Table: '.$table.'<br/>';
	   //print 'Id: '.$id.'<br/>';
	   //foreach($fieldArray as $key=>$value)
	   //print '  Key: '.$key.' Value: '.$value.'<br/>';
    }
    
}