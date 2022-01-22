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
	$IAM_KEY = $_ENV['aws_access_key_id'];
	$IAM_SECRET = $_ENV['aws_secret_access_key'];
	$TOKEN = $_ENV['aws_session_token'];

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
	
	$img = "<img src ='originales/$key' id='myImg' margin-top='50px'/> ";
	
	echo "<h1>Foto</h1>";
	echo "<div id='divFoto'>";
	echo $img;
	echo "</div>";
	echo "<button onclick='takeshot()' id='btnCapturar' type='button'>Recortar Foto</button>";
	echo "<a href='javascript:location.reload()'>Reset foto</a>";
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
    
    
    // foreach($result['FaceDetails'] as $face) {
    //     echo '<br>Left ' . $face['BoundingBox']['Left'];
    //     echo '<br>Top ' . $face['BoundingBox']['Top'];
        
    //     echo '<br>Width ' . $face['BoundingBox']['Width'];
    //     echo '<br>Height ' . $face['BoundingBox']['Height'];
        
    //     // echo '<br>Age Low ' . $face['AgeRange']['Low'];
    //     // echo '<br>Age High ' . $face['AgeRange']['High'];
    //     // echo '<br>Gender ' . $face['Gender']['Value'];
    //     echo '<br>';
    // }
    
    // define('HOLA', 'hola2');
    
    global $myJson;
    $myJson = json_encode($result['FaceDetails']);
    
    // return $myJson;
    
    
    // paso key a img para verlo mejor
    
    // global $img;
    // $img = __DIR__ . "/originales/" . $key;
    
    // // // tamanios
    
    // $medidas = getimagesize($img);    //Sacamos la información
    // $ancho = $medidas[0];             
    // $alto = $medidas[1];
    
    // $image1 = imagecreatefromjpeg($img);
    // $image2 = imagecreatefromjpeg($img);
    
    // for ($i = 1; $i <= 5; $i++) {
    //     imagefilter($image1, IMG_FILTER_GAUSSIAN_BLUR); //apply repeated times
    // }
    
    
    // // echo $img;
    // // echo $image2;
    
    // imagecopy($image2, $image1, 0.59707909822464 * $ancho, 0.19670516252518 * $alto,
    // 0.20028042793274 * $ancho , 0.45828658342361 * $alto,
    // 0.20028042793274 * 10 , 0.45828658342361 * $alto); //copy area
    
    // imagepng($image2, 'ABLUR.jpg', 0, PNG_NO_FILTER); //save new file
    
     
    
    // imagedestroy($image1);
    // imagedestroy($image2);
    

    
}

detectFaces($result);

// $imgg = $img;

$JsonJS = $myJson;


?>


<!DOCTYPE html>
<html>
<body>
    <head>
        <title>Preprocess</title>
        <link rel="stylesheet" href="https://unpkg.com/jcrop/dist/jcrop.css">
        <style type="text/css">
            .maxi{
                /*background: rgba(210,215,211,0.8);*/
                /*filter: blur(4px);*/
                /*-webkit-filter: blur(4px);*/
                /*backdrop-filter: grayscale(0);*/
                background-image: url("fondoBlur2.png");
                background-position:center;
                opacity:0.85;
                
            /*otra opcion*/
                /*background: black;*/
                /*opacity: 0.75 !important;*/
            }
            #divFoto{
                width: fit-content;
            }
        </style>
            <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.4/css/bulma.min.css" />-->
           <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.0.0-rc.5/dist/html2canvas.min.js"></script>
           
        
    </head>

<!--<img src="image_61c34ff30970b.png" id="myImg" />-->

<!--<canvas id="myCanvas" width="600" height="600" style="border:1px solid #d3d3d3;">-->
<!--</canvas>-->

  <div id="output"></div>
  
</body>

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
        
        jcrop.listen('crop.change', (widget, event) => {
        
       
            // los pequeños recuadros que tiene el que seleccionas 
            var recudritos = document.querySelectorAll('.jcrop-handle ');
             for (var i = 0; i < recudritos.length; i++) {
                recudritos[i].style.opacity = 0     
             }
        
        
        
            var rectangules = document.querySelectorAll('.jcrop-widget');    
        

            for (var i = 0; i < rectangules.length; i++) {
                rectangules[i].addEventListener('dblclick', function(event) {
                        this.className += ' maxi'
                        // alert(this.className);
                        // this.style.filter = "blur(2px)";
                        // this.style.backgroundColor = "black";
                        // this.style.backdropFilter = "blur(10px)";
                        // this.
                        // this.style.blur = "8px";
                        // style="backdropFilter: 'blur(0px')"
    
                        event.preventDefault();
                    
                });
                
                rectangules[i].addEventListener('contextmenu', function(event) {
                    this.style.backdropFilter = "";
                    this.className = 'jcrop-widget'
                	event.preventDefault();
                    // alert('Success');
                });
    
                // desactiva el blur del fondo
                
            }
            jcrop.setOptions({ shade: false,});
        
        });
        
        jcrop.setOptions({ shade: false,});
        
    // var lineasCuadritos = document.querySelectorAll('.jcrop-widget');
    // for (var i = 0; i < lineasCuadritos.length; i++) {
    //     lineasCuadritos[i].style.border =  "red";
    // }
        
}

    
    
</script>


<script type="text/javascript">
    
    // Define the function 
    // to screenshot the div
    function takeshot() {
        
    // una vez procesada la imagen, quita los bordes de la imagen
    var lineasCuadritos = document.querySelectorAll('.jcrop-widget');
    for (var i = 0; i < lineasCuadritos.length; i++) {
        lineasCuadritos[i].style.border =  "transparent";
    }
        
        
        let div =document.getElementById('divFoto');
        
        // let div =document.getElementsByClassName('jcrop-stage jcrop-image-stage');
            
        

        // Use the html2canvas
        // function to take a screenshot
        // and append it
        // to the output div
        html2canvas(div,{
        //  allowTaint: true
        })
        .then(
            function (canvas) {
                document
                .getElementById('output')
                .appendChild(canvas);
            })
            
            
        // screenshot(yourElement, {
        //   x: 20, // this are our custom x y properties
        //   y: 20, 
        //   width: 150, // final width and height
        //   height: 150,
        //   useCORS: true // you can still pass default html2canvas options
        // }).then(canvas => {
        //   //do whatever with the canvas
        // })
    }
</script>



    
</script>

</html>