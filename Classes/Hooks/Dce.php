<?php
declare(strict_types=1);

namespace IgorKruglov\IkDcefrontendediting\Hooks;
use TYPO3\CMS\FrontendEditing\RequestPreProcess\RequestPreProcessInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class Dce implements RequestPreProcessInterface
{
  
  var $flexVal='';
  var $newFlexVal='';
  
    /**
     * Pre process the content
     *
     * @param string $table
     * @param array $record
     * @param string $fieldName
     * @param string $content
     * @param bool $isFinished
     * @return string the modified content
     */
    public function preProcess(
        string $table,
        array $record,
        string &$fieldName,
        string $content,
        bool &$isFinished
    ): string {
     
        // special processing for DCE element type
            
       if ($table === 'tt_content' &&  stripos($record['CType'],'dce_dce')!==false) {
            $contentElement = $record;
            $this->flexVal = $record['pi_flexform'];
            $this->newFlexVal = $record['pi_flexform'];
	    
	    	    
	        
            
                 
            //$contentElement = BackendUtility::getRecord('tt_content', $contentElement['uid']);
        

        /*
        // Make instance of "DceRepository" and "FlexFormService"
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        /** @var \ArminVieweg\Dce\Domain\Repository\DceRepository $dceRepository */
        //$dceRepository = $objectManager->get('ArminVieweg\Dce\Domain\Repository\DceRepository');
        /** @var \TYPO3\CMS\Extbase\Service\FlexFormService $flexFormService */
        //$flexFormService = $objectManager->get('TYPO3\CMS\Extbase\Service\FlexFormService');

        // Convert flexform XML to array
        //$flexData = $flexFormService->convertFlexFormContentToArray($contentElement['pi_flexform'], 'lDEF', 'vDEF');
        
        //$flexDataString = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml($flexData,'',0,'T3FlexForms');
        
        
        /*
        // Retrieve DCE domain model object
        $dceUid = self::getDceUidByContentElementUid($contentElement['uid']);
        $dce = $dceRepository->findAndBuildOneByUid(
            $dceUid,
            $flexData['settings'],
            $contentElement
        );
        */
        
            $isFinished = true;
	    
            // create temporary file to save existing flexform to            
           // $uploadPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('uploads/tx_ikdcefrontendediting/');
            $uploadPath = GeneralUtility::getFileAbsFileName('uploads/tx_ikdcefrontendediting/');
           
	    $localfilename = 'user_'.substr(md5((string)time()),0,10);
            $FilePointer=fopen($uploadPath.$localfilename,"wb");
            //$writting_result = fwrite($FilePointer,PHP_EOL.$message);
            $writting_result = fwrite($FilePointer,$this->flexVal);
	    fclose($FilePointer);
	    
            // navigate through xml. While navigating new flexform, with data got from editor,is build                        

            $reader = new \XMLReader();
            libxml_disable_entity_loader(false); 
        
            $reader->open('file://'.$uploadPath.$localfilename);
            
             while ($reader->read()) {
                
                if ($reader->nodeType == \XMLReader::ELEMENT) {
                    $r = $this->startElement($reader,$fieldName,$content);                
                    
                    // if new field element could not be started (previous one was not closed) then exit
                    if(!$r && $reader->name=='field')
                    return true;
                    
                }
                
                else {
                   
                    if ($reader->nodeType == \XMLReader::END_ELEMENT) {
                        $this->endElement($reader);
                } else {
                    continue;
                }
               
            }
            }
            $reader->close();
            
            // update plexform DB content with just builded one
  /*
            $uid = $record['uid'];
	    $fieldToSaveName = 'pi_flexform';
	    $table='tt_content';
	    $data = [
            $table => [
                $uid => [
                    $fieldToSaveName => $this->newFlexVal
                ]
            ]
            ];
*/
 //       $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
   //     $dataHandler->start($data, []);
	    
	    $table = 'tt_content';
            $where = 'uid='.$record['uid'];
	    $fields = array(
                                'pi_flexform' => $this->newFlexVal							
                               );
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where,$fields );
	    ////////////////////
    
       }        
        $message = 'Build plexform: '.$this->newFlexVal;
	    $this->addtoLog($message);    
            
        // delete temporary file
	//!!!!!!!!!!!! unlink($uploadPath.$localfilename);
        
        return $this->newFlexVal;
    }
    
     /**
     * Method is invoked when parser accesses start tag of an element.
     *
     * @param string $fieldName name of dce field that was just edited
     */
    protected function startElement($reader,$dceFieldName,$newFieldContent)
    {
                 
        
        $elementName = $reader->name;
        switch ($elementName) {
            case 'field':
                $indexVal = $reader->getAttribute('index');
                $fieldName = substr($indexVal,9);
                $fieldXML = $reader->readOuterXML();
                // substitute old value with a new one
                
                $valueReader = new \XMLReader();
                $valueReader->xml(trim($fieldXML));
                
                while ($valueReader->read()) {
                    if ($valueReader->nodeType == \XMLReader::ELEMENT) {
                        $this->startValueElement($valueReader,$fieldName,$dceFieldName,$newFieldContent);               
                } else {
                    if ($valueReader->nodeType == \XMLReader::END_ELEMENT) {
                        $this->endValueElement($valueReader);
                } else {
                    continue;
                  }
                 }
                }
                $valueReader->close();          
                
                break;
                default:
                
                break;
            }
            
                
        
        return true;
    }

    /**
     * Method is invoked when parser accesses end tag of an element.
     *
     * @param string $elementName: element name at parser's current position
     */
    protected function endElement($reader)
    {
        return true;
       
    }
     	
	
     /**
     *@param $fieldName name of the field in xml that is being processed 
     *@param $dceFieldName name of the dce field that is being edited
     *@param $newFieldContent content from editor 
     **/
    protected function startValueElement($reader,$fieldName,$dceFieldName,$newFieldContent){
        $elementName = $reader->name;
	
	/*
	$str =str_replace("\b", " ", $newFieldContent);
        $str =str_replace(chr(8), " ", $str);
        $str = str_replace("\\b", " ", $str);
	$str =str_replace("\B", "", $str);
        $str = str_replace("\\B", " ", $str);
	*/
	//$str =rawurldecode($newFieldContent);
	//$str = str_replace(chr(160), "", $newFieldContent);
	
	//$this->addtoLog('XML field name: '.$fieldName.' Editor field name'.$dceFieldName);
	
	
               
        switch($elementName){            
                
            case 'value':
                if($fieldName==$dceFieldName){
        
	
	//$str = str_replace(chr(160),htmlentities("&nbsp;"),$newFieldContent);
	//$this->addtoLog('Content do: '.$newFieldContent.' Content posle:'.$str);
	
	
	
	            // current value
                    $valueVal = $reader->readString();
                    // value of the db fieldXML where old value is substituted with the new one
	// this is for simple type	    
	$str =     str_replace('&nbsp;',' ',htmlentities($newFieldContent,ENT_QUOTES, 'UTF-8'));
	// for complex type
        $str = htmlspecialchars($str);

	
	
	
		    
	
	
	
		    $this->addtoLog('Value to search: '.htmlspecialchars($valueVal));
		    $this->addtoLog('Value to replace: '.$str);
		    $this->addtoLog('XML to search: '.$this->newFlexVal);
                    $this->newFlexVal = str_replace(htmlspecialchars($valueVal),$str, $this->newFlexVal);                  
                   
                }           
                break;
            
            
        }        
        return true;
}
    /**
     *
     **/
    protected function endValueElement($reader){
        
        return true;
    }
    
    /**
     *
     **/
    public function addtoLog($message){
        
        $path = GeneralUtility::getFileAbsFileName('typo3conf/ext/ik_dcefrontendediting/dcelog.txt');
        $FilePointer=fopen($path,"a");
        $writting_result = fwrite($FilePointer,$message."\r\n");
	fclose($FilePointer);                
      
    }
    
}