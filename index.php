<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    global $result;
    $result = uploadFile($name);
    uploadToBucket($result);
    
    // echo uploadToBucket($result);
    // detectFaces($result);
    // var_dump($result);
}

main('archivo');

function uploadToBucket($result) {
    
    // AWS Info
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    
    $bucketName = $_ENV['bucketName'];
	$IAM_KEY = $_ENV['IAM_KEY'];
	$IAM_SECRET = $_ENV['IAM_SECRET'];
	$TOKEN = $_ENV['TOKEN'];

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
	
	echo "<h1>se ha subido la foto al bucket</h1>";
	echo "<a href='preprocess.php'>Preprocess</a>";
	return true;
	
}


	$get_result = $result;
    $_SESSION['result_face'] = $get_result;


?>






