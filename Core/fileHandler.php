<?php

if(!empty($_POST['action'])&&$_POST['action']==='start'){
    GLOBAL $fileName;
    GLOBAL $directoryName;
    $directoryName='KrollData';
    $fileName = 'KrollDealerCatalogProductExport.xml';

   outputString("Starting........".PHP_EOL);
   outputString("\nSearching for ".$directoryName." directory.....\n");
   if(validateDirectory($directoryName)){
       outputString($directoryName. " directory found.....\n");
       if(validateFile($directoryName,$fileName)){
           outputString($fileName." file was found.....\n");
           splitFile($directoryName."/".$fileName);
       }else{
           outputString($fileName. " not found, halting execution.\n");
           die($fileName." Not Found");
       }
   }else{
       outputString($directoryName." directory not found, halting execution.\n");
       die("\n".$directoryName." Not Found");
   }
}else{
    outputString("An invalid value was passed to the file handler, halting execution!\n");
    die("\nINVALID POST DATA");
}

function validateDirectory($dirName):bool {
    return file_exists('../'.$dirName);
}

function validateFile($directory,$filename){
    return file_exists('../'.$directory.'/'.$filename);
}

function outputString($stringOut){
    echo nl2br(htmlentities($stringOut,ENT_QUOTES)."\n");
}

/*Locate kroll data file and split in to more manageable files*/

function splitFile($fileLocation){
//    $xmlHeader = array();
//    $dataSetEndTag = "";
    //find XML header -> store to header variable
    outputString("Grabbing XML schema data.....\n");
    if(!empty($xmlHeader=grabXMLHeader($fileLocation))){
        outputString("XML schema header found.\n");
    }else{
        outputString("Could not locate XML schema header, quitting....\n");
    }
    //identify XML data set opening tag -> store to variable
    outputString("Dataset tag is:" .$xmlHeader[1]."\n");
    $dataSetTag = $xmlHeader[1];
    $dataSetEndTag = createClosingTag($dataSetTag);
    outputString("Dataset closing tag set to: ".$dataSetEndTag."\n");
    //identify XML item entry tag -> store to variable
    outputString("Capturing object entry tag.....\n");
    $dataEntryTag = grabDataEntryTag($fileLocation,$xmlHeader[sizeof($xmlHeader)-1]);
    outputString("Captured object entry tag as: ".$dataEntryTag."\n");
    $dataEntryEndTag = createClosingTag($dataEntryTag);
    outputString("Object closing tag set to: ".$dataEntryEndTag."\n");
    //count number of items in catalog -> store to variable
    //split file in to X number of files
    //copy XML header information to beginning of each file
    //build data set from original (large) file -> place in new file
    //close new file with XML data set closing tag at bottom of file
    //repeat operations until large file is split
    //close original file
    //output error or done message
}

function getTagCountInFile($fileLocation,$targetTag){
    
}

function createClosingTag($openingTag):string{
    $closingTag = str_replace("<","</",$openingTag);
    return $closingTag;
}
function grabXMLHeader($fileLocation):array {
    $responseData = readFileToTarget($fileLocation,"</xs:schema>",null);
    return $responseData;
}

function grabDataEntryTag($fileLocation,$endOfHeaderTarget):string{
    $responseData = readFileToTarget($fileLocation,$endOfHeaderTarget,1);
    return $responseData[sizeof($responseData)-1];
}

function readFileToTarget($fileLocation, $target,$offset):array {
    outputString("Line target is: ".$target."\n");
    $fileArray = array();

    $fileGen = readFileToTargetGenerator($fileLocation,$target,$offset);

    foreach ($fileGen as $value){
        array_push($fileArray,$value);
    }
    return $fileArray;
}

function readFileToTargetGenerator($fileLocation,$target,$offset){
    $file = "../".$fileLocation;
    $handle = fopen($file,'r');
    $lastLine = "";

    if(is_null($offset)||$offset===0){
        while(trim($lastLine)!==$target){
            yield 'value' => $lastLine=trim(fgets($handle));
        }
    }
    if(!is_null($offset)&&$offset!==0){
        while(trim($lastLine)!==$target){
            yield 'value' => $lastLine=trim(fgets($handle));
        }
        if(trim($lastLine)===$target){
            for($i=0; $i<$offset; $i++){
                yield 'value' => $lastLine=trim(fgets($handle));
            }
        }
    }
}