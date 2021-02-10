<script src="../include/jquery-3.3.1.min.js"></script>
<link href="../include/bootstrap-4.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
<style>
	body {
		
		background-color: #gggggg;
		font-family: Arial, Helvetica, sans-serif;
	}
	.parent {
		display: flex;
		justify-content: center;
		align-items: center;
		height: 100vh;
	}
	.object {	
		position: relative;
		height: 400px;
		width: 160px;
		margin-bottom: 1rem;
		border-top: 2px solid black;
		border-bottom: 2px solid black;
		display:none;
		margin-left:40%;
	}
	.target {
		position: absolute;
		top: 100px;
		width: 100%;
		border-top: 2px solid black;
		z-index: 2;
	}
	.bar {
		position: absolute;
		bottom: 0;
		left: 40px;
		width: 80px;
		height: 0;
		background-color: grey;
		opacity: 0.5;

	}
	.feedback {
		color: red;
		font-weight: strong;
		text-align: center;
		font-size: 40pt;
		margin-top:20px;
		z-index: 99999;
	}
	.cue {
		color: green;
		font-weight: strong;
		text-align: center;
		font-size: 40pt;
		margin-top:20px;
		z-index: 99999;
	}
	.scorePanel {
		font-size: 16pt;
		color: black;
		display:none;
		margin-left:40%;
	}
	.scoreAdd { 
		font-weight: bold; 
	}
	.mainContent {
		font-size: 14pt;
		color: green;
	}
	.instructions {
		width: 500px;
		font-size: 14pt;
	}
	p { font-size: 12pt; }
	small {font-size: 10pt;}
</style>
<script>
// 
// Stop-Signal Anticipation Task coded in JavaScript
// V1 - ppas 20180811
//
$(document).ready(function() {
	// configure 
	var trialDuration = 3500;
	var cueDuration = 1000;
	var barRaiseTime = 2000;
	var stopSignalDelay = 1150;
	var score = 0;

	// read sequence file
	<?php
	$seqCue = array();
	$seqStop = array();
	$file = fopen('sequence_1.txt',"r");
	if ($file) {
		while (($line = fgets($file)) !== false) {
	    	$vals = preg_split('/\s+/', $line);
	    	array_push($seqCue, $vals[0]);
	    	array_push($seqStop, $vals[1]);
		}
	}
	fclose($file);
	?>

	// write sequence vars as javascript
	var seqCue = [<?php foreach($seqCue as $val) { echo $val . ','; } ?>];
	var seqStop = [<?php foreach($seqStop as $val) { echo $val . ','; } ?>];

	nTrials = seqCue.length + 1;

	// configure in ini file
	<?php 
	echo  "var sessionHash = '" . $_GET['sessionCode'] . "';";
	?>

	// cues
	var cueA = {symbol: "O", color: "green", penalty: 10};
	var cueB = {symbol: "U", color: "orange", penalty: 10};
	var cueC = {symbol: "H", color: "purple", penalty: 100};

	// instructions
	document.getElementById("mainContent").innerHTML = `<div class="parent">
	<div class="instructions">
		<h1>Instructies.</h1><hr>
			<p>Je ziet zometeen drie horizontale lijnen op het scherm. Vanaf de onderste lijn zal een balk naar boven gaan lopen. Het is de bedoeling dat je deze lijn stopt op het middelste streepje door op de spatiebalk te drukken.</p>
			<p>Soms zal de balk uit zichzelf al eerder stoppen - je moet dan niet drukken</p><p>Voordat de balk zal gaan bewegen zie je telkens een letter. Deze geeft aan of de balk vanzelf zal stoppen, en hoeveel punten je kunt winnen (en verliezen).</p>
			<p><strong style="font-size:22pt">` + cueA.symbol + `</strong> <small>De balk zal nooit vanzelf stoppen, dus altijd zelf drukken</small></p>
			<p><strong style="font-size:22pt;color:orange">` + cueB.symbol + `</strong> <small>Balk stopt soms vanzelf, punten bij goed drukken en strafpunten als je toch drukt</small></p>
			<p><strong style="font-size:22pt;color:purple">` + cueC.symbol + `</strong> <small>Balk stopt soms vanzelf, extra veel (straf-)punten</small></p>
			<hr><h3>Druk op spatie om te starten.</h3></div></div>`;


	document.body.onkeydown = function(e){
		if(e.keyCode){
			document.getElementById("mainContent").innerHTML = `<div class="parent">
				<div class="container">
					<div class="scorePanel" id="scorepanel"><span id="score">0</span> <span class="scoreAdd" id="scoreAdd"></span></div>
					<div class="object" id="object">
						<div class="feedback" id="feedback"></div>
						<div class="target"></div>
						<div class="bar"></div>
						<div class="cue" id="cue"></div>	
					</div>
				</div>
			</div>`;

			// show elements
			$("#object").fadeIn(500);
			$("#scorepanel").fadeIn(1000);

			// create data storage variables
			var dataResponse = new Array;
			var dataAccuracy = new Array;
			var dataRT = new Array;
			var dataSSD = new Array;
			var dataStopTrial = new Array;
			var dataBarStopTime = new Array;
			var dataPoints = new Array;
			var dataCueTrial = new Array;

			var expStart = ((new Date()).getTime()/1000); // unix timestamp in milliseconds

			for (var i = 1; i < nTrials; i++) {  // trial loop
				setTimeout(function(){
					var trialStart = (new Date).getTime();
					var isResponse = 0;
					var RT = 0;
					var barStopTime = 0;
					var isStopTrial = 0;
					var isAccurate = 1;
					var iTrial = dataPoints.length; 

					$bar = $('.bar')
					$bar.css('height', 0).animate({height:'100%'}, barRaiseTime) // animation speed

					if (seqCue[iTrial] > 0) {  // cue p > 0%
						if (seqCue[iTrial] == 1) { 
							cue = cueB.symbol;	// low penalty
							$('#cue').css("color", cueB.color);
							penalty = cueB.penalty;
						} else {
							cue = cueC.symbol;  // high penalty
							$('#cue').css("color", cueC.color);
							penalty = cueC.penalty;
						}
						
						if (seqStop[iTrial] == 0) { // no stop
							$('#cue').text(cue);
							setTimeout(function(){
								$("#cue").text("");
							}, (cueDuration)); 
						} else {
							$('#cue').text(cue); // stop
							setTimeout(function(){
								$("#cue").text("");
							}, (cueDuration)); 
							setTimeout(function(){
								isStopTrial = 1;
								$bar.stop();
								barStopTime = (new Date).getTime() - trialStart;
								document.body.onkeydown = function(e){
					            	if(e.keyCode){
					            		RT = (new Date).getTime() - trialStart;
										isResponse = 1; 
										isAccurate = 0; // incorrect press
										score -= penalty;
										stopSignalDelay -= 50; // decrease difficulty
										$('#feedback').text("X");

										$('#scoreAdd').text("- " + penalty);
										$('#scoreAdd').css("color", "red");
										setTimeout(function(){
											$('#scoreAdd').text("");
										}, (2000));
										$('#score').text(score);

										setTimeout(function(){
											$("#feedback").text("");
										}, (500)); // feedback duration
									} 
								}
							}, (stopSignalDelay)); 
						}
					} else { // cue p == 0%
						cue = cueA.symbol;
						penalty = cueA.penalty;
						$('#cue').css("color", cueA.color);
						$('#cue').text(cue);
						setTimeout(function(){
							$("#cue").text("");
						}, (cueDuration)); // feedback duration
					}

					document.body.onkeydown = function(e){
			            if(e.keyCode){ // correct response on go trial
							if ($bar.is(':animated') && isStopTrial == 0) {
								RT = (new Date).getTime() - trialStart;
								penalty = Math.round(50 - Math.abs((1350 - Math.abs(RT))/2));
								if (penalty < 0) { penalty = 0; }
								score += penalty;
								isResponse = 1;
								isAccurate = 1;
								$bar.stop();
								$('#scoreAdd').text("+ " + penalty);
								$('#scoreAdd').css("color", "green");
								setTimeout(function(){
									$('#scoreAdd').text("");
								}, (2000));
								$('#score').text(score);			
							}
						}
					}

					setTimeout(function(){ // no response on go trial
						if (isResponse == 0 && isStopTrial == 0) {
							isAccurate = 0;
							score -= penalty;
							$('#feedback').text("X");
							setTimeout(function(){
								$("#feedback").text("");
							}, (500)); // feedback duration
						}
					}, (barRaiseTime)); // if not pressed after X ms.. 


					setTimeout(function(){ // log responses at end of trial

						if (isAccurate == 1 && isResponse == 0 && stopSignalDelay < 1300) {
							stopSignalDelay += 50; // increase difficulty
							score += penalty;
						} 

						if (isResponse == 0) { // too slow response
							if (isAccurate == 1) {
								$('#scoreAdd').text("+ " + penalty);
								$('#scoreAdd').css("color", "green");
							} else {
								$('#scoreAdd').text("- " + penalty);
								$('#scoreAdd').css("color", "red");
							}
							setTimeout(function(){
								$('#scoreAdd').text("");
							}, (2000));
							$('#score').text(score);	
						}

						// save data to arrays
						dataCueTrial.push(seqCue[iTrial]);
						dataResponse.push(isResponse);
						dataAccuracy.push(isAccurate);
						dataStopTrial.push(isStopTrial);
						dataRT.push(RT);
						dataSSD.push(stopSignalDelay);
						dataBarStopTime.push(barStopTime);
						dataPoints.push(score);

						console.log(">> Response: " + dataResponse);
						console.log(">> Accuracy: " + dataAccuracy);
						console.log(">> RT: " + dataRT);
						console.log(">> SSD: " + dataSSD);
					}, (trialDuration - 500)); 
							
				}, (trialDuration * i)); // trial duration
			}

			setTimeout(function(){ // save data at end of experiment
				$bar.css('height', 0).animate({height:'0%'}, 100);
				$.ajax({
				    type: 'POST',
				    url: 'saveLog.php',                
				    data: {sessionHash:sessionHash,dataCueTrial:dataCueTrial,dataStopTrial:dataStopTrial,dataResponse:dataResponse,dataAccuracy:dataAccuracy,dataRT:dataRT,dataSSD:dataSSD,dataBarStopTime:dataBarStopTime,dataPoints:dataPoints},
				 	success: function(data) {}  
				})

				$("#object").fadeOut(1000);
				$("#scorepanel").fadeOut(1000);
					setTimeout(function(){
						document.getElementById("mainContent").innerHTML = `
						<div class="parent">
							<div class="instructions">
							<h3>Einde experiment.</h3>
							<p>Score: `+ score +`</p>
							</div>
						</div>`;
				}, (1500));
			}, ((trialDuration * nTrials) + 100));
		}
	}
})
</script>

<div class="mainContent" id="mainContent"></div>



