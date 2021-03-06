<?php 
//calculate gross pay, taxes and net pay
const REGULAR_HOURS = 40;
const REGULAR_SENIOR_HOURLY_RATE = 50;
const REGULAR_JUNIOR_HOURLY_RATE = 0.7 * REGULAR_SENIOR_HOURLY_RATE;
const SENIOR_OVERTIME_RATE = 1.5 * REGULAR_SENIOR_HOURLY_RATE;
const JUNIOR_OVERTIME_RATE = 1.25 * REGULAR_JUNIOR_HOURLY_RATE;

const INCOME_TAX = 0.15;
const NHIL_TAX = 0.01;
const DISTRICT_TAX = 0.03;
const GETFUND_TAX = 1;


function calcGrossPay($rank, $hoursWorked) : float {
	if ($rank == strtolower('senior')) {
		$hourlyRate = REGULAR_SENIOR_HOURLY_RATE;
		$overtimeRate = SENIOR_OVERTIME_RATE;
	}elseif ($rank == strtolower('junior')){
		$hourlyRate = REGULAR_JUNIOR_HOURLY_RATE;
		$overtimeRate = JUNIOR_OVERTIME_RATE;
	}else{
		echo "Provide rank of staff. Eg: senior/junior";
		return false;
	}
  	if ($hoursWorked > REGULAR_HOURS) {
  		$pay = REGULAR_HOURS * $hourlyRate;
  		$overtimeHours = $hoursWorked - REGULAR_HOURS;
  		$overTimePay = $overtimeRate * $overtimeHours;
  		$grossPay = $pay + $overTimePay;
  		return $grossPay;
  	}else{
  		$grossPay = $hoursWorked * $hourlyRate;
  		return $grossPay;
  	}
}

function calcIncomeTax($rank, $hoursWorked): float {
	$incomeTax = INCOME_TAX * calcGrossPay($rank, $hoursWorked);
	return $incomeTax;
}

function calcNHIL($rank, $hoursWorked): float{
	 $nhil = NHIL_TAX * calcGrossPay($rank, $hoursWorked);
	 return $nhil;
}

function calcDistrictTax($rank, $hoursWorked): float {
	 $districtTax = DISTRICT_TAX * calcGrossPay($rank, $hoursWorked);
	 return $districtTax;
}

function calcGetFund($children): float {
	if ($children > 3) {
		$remainigChildren = $children - 3;
		$getFund = GETFUND_TAX * $remainigChildren;
		return $getFund;
	}else{
		$getFund = 0;
		return $getFund;
	}
}

function calcDeductions($rank, $hoursWorked, $children){
	$taxes = calcIncomeTax($rank, $hoursWorked) + calcNHIL($rank, $hoursWorked) + calcDistrictTax($rank, $hoursWorked) + calcGetFund($children);
	return $taxes;
}

function calcNetPay($rank, $hoursWorked, $children): float {
	$netPay = calcGrossPay($rank, $hoursWorked) - calcDeductions($rank, $hoursWorked, $children);
	return $netPay;
}

?>



<!-- html -->
<!DOCTYPE html>
<html>
<head>
<title>Staff Salary App</title>
</head>
<body>
<!-- <div class="container"> -->
<h1>Welcome to the Staff Salary App</h1>

<form method="POST" enctype="multipart/form-data">
	<p>Upload csv file of staff data in the following format:</p>
	<p>csv format: <strong>staff,rank,hours worked,children</strong></p>
	<label for="uploadedFile">Upload staff data: </label>
	<input type="file" name="uploadedFile" id="uploadedFile"><br>
	<input type="submit" name="upload" value="Upload"><br><br>
</form>
<form method="POST">
	<p>Fill the form to calculate your salary</p>
	<input type="text" name="name" id="name" placeholder="Name" required><br>
	<label for="rank">Select rank:</label>
	<select name="rank" id="rank" placeholder="rank" required>
	    <option value="junior">junior</option>
	    <option value="senior">senior</option>
  	</select><br>
	<input type="number" step="any" name="hoursWorked" id="hoursWorked" placeholder="Hours worked" required><br>
	<input type="number" step="any" name="children" id="children" placeholder="Children" required><br>
	<input type="submit" name="calculate" value="Calculate"><br><br>
</form>


<?php 

function showResults($name,$rank,$hoursWorked,$children){
	$hoursWorked = floatval($hoursWorked); //handle hours given in decimals
	$children = intval($children); //make sure number of children is not decimal
	$resultTxt = $name . ", " . $rank . ", " . $hoursWorked."hrs" . ", " . $children . ", GH₵" . calcGrossPay($rank, $hoursWorked) . ", GH₵" . calcIncomeTax($rank, $hoursWorked) . ", GH₵" . calcNHIL($rank, $hoursWorked) . ", GH₵" . calcDistrictTax($rank, $hoursWorked) . ", GH₵" . calcGetFund($children) . ", GH₵" . calcNetPay($rank, $hoursWorked, $children)."\n";
	return $resultTxt;
}

//to display heading of results
$heading = "<strong>staff ,rank ,hours worked ,children, gross pay, income tax, nhil, district tax, getfund, net pay \n</strong>";




//check if file is uploaded
if(isset($_POST["upload"]) && isset($_FILES['uploadedFile'])) {
	$fileName = basename($_FILES["uploadedFile"]["name"]);
	$target_file = "csvDir/" . $fileName;
	$fileExtension = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	$uploadOk = 1;
	// Check if csv file is a actual csv or fake csv
	if($fileExtension == 'csv') {
		$uploadOk = 1;
	} else {
		echo "Not a csv file!<br>";
		die("Unable to open file!");
		$uploadOk = 0;
	}

	// Check if file already exists
	if (file_exists($target_file)) {
		echo "Kindly, change file name.<br>";
		$uploadOk = 0;
	}

	// Check file size
	if ($_FILES["uploadedFile"]["size"] > 5000) {
		echo "Sorry, your file is too large.<br>";
		$uploadOk = 0;
	}

	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		echo "Sorry, your file was not uploaded.<br>";
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["uploadedFile"]["tmp_name"], $target_file)) {
		echo "Your file has been uploaded.<br>";
		} else {
		echo "Sorry, there was an error uploading your file.<br>";
		}
	}




	//process the staff data  and display
	$file = fopen($target_file,"r");
	$finishedFileName = "csvDir/[finished]";
	$finishedFile = fopen($finishedFileName.$fileName, "w") or die("Unable to open file!");
	$data = [];
	$totalGrossPay = 0;
	$totalNetPay = 0;
	$totalDeductions = 0;
	$count = 0;
	if (($file = fopen($target_file,"r")) !== false) {
		echo "<p>RESULTS: <em>" .  $fileName . "</em></p>";
		echo $heading;
		echo '<pre>';
		fwrite($finishedFile, $heading);
		while(($data = fgetcsv($file, 100, ",")) !== false){
			if (!is_numeric($data[2])) continue; //skip if there is heading in uploaded file
			$count++;
			$resultTxt = showResults($data[0],$data[1],$data[2],$data[3]);
			echo $resultTxt;
			fwrite($finishedFile, $resultTxt);
			$totalGrossPay += calcGrossPay($data[1], $data[2]);
			$totalNetPay += calcNetPay($data[1], $data[2], $data[3]);
			$totalDeductions += calcDeductions($data[1], $data[2], $data[3]);
		}
		
		$avgTotalNetPay = $totalNetPay/($count);
		$totals =  "\nNumber of employees: " . $count."\n".
		 "Total Gross Pay paid to all employees: GH₵" . $totalGrossPay."\n".
		 "Total Net Pay paid to all employees: GH₵" . $totalNetPay."\n".
		 "Average Net Pay of all employees: GH₵" . $avgTotalNetPay."\n".
		 "Total deductions paid by all employees: GH₵" . $totalDeductions."\n";
		echo "<br>". $totals;
		echo '</pre>';
		fwrite($finishedFile, $totals);
		echo "<a href='".$finishedFileName.$fileName."'>Download results</a>";
		fclose($file);
		fclose($finishedFile);
	}
	
}

if (isset($_POST['calculate'])) {
	//make sure all fields are filled
	if(count(array_filter($_POST)) != count($_POST)){
		echo "All fields are required!";
		return;
	}
	$resultTxt = showResults($_POST['name'],$_POST['rank'],$_POST['hoursWorked'],$_POST['children']);
	echo "<p>RESULTS:</p>";
	echo $heading;
	echo '<pre>';
	echo $resultTxt;
}

?>

<script>
	//prevent resubmission dialog
	window.history.replaceState(null, null, window.location.href);
</script>
</body>
</html>