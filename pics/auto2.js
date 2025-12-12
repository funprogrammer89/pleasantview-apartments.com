

let rotate = true;
var i = 0; 			// Start Point
var images = [];	// Images Array
var time = 3000;	// Time Between Switch
	 
// Image List
images[0] = "pics\Aerial View.jpg";
images[1] = "pics/P-1.jpg";
images[2] = "pics/P-2.jpg";
images[3] = "pics/P-4.jpg";
images[4] = "pics/P-6.jpg";
images[5] = "pics/P-7.jpg";
images[6] = "pics/P-8.jpg";
images[7] = "pics/P-9.jpg";
images[8] = "pics/P-11.jpg";
images[9] = "pics/bathroom.jpg";
images[10] = "pics/P-15.jpg";
images[11] = "pics/P-17.jpg";
images[12] = "pics/P-18.jpg";
images[13] = "pics/P-20.jpg";
images[14] = "pics/P-21.jpg";
images[15] = "pics/P-22.jpg";
images[16] = "pics/P-23.jpg";


// Change Image
function changeImg(){

if (rotate == true){

	document.slide.src = images[i];

	// Check If Index Is Under Max
	if(i < images.length - 1){
	  // Add 1 to Index
	  i++; 
	} else { 
		// Reset Back To O
		i = 0;
	}

}

	// Run function every x seconds
	setTimeout("changeImg()", time);
return;


}

function rotateon(){

rotate = true;


}

function rotateoff(){

rotate = false;


}

// Run function when page loads
window.onload=changeImg;

