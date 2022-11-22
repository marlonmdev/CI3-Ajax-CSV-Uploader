<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Import_controller extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->model('import_model');
	}

	public function index() {
		$this->load->view('index');
	}

	public function fetch_students() {
		$rows = $this->import_model->get_all_students();
		$result = array();
		foreach ($rows as $row) {
			// this is for datatable values
			$result['data'][] = array(
				$row['stud_id'],
				$row['name'],
				$row['age'],
				date("m/d/Y", strtotime($row['date_of_birth'])),
				$row['gender'],
				$row['email']
			);
		}
		echo json_encode($result);
	}

	public function import_csv_to_database() {
		$data = $this->input->post('json');
		$students = json_decode($data, TRUE);
		$insertedCount = $updatedCount = $rowCount = $notAddCount = 0;
		if ($students) {
			foreach ($students as $row) {
				$rowCount++;
				// ?: '' -> ternary operator shorthand: returns current value if exist else return empty string
				$post_data = array(
					'stud_id' => $row['Student ID'] ?: '',
					'name' => $row['Name'] ?: '',
					'age' => $row['Age'] ?: '',
					'gender' => $row['Gender'] ?: '',
					'date_of_birth' => date("Y-m-d", strtotime($row['Date Of Birth'])) ?: '',
					'email' => $row['Email'] ?: '',
				);

				// Check whether emp_id already exists in the database
				$con = array(
					'where' => array(
						'stud_id' => $row['Student ID']
					),
					'returnType' => 'count'
				);
				$prevCount = $this->import_model->get_rows($con);

				if ($prevCount > 0) {
					// Update student's data
					$condition = array('stud_id' => $row['Student ID']);
					$update = $this->import_model->update($post_data, $condition);
					if ($update) {
						$updatedCount++;
					}
				} else if ($prevCount <= 0 && $row['Student ID'] != '' && $row['Student ID'] != 0) {
					// Insert student's data
					$insert = $this->import_model->insert($post_data);
					if ($insert) {
						$insertedCount++;
					}
				}
			}
			$notAddCalc = ($rowCount - ($insertedCount + $updatedCount));
			$notAddedCount = $notAddCalc > 0 ? $notAddCount : 0;
			$successMsg = 'Students Imported successfully. Total Rows (' . $rowCount . ') | Inserted (' . $insertedCount . ') | Updated (' . $updatedCount . ') | Not Inserted (' . $notAddedCount . ')';
			$response = array('status' => 'success', 'message' => $successMsg);
		} else {
			$response = array('status' => 'error', 'message' => 'The uploaded CSV File is empty');
		}

		echo json_encode($response);
	}

	public function csv_format_download() {
		$this->load->helper('download');
		$filePath = base_url() . 'assets/csvFormat/format.csv';
		redirect($filePath);
	}
}
