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
    echo nl2br(htmlentities($stringOut,ENT_QUOTES));
}

/*Locate kroll data file and split in to more manageable files*/

function splitFile($fileLocation){
    $xmlHeader = array();
    //find XML header -> store to header variable
    outputString("Grabbing XML schema data.....\n");
//    if(!empty($xmlHeader=grabXMLHeader($fileLocation))){
//        outputString("XML schema header found.\n");
//    }else{
//        outputString("Could not locate XML schema header, quitting....\n");
//    }
    $response = readFileToTarget($fileLocation,"</xs:schema>",null);
    foreach ($response as $value){
        outputString($value."\n");
    }
    //identify XML data set opening tag -> store to variable
    //identify XML item entry tag -> store to variable
    //count number of items in catalog -> store to variable
    //split file in to X number of files
    //copy XML header information to beginning of each file
    //build data set from original (large) file -> place in new file
    //close new file with XML data set closing tag at bottom of file
    //repeat operations until large file is split
    //close original file
    //output error or done message
}

function grabXMLHeader($fileLocation):array {
//    $responseData = array();
    $responseData = readFileToTarget($fileLocation,"</xs:schema>");
    return $responseData;
}

function readFileToTarget($fileLocation, $target):array {
    outputString("file target is: ".htmlentities($target,ENT_QUOTES)."\n");
    $fileArray = array();

    $fileGen = readFileGenerator($fileLocation,$target);

    foreach ($fileGen as $value){
        array_push($fileArray,$value);
//        outputString($value."\n");
    }
    return $fileArray;
}

function readFileGenerator($fileLocation,$target){
    $file = "../".$fileLocation;
    $handle = fopen($file,'r');
    $lastLine = "";

    while(trim($lastLine)!==$target){
        yield 'value' => $lastLine=trim(fgets($handle));
    }
}