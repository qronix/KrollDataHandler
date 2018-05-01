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
        'objectsPerFile'=>3,'DataSetClosingTag'=>$dataSetEndTag,'XMLHeader'=>$xmlHeader,'numTargetObjects'=>$objectsCount]);
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
 *
 * numTargetObjects  -> The number of target objects in the XML file
 * */

function buildSubFiles($fileLocation, $splitParams){
    try{
        outputString("Checking for data directory...\n");
        if(checkDataDirExists()){
            outputString("Data directory was found....\n");
        }else{
            outputString("Data directory was created......\n");
        }

        $startAtObjectNumber = 0;
        $numberOfSubfiles = $splitParams['numTargetObjects']/$splitParams['objectsPerFile'];
        outputString("Number of subfiles is: {$numberOfSubfiles}\n");

        for($i=0; $i<$numberOfSubfiles; $i++) {

            $dateStamp = createFileDateStamp();


            $dataToWrite = readFileFromStartToObjectCount($fileLocation, $splitParams,$startAtObjectNumber);
            outputString("Writing data to subfile......\n");
            $objectCount = 0;
            $closeTagCount = 0;
            //begin XML dataset
            writeXMLHeader($dateStamp, $splitParams,$i);
            foreach ($dataToWrite as $value) {
//                outputString("Current yielded value is: " . $value . "\n");
                if ($value === $splitParams['objectStartTag']) {
                    $objectCount++;
                    $startAtObjectNumber++;
                }
                if($value === $splitParams['objectEndTag']){
                    $closeTagCount++;
                }

                //if object count reached, close the dataset
//                if ($objectCount > $splitParams['objectsPerFile']) {
//                    file_put_contents("../KrollData/dataSets/dataSet_" . $dateStamp."_{$i}.xml", $splitParams['DataSetClosingTag'] . PHP_EOL, FILE_APPEND);
//                    break;
//                }
                file_put_contents("../KrollData/dataSets/dataSet_" . $dateStamp."_{$i}.xml", $value . PHP_EOL, FILE_APPEND);
                if($closeTagCount>=$splitParams['objectsPerFile']){
                    file_put_contents("../KrollData/dataSets/dataSet_" . $dateStamp."_{$i}.xml", $splitParams['DataSetClosingTag'] . PHP_EOL, FILE_APPEND);
                    break;
                }
            }
        }
    }catch (Exception $exc){
        outputString("An error occurred: ".$exc." stopping!\n");
        die($exc);
    }

}

function writeXMLHeader($dateStamp,$splitParams,$fileNumber){
    foreach ($splitParams['XMLHeader'] as $value){
        file_put_contents("../KrollData/dataSets/dataSet_".$dateStamp."_{$fileNumber}.xml",$value.PHP_EOL,FILE_APPEND);
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
 *
 * numTargetObjects  -> The number of target objects in the XML file
 * */


function readFileFromStartToObjectCount($fileLocation, $splitParams,$startObjectNumber){
    $file = "../".$fileLocation;
    $handle = fopen($file,'r');
    $pastHeader = false;
    $currentObjectCount = 0;
    $objectsSeen = 0;
    outputString("Starting split, objects per file is set to: ".$splitParams['objectsPerFile']."\n");
//    $headerEndTag = $splitParams['XMLHeader'][sizeof($splitParams['XMLHeader'])]
    while(!feof($handle)){
        if(trim(fgets($handle))===($splitParams['XMLHeader'][sizeof($splitParams['XMLHeader'])-1])){
            $pastHeader = true;
        }
        if($pastHeader){
            $value=trim(fgets($handle));
            if($value===$splitParams['objectStartTag']){
                $objectsSeen++;
            }
            //are we at the target object?
            if($startObjectNumber!==0){
                do{
                    $value=trim(fgets($handle));
                    if($value==""){
                        break;
                    }
                    if($value===$splitParams['objectStartTag']){
                        $objectsSeen++;
                    }
                }while($objectsSeen<=$startObjectNumber);
            }
            if($value===$splitParams['objectStartTag']){
                while(($currentObjectCount<$splitParams['objectsPerFile'])){
                    yield 'value' => $value;
                    $value = trim(fgets($handle));
                    if($value==""){
                        break;
                    }
//                    yield 'value' => $value;
//                    outputString("Output value is: " .$value."\n");
                    if($value===$splitParams['objectStartTag']){
                        $currentObjectCount++;
                    }
//                    outputString("Current object count is: ".$currentObjectCount."\n");
                }
            }
            if($value==""){
                break;
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