<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Import_model extends CI_Model {

  var $table = 'students';

  /*
   * Get all students data from the database
  */
  public function get_all_students() {
    return $this->db->get($this->table)->result_array();
  }

  /*
     * Fetch applicants data from the database
     * @param array filter data based on the passed parameters
     */
  function get_rows($params = array()) {
    $this->db->select('*');
    $this->db->from($this->table);

    if (array_key_exists("where", $params)) {
      foreach ($params['where'] as $key => $val) {
        $this->db->where($key, $val);
      }
    }

    if (array_key_exists("returnType", $params) && $params['returnType'] == 'count') {
      $result = $this->db->count_all_results();
    } else {
      if (array_key_exists("app_id", $params)) {
        $this->db->where('app_id', $params['app_id']);
        $query = $this->db->get();
        $result = $query->row_array();
      } else {
        $this->db->order_by('app_id', 'desc');
        if (array_key_exists("start", $params) && array_key_exists("limit", $params)) {
          $this->db->limit($params['limit'], $params['start']);
        } elseif (!array_key_exists("start", $params) && array_key_exists("limit", $params)) {
          $this->db->limit($params['limit']);
        }

        $query = $this->db->get();
        $result = ($query->num_rows() > 0) ? $query->result_array() : FALSE;
      }
    }

    // Return fetched data
    return $result;
  }


  /*
     * Insert students data into the database
     * @param $data data to be insert based on the passed parameters
     */
  public function insert($data = array()) {
    if (!empty($data)) {
      // Add created and modified date if not included
      if (!array_key_exists("created", $data)) {
        $data['added_on'] = date("Y-m-d H:i:s");
      }
      if (!array_key_exists("updated", $data)) {
        $data['updated_on'] = date("Y-m-d H:i:s");
      }

      // Insert applicants data
      $insert = $this->db->insert($this->table, $data);

      // Return the status
      return $insert ? $this->db->insert_id() : false;
    }
    return false;
  }

  /*
     * Update student data into the database
     * @param $data array to be update based on the passed parameters
     * @param $condition array filter data
     */
  public function update($data, $condition = array()) {
    if (!empty($data)) {
      // Add modified date if not included
      if (!array_key_exists("updated", $data)) {
        $data['updated_on'] = date("Y-m-d H:i:s");
      }

      // Update member data
      $update = $this->db->update($this->table, $data, $condition);

      // Return the status
      return $update ? true : false;
    }
    return false;
  }
}
