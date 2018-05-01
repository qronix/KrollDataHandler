<?php

if(!empty($_POST['action'])&&$_POST['action']==='start'){
    GLOBAL $fileName;
    GLOBAL $directoryName;
    $directoryName='KrollData';
    $fileName = 'KrollDealerCatalogProductExport1.xml';

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
    $objectStartTag = grabDataEntryTag($fileLocation,$xmlHeader[sizeof($xmlHeader)-1]);
    outputString("Captured object start tag as: ".$objectStartTag."\n");
    $objectEndTag = createClosingTag($objectStartTag);
    outputString("Object closing tag set to: ".$objectEndTag."\n");
    //count number of items in catalog -> store to variable
    outputString("Determining number of target objects.....\n");
    $objectsCount = getTagCountInFile($fileLocation,$objectStartTag);
    outputString("There are ".$objectsCount." objects in the target file\n");
    //split file in to X number of files
    outputString("Starting file splitting operation.......\n");
    buildSubFiles($fileLocation,['objectStartTag'=>$objectStartTag,'objectEndTag'=>$objectEndTag,
        'objectsPerFile'=>2,'DataSetClosingTag'=>$dataSetEndTag,'XMLHeader'=>$xmlHeader]);
    //copy XML header information to beginning of each file
    //build data set from original (large) file -> place in new file
    //close new file with XML data set closing tag at bottom of file
    //repeat operations until large file is split
    //close original file
    //output error or done message
}

/*
 * Split params are options for splitting the target file in to subfiles
 *
 * objectStartTag    -> The opening tag which denotes a data object such as an item
 *
 * objectEndTag      -> The closing tag which denotes the end of a data object such as an item
 *
 * objectsPerFile    -> The total number of objects the sub file will contain
 *
 * DataSetClosingTag -> The closing tag for the XML data set
 *
 * XMLHeader         -> The XML file header which will be contained at the top of each sub file
 * */

function buildSubFiles($fileLocation, $splitParams){
    $subfileObjects = array();
    try{
        outputString("Checking for data directory...\n");
        if(checkDataDirExists()){
            outputString("Data directory was found....\n");
        }else{
            outputString("Data directory was created......\n");
        }
        $dateStamp = createFileDateStamp();

//        fopen($fileLocation."/dataSets/dataSet_".$dateStamp."xml","w");
        $dataToWrite = readFileFromStartToObjectCount($fileLocation,$splitParams);
//        $subFile = fopen($fileLocation."/dataSets/dataSet_".$dateStamp."xml","w");
//        $subFile = file_put_contents("../KrollData/dataSets/dataSet_".$dateStamp.".xml","w");
        outputString("Writing data to subfile......\n");
        $objectCount = 0;
        //begin XML dataset
        writeXMLHeader($dateStamp,$splitParams);
        foreach ($dataToWrite as $value){
            outputString("Current yielded value is: ".$value."\n");
            if($value===$splitParams['objectStartTag']){
                $objectCount++;
            }
            //if object count reached, close the dataset
            if($objectCount>$splitParams['objectsPerFile']){
                file_put_contents("../KrollData/dataSets/dataSet_".$dateStamp.".xml",$splitParams['DataSetClosingTag'].PHP_EOL,FILE_APPEND);
                break;
            }
            $subFile = file_put_contents("../KrollData/dataSets/dataSet_".$dateStamp.".xml",$value.PHP_EOL,FILE_APPEND);
        }
//        fclose($subFile);
    }catch (Exception $exc){
        outputString("An error occurred: ".$exc." stopping!\n");
        die($exc);
    }

}

function writeXMLHeader($dateStamp,$splitParams){
    foreach ($splitParams['XMLHeader'] as $value){
        file_put_contents("../KrollData/dataSets/dataSet_".$dateStamp.".xml",$value.PHP_EOL,FILE_APPEND);
    }
}

function checkDataDirExists():bool{
    try{
        if(file_exists("../KrollData/dataSets")){
            return true;
        }else{
            mkdir("../KrollData/dataSets");
            return false;
        }
    }catch (Exception $exc){
        outputString("An error occurred ".$exc."\n");
        die("Fatal error occurred, cannot continue");
    }
}

function createFileDateStamp():string{
    $today = getdate();
    $month = $today['mon'];
    $day   = $today['mday'];
    $year  = $today['year'];

    return $month."_".$day."_".$year;
}

/*
 * Split params are options for splitting the target file in to subfiles
 *
 * objectStartTag    -> The opening tag which denotes a data object such as an item
 *
 * objectEndTag      -> The closing tag which denotes the end of a data object such as an item
 *
 * objectsPerFile    -> The total number of objects the sub file will contain
 *
 * DataSetClosingTag -> The closing tag for the XML data set
 *
 * XMLHeader         -> The XML file header which will be contained at the top of each sub file
 * */


function readFileFromStartToObjectCount($fileLocation, $splitParams){
    $file = "../".$fileLocation;
    $handle = fopen($file,'r');
    $pastHeader = false;
    $currentObjectCount = 0;
    outputString("Starting split, objects per file is set to: ".$splitParams['objectsPerFile']."\n");
//    $headerEndTag = $splitParams['XMLHeader'][sizeof($splitParams['XMLHeader'])]
    while(!feof($handle)){
        if(trim(fgets($handle))===($splitParams['XMLHeader'][sizeof($splitParams['XMLHeader'])-1])){
            $pastHeader = true;
        }
        if($pastHeader){
            if(($value=trim(fgets($handle)))===$splitParams['objectStartTag']){
                yield 'value' => $value;
                while(($currentObjectCount<$splitParams['objectsPerFile'])){
                    $value = trim(fgets($handle));
                    yield 'value' => $value;
                    outputString("Output value is: " .$value."\n");
                    if($value===$splitParams['objectStartTag']){
                        $currentObjectCount++;
                    }
                    outputString("Current object count is: ".$currentObjectCount."\n");
                }
            }
        }
    }
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

function getTagCountInFile($fileLocation,$targetTag):int {
    $file = "../".$fileLocation;
    $handle = fopen($file,'r');
    $tagCount = 0;
    while(!feof($handle)){
        if(($currentLine = trim(fgets($handle)))===$targetTag){
            $tagCount++;
        }
    }
    return $tagCount;
}