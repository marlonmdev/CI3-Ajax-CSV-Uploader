<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<title>CI3 CSV Import</title>
	<link rel="stylesheet" href="<?= base_url() ?>assets/vendors/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?= base_url() ?>assets/vendors/bootstrap-icons/bootstrap-icons.css">
	<link rel="stylesheet" href="<?= base_url() ?>assets/vendors/toastr/build/toastr.min.css">
	<link rel="stylesheet" href="<?= base_url() ?>assets/vendors/dataTables/datatables.min.css">
</head>
<style type="text/css">
	.btn {
		padding: 8px 16px;
	}

	.btn,
	input[type="file"] {
		border-radius: 5px;
	}
</style>

<body>
	<div class="container mt-2">
		<h2 class="text-center">CI3 AJAX CSV IMPORT</h2>

		<div class="alert alert-success alert-dismissible fade show mt-4 d-none" id="successAlert" role="alert">
			<h3 class="text-center"><strong><i class="bi bi-check-circle-fill"></i> Success!</strong></h3>
			<h5 class="text-center" id="successMsg"></h5>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>

		<!-- File upload form -->
		<div class="row d-flex justify-content-center align-items-center my-4" id="importForm">
			<form method="post" enctype="multipart/form-data" id="importCsvForm">
				<div class="col-md-8 offset-2">
					<div class="form-group input-group-lg">
						<input type="file" name="file" class="form-control" id="csv" accept=".csv" required>
					</div>
				</div>
				<div class="col-md-12 d-flex justify-content-center mt-3">
					<button type="button" class="btn btn-primary" id="importCsvBtn">
						<img src="<?php echo base_url(); ?>assets/images/preloader.gif" class="d-none" width="30px" alt="Loader" id="loaderGif">
						<strong id="importCsvBtnText">
							UPLOAD CSV FILE
						</strong>
					</button>
					&nbsp;&nbsp;
					<a href="<?= base_url() ?>format-download" type="button" class="btn btn-success">
						<strong style="vertical-align:middle;">
							DOWNLOAD FORMAT
						</strong>
					</a>
					<br><br>
				</div>
			</form>
			<div class="row mt-3">
				<textarea class="form-control" name="json" id="json" cols="20" rows="10"></textarea>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<table class="table table-striped table-bordered" id="studentsTable">
					<thead>
						<tr>
							<th>Student ID</th>
							<th>Name</th>
							<th>Age</th>
							<th>Date of Birth</th>
							<th>Gender</th>
							<th>Email</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>

</body>
<script src="<?= base_url() ?>assets/vendors/jquery/jquery.min.js"></script>
<script src="<?= base_url() ?>assets/vendors/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url() ?>assets/vendors/toastr/build/toastr.min.js"></script>
<script src="<?= base_url() ?>assets/vendors/dataTables/datatables.min.js"></script>
<script>
	$(document).ready(function() {

		const baseUrl = "<?php echo base_url() ?>";
		const successMsg = document.querySelector('#successMsg');
		const errorMsg = document.querySelector('errorMsg');

		// initialized datatables
		$("#studentsTable").DataTable({
			ajax: {
				url: `${baseUrl}fetch-students`,
				dataSrc: function(data) {
					if (data == "") {
						return [];
					} else {
						return data.data;
					}
				}
			},
			responsive: true,
			fixedHeader: true
		});

		// When the 'Import Data' button is clicked, it will first make sure if the csv file is uploaded and then it goes to the csvjsonConverter function below to convert it from CSV to JSON. Afterwards, it will send the result using ajax request
		$("#importCsvBtn").click(function() {
			let csv = $("#csv")[0].files[0];
			let url = "<?php echo base_url(); ?>students-import";
			if (csv !== undefined) {
				showLoader();
				let reader = new FileReader();
				reader.onload = function(e) {
					let rows = e.target.result;
					let convertjson = csvjsonConverter(rows);
					// ajax post request
					$.ajax({
						url: url,
						method: 'post',
						data: {
							json: convertjson,
						},
						dataType: "json",
						success: function(response) {
							$('#importCsvForm')[0].reset();
							hideLoader();
							if (response.status == "success") {
								$('#successAlert').removeClass('d-none');
								successMsg.innerHTML = response.message;
								$("#studentsTable").DataTable().ajax.reload();
								$("#json").val(convertjson);
							} else {
								toastr.options = {
									closeButton: true,
									preventDuplicates: true,
								};
								toastr["error"](response.message);
							}
						},
					});
					// end of ajax post request
				};
				reader.readAsText(csv);
			} else {
				$("#json").val("");
				toastr.options = {
					closeButton: true,
					preventDuplicates: true,
				};
				toastr["error"]('Please select CSV file to upload!');
			}
		});

	});

	function showLoader() {
		$("#importCsvBtnText").html("IMPORTING CSV");
		$("#loaderGif").removeClass("d-none");
	}

	function hideLoader() {
		$("#loaderGif").addClass("d-none");
		$("#importCsvBtnText").html("IMPORT CSV");
	}

	//Function for converting from CSV to JSON. This function is consider as a backend component for performing this task.
	function csvjsonConverter(csvdata, delimiter = ",") {
		//This array will store the each of the patterns from the regular expression below.
		let arrmatch = [];
		//This array will store the data from the CSV.
		let array = [
			[]
		];
		//Stores matched values for quoted values.
		let quotevals = "";
		//Storing JSON array
		let jsonarray = [];
		//Increment value
		let k = 0;
		//Uses regular expression to parse the CSV data and determines if any values has their own quotes in case if any
		// delimiters are within.
		let regexp = new RegExp(
			"(\\" +
			delimiter +
			"|\\r?\\n|\\r|^)" +
			'(?:"([^"]*(?:""[^"]*)*)"|' +
			'([^"\\' +
			delimiter +
			"\\r\\n]*))",
			"gi"
		);
		//This will loop to find any matchings with the regular expressions.
		while ((arrmatch = regexp.exec(csvdata))) {
			//This will determine what the delimiter is.
			let delimitercheck = arrmatch[1];
			//Matches the delimiter and determines if it is a row delimiter and matches the values to the first rows.
			//If it reaches to a new row, then an empty array will be created as an empty row in array.
			if (delimitercheck !== delimiter && delimitercheck.length) {
				array.push([]);
			}
			//This determines as to what kind of value it is whether it has quotes or not for these conditions.
			if (arrmatch[2]) {
				quotevals = arrmatch[2].replace('""', '"');
			} else {
				quotevals = arrmatch[3];
			}
			//Adds the value from the data into the array
			array[array.length - 1].push(quotevals);
		}
		//This will parse the resulting array into JSON format
		for (let i = 0; i < array.length - 1; i++) {
			jsonarray[i - 1] = {};
			for (let j = 0; j < array[i].length && j < array[0].length; j++) {
				let key = array[0][j];
				jsonarray[i - 1][key] = array[i][j];
			}
		}
		//This will determine what the properties of each values are from the JSON
		//such as removing quotes for integer value.
		for (k = 0; k < jsonarray.length; k++) {
			let jsonobject = jsonarray[k];
			for (let prop in jsonobject) {
				if (!isNaN(jsonobject[prop]) && jsonobject.hasOwnProperty(prop)) {
					jsonobject[prop] = +jsonobject[prop];
				}
			}
		}
		//This will stringify the JSON and formatting it.
		let formatjson = JSON.stringify(jsonarray, null, 2);
		//Returns the converted result from CSV to JSON
		return formatjson;
	};
</script>

</html>