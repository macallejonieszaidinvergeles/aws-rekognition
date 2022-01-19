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
    <head>
        <title>Preprocess</title>
        <link rel="stylesheet" href="https://unpkg.com/jcrop/dist/jcrop.css">
        
        <style type="text/css">
        .jcrop-widget active {
           filter: blur(8px);
           width: 10px !important;
         }
        </style>
    </head>

<!--<img src="image_61c34ff30970b.png" id="myImg" />-->

<!--<canvas id="myCanvas" width="600" height="600" style="border:1px solid #d3d3d3;">-->
<!--</canvas>-->



<script src="https://unpkg.com/jcrop"></script>


<script>
    window.onload = function() {
         
        var json = <?php echo json_encode($JsonJS); ?>;
        var jsonParse = JSON.parse(json);
        console.log(jsonParse)
        
        
        var img=document.getElementById("myImg"); 
        var width = img.width;
        var height = img.height;
        let rect;
        

        const jcrop = Jcrop.attach('myImg', {multi: true});


        for (const [key, value] of Object.entries(jsonParse)) {
            rect = Jcrop.Rect.create(value['BoundingBox']['Left'] * width, value['BoundingBox']['Top'] * height, value['BoundingBox']['Width'] * width, value['BoundingBox']['Height'] * height);
            jcrop.newWidget(rect, {});     
            
        }
        
        // jcrop.addClass('blur');
        
        var rectangules = document.querySelectorAll('.jcrop-widget');
        for (var i = 0; i < rectangules.length; i++) {
            rectangules[i].addEventListener('dblclick', function(event) {
                    // alert(this.className);
                    this.style.filter = "blur(2px)";
                    // this.style.backgroundColor = "black";
                    this.style.backdropFilter = "blur(3px)";
                    // this.style.blur = "8px";
                    // style="backdropFilter: 'blur(0px')"
                    event.preventDefault();
                
            });
        }
        
        // var a = img;
        // a.href = "/img.png";
        // a.download = "img.png";
        // document.body.appendChild(a);
        // a.click();
        // document.body.removeChild(a);
        

        // $('#myImg').Jcrop({
        //     onSelect: function(c){
        //         console.log(c);
        //         console.log(c.x);
        //         console.log(c.y);
        //         console.log(c.w);
        //         console.log(c.h);
        //     }
        // })
    
    }
</script>





</body>
</html>