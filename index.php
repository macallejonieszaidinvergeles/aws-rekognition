<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\ObjectUploader;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;
use Aws\Common\Aws;
use Aws\S3\Exception\S3Exception;
use Aws\Rekognition\RekognitionClient;

// echo shell_exec("whoami");


function isUploadedFile($name) {
    return isset($_FILES[$name]);
}

function isValidUploadedFile($file) {

    $result = true;
    $error = $file['error'];
    $name = $file['name'];
    $size = $file['size'];
    $tmp_name = $file['tmp_name'];
    $type = $file['type'];
    if($error != 0 || $name == '' || $size == 0 || strpos($type, 'image/') === false || !is_uploaded_file($tmp_name)) {
        $result = false;
    } else {
        $mcType = mime_content_type($tmp_name);
        if(strpos($mcType, 'image/') === false) {
            $result = false;
        }
    }
    return $result;
}



function uploadFile($paramName) {
    $result = false;

    if(!isUploadedFile($paramName)) {
        return false;
    }

    $file = $_FILES[$paramName];
    
    if(!isValidUploadedFile($file)) {
        return false;
    }

    return moveFile($file);

}


function moveFile($file) {
    
    $target = 'originales/';
    $uniqueName = uniqid('image_');
    $name = $file['name'];
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    $tmp_name = $file['tmp_name'];
    $uploadedFile = $target . '/' . $uniqueName . '.' . $extension;
    
    
    if(move_uploaded_file($tmp_name, $uploadedFile)) {
        return [$uploadedFile, $uniqueName . '.' . $extension, $uniqueName, $extension,];
    }
    return false;
}

function main($name){
    $result = uploadFile($name);
    uploadToBucket($result);
    detectFaces($result);
    // var_dump($result);
}

main('archivo');

function uploadToBucket($result) {
    
    // AWS Info
	$bucketName = 'updatemax';
	$IAM_KEY = 'ASIAT6ZAKFPLRDWRHEN4';
	$IAM_SECRET = 'pFHmkTV/6aPq9wMrE4SS2QYk3HHTceM0jTOn3Upy';
	$TOKEN = 'FwoGZXIvYXdzEEkaDCSi6BO3SYw17Ai7ryLZARgeSGD1lklFhf7DnrkO66g/nbjr23lkPrsMlPUzLBdIk9btZdHp5IX1BKqC/soLZsO1kcO90Pb98aR9dSJ+/8LzLGQMIzMIjOh5p9BO9Qcgr0oOyKcEIt2MYOWpQmLewyOmqxG56FI3x+zPfcLBXv6OQjPkBviMGjhCqB/1OcqokLrA1Lh9oqiTYlTBB+MlXonACqcCUnIM7TnAi1lPrD4RZHiQDCIClsWJ3EFcYPvEjsCUbqNiKvJ5W79GCxi0SRxn9tZAn64oGmps4pXiMbvtHynitC5DISMo/P+MjgYyLV1aax0lCMPJMKe4gSbB5qmU1XCUHZKeGPOcCoQX7eu+6pR7C7LkM5uiuEV0ew==';

	// Connect to AWS
	try {
		// You may need to change the region. It will say in the URL when the bucket is open
		// and on creation.
		$s3 = S3Client::factory(
			array(
				'credentials' => array(
					'key' => $IAM_KEY,
					'secret' => $IAM_SECRET,
					'token' => $TOKEN
				),
				'version' => 'latest',
				'region'  => 'us-east-1'
			)
		);
	} catch (Exception $e) {
		// We use a die, so if this fails. It stops here. Typically this is a REST call so this would
		// return a json object.
		die("Error: " . $e->getMessage());
	}
	


	// For this, I would generate a unqiue random string for the key name. But you can do whatever.
    // 	$keyName = 'test_example/' . basename($_FILES["archivo"]['tmp_name']);
    // 	$pathInS3 = 'https://s3.us-east-2.amazonaws.com/' . $bucketName . '/' . $keyName;


	// Add it to S3
	try {
		// Uploaded:
        // 		$file_Path = __DIR__. "/image_61bdddcac3128.jpg";
        // 		$key = basename($file_Path);

        /*$originales = scandir(__DIR__. "/originales");
        $lastFile = end($originales);
        
        $file_Path = __DIR__. "/originales/" . $lastFile; 

		$key = basename($file_Path);*/
		
		$file_Path = $result[0];
		$key = $result[1];
		
		$s3->putObject(
			array(
				'Bucket'=>$bucketName,
				'Key' =>  $key,
				'Body' => fopen($file_Path,'r'),
			)
		);

	} catch (S3Exception $e) {
		die('Error:' . $e->getMessage());
	} catch (Exception $e) {
		die('Error:' . $e->getMessage());
	}
	return true;
}

function detectFaces($result) {
    $bucketName = 'updatemax';
	$IAM_KEY = 'ASIAT6ZAKFPLRDWRHEN4';
	$IAM_SECRET = 'pFHmkTV/6aPq9wMrE4SS2QYk3HHTceM0jTOn3Upy';
	$TOKEN = 'FwoGZXIvYXdzEEkaDCSi6BO3SYw17Ai7ryLZARgeSGD1lklFhf7DnrkO66g/nbjr23lkPrsMlPUzLBdIk9btZdHp5IX1BKqC/soLZsO1kcO90Pb98aR9dSJ+/8LzLGQMIzMIjOh5p9BO9Qcgr0oOyKcEIt2MYOWpQmLewyOmqxG56FI3x+zPfcLBXv6OQjPkBviMGjhCqB/1OcqokLrA1Lh9oqiTYlTBB+MlXonACqcCUnIM7TnAi1lPrD4RZHiQDCIClsWJ3EFcYPvEjsCUbqNiKvJ5W79GCxi0SRxn9tZAn64oGmps4pXiMbvtHynitC5DISMo/P+MjgYyLV1aax0lCMPJMKe4gSbB5qmU1XCUHZKeGPOcCoQX7eu+6pR7C7LkM5uiuEV0ew==';

    $file_Path = $result[0];
	$key = $result[1];
	
    try{
	  $options = [
				'credentials' => array(
					'key' => $IAM_KEY,
					'secret' => $IAM_SECRET,
					'token' => $TOKEN
				),
		'version' => 'latest',
		'region'  => 'us-east-1'
			
    ];

    $rekognition = new RekognitionClient($options);
	
    // Get local image
    // $photo = $file_Path;
    // $fp_image = fopen($photo, 'r');
    // $image = fread($fp_image, filesize($photo));
    // fclose($fp_image);
    
    
    // ultima foto subida al bucket s3
    // $url = $s3->getObjectUrl($bucketName, $key);
    
    // echo $url;


    // Call DetectFaces
    $result = $rekognition->DetectFaces(array(
       'Image' => [
                'S3Object' => [
                    'Bucket' => $bucketName,
                    'Name'  =>  $key,
                    'Key'   =>  $file_Path,
                ],
            ],
       'Attributes' => array('ALL')
       )
    );

    
	}catch (S3Exception $e) {
		die('Error:' . $e->getMessage());
	} catch (Exception $e) {
		die('Error:' . $e->getMessage());
	}
	
	echo "<img src ='originales/$key' width='350px'/>" ;
	echo "<br/>";
	
	// Display info for each detected person
    /*print 'People: Image position and estimated age' . PHP_EOL;
    for ($n=0;$n<sizeof($result['FaceDetails']); $n++){
      print 'Position: ' . $result['FaceDetails'][$n]['BoundingBox']['Left'] . " "
      . $result['FaceDetails'][$n]['BoundingBox']['Top']
      . PHP_EOL
      . 'Age (low): '.$result['FaceDetails'][$n]['AgeRange']['Low']
      .  PHP_EOL
      . 'Age (high): ' . $result['FaceDetails'][$n]['AgeRange']['High']
      .  PHP_EOL . PHP_EOL
      . 'Gender: ' . $result['FaceDetails'][$n]['Gender']['Value']
      .  PHP_EOL . PHP_EOL
      . 'Landmarks: ' . $result['FaceDetails'][$n]['Landmarks']['X']
      .  PHP_EOL . PHP_EOL;
    }*/
    foreach($result['FaceDetails'] as $face) {
        echo '<br>Left ' . $face['BoundingBox']['Left'];
        echo '<br>Width ' . $face['BoundingBox']['Width'];
        echo '<br>Height ' . $face['BoundingBox']['Height'];
        echo '<br>Top ' . $face['BoundingBox']['Top'];
        echo '<br>Age Low ' . $face['AgeRange']['Low'];
        echo '<br>Age High ' . $face['AgeRange']['High'];
        echo '<br>Gender ' . $face['Gender']['Value'];
    }
}

// 	muestra el ultimo objeto del bucket
// 	try{
//     	$objects = $s3->listObjectsV2([
//             'Bucket' => $bucketName,
//         ]);
        
//     // foreach ($objects['Contents'] as $object){
//     //     echo "{$object['Key']}";
//     // }
    
//     //  var_dump($objects['Contents'][sizeof($objects)]['Key']);
    
// 	}catch (S3Exception $e) {
// 		die('Error:' . $e->getMessage());
// 	} catch (Exception $e) {
// 		die('Error:' . $e->getMessage());
// 	}
    // print_r($result);