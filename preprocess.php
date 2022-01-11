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


$result = $_SESSION['result_face'];


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
    
    // return $myJson;
    
    
    // $url =  "//{$_SERVER['HTTP_HOST']}/PIA/upload/preprocess/preprocess.php";
    // header("Location: https://" . $url);
    
}

detectFaces($result);



$JsonJS = $myJson;

// echo "json" . $myJson;

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

<canvas id="myCanvas" width="600" height="600" style="border:1px solid #d3d3d3;">
</canvas>



<script>

window.onload = function() {
    
    //quitar las comillas
    var json = <?php echo json_encode($JsonJS); ?>;
    var jsonParse = JSON.parse(json);
    // console.log(jsonParse);
    
    // jsonParse.forEach(element => console.log(element));
    
//     for (const [key, value] of Object.entries(jsonParse)) {
//   console.log(key, value);
// }
    

    var c=document.getElementById("myCanvas");
    var ctx=c.getContext("2d");
    var img=document.getElementById("myImg");  
    ctx.drawImage(img,10,10);  
    var canvas = document.getElementById('myCanvas');
    var context = canvas.getContext('2d');
    
    // ancho y alto de la imagen
    var width = img.width;
    var height = img.height;
    
    context.beginPath();
    //multiplicar las coordenadas por ancho y altura de la foto
    // context.rect(0.48962652683258 * width, 0.30748847126961 * height, 0.18888917565346 * width, 0.37117749452591 * height)
    // context.rect(0.3519452214241 * width, 0.39240410923958 * height, 0.16134768724442 * width, 0.34630155563354 * height); 
    
    for (const [key, value] of Object.entries(jsonParse)) {
        console.log(value)
        //multiplicar las coordenadas por ancho y altura de la foto
        context.rect(value['BoundingBox']['Left'] * width, value['BoundingBox']['Top'] * height, value['BoundingBox']['Width'] * width, value['BoundingBox']['Height'] * height)
        
        // if(value['AgeRange']['High'] < 18){
        //     context.fillStyle = 'black';
        //     // context.filter = "blur(3px)";
        //     // context.fillStyle = "black";
        // }
    // console.log(key, value);
}

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