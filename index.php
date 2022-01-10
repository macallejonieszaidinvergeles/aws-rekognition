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
	return true;
}

function detectFaces($result) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    
    $bucketName = $_ENV['bucketName'];
	$IAM_KEY = $_ENV['IAM_KEY'];
	$IAM_SECRET = $_ENV['IAM_SECRET'];
	$TOKEN = $_ENV['TOKEN'];

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
	
	echo "<img src ='originales/$key' id='myImg' margin-top='50px'/> ";
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
        echo '<br>';
    }
    
    // define('HOLA', 'hola2');
    
    global $myJson;
    $myJson = json_encode($result['FaceDetails']);
    
    
    // $url =  "//{$_SERVER['HTTP_HOST']}/PIA/upload/preprocess/preprocess.php";
    // header("Location: https://" . $url);
    
}



$JsonJS = $myJson;

// echo "json" . $myJson;

// echo HOLA;

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
    
?>


<!DOCTYPE html>
<html>
<body>

<!--<img src="image_61c34ff30970b.png" id="myImg" />-->

<canvas id="myCanvas" width="600" height="300" style="border:1px solid #d3d3d3;">
</canvas>



<script>

window.onload = function() {
    
    var json = <?php echo json_encode($JsonJS); ?>;
    var jsonParse = JSON.parse(json);
    console.log(jsonParse);
    

    var c=document.getElementById("myCanvas");
    var ctx=c.getContext("2d");
    var img=document.getElementById("myImg");  
    ctx.drawImage(img,10,10);  
    var canvas = document.getElementById('myCanvas');
    var context = canvas.getContext('2d');


    var width = img.width;
    var height = img.height;
    
    context.beginPath();
    //multiplicar las coordenadas por ancho y altura de la foto
    context.rect(0.48962652683258 * width, 0.30748847126961 * height, 0.18888917565346 * width, 0.37117749452591 * height)
    context.rect(0.3519452214241 * width, 0.39240410923958 * height, 0.16134768724442 * width, 0.34630155563354 * height); 
    context.fillStyle = 'transparent';
    //   context.filter = "blur(6px)";
    // context.fillStyle = "black";
    context.fill();

    context.lineWidth = 2;
    context.strokeStyle = 'black';
    context.stroke();
    
    
    
}
</script>


</body>
</html>