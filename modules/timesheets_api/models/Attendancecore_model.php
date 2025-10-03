<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Attendancecore_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function authenticate($email, $password)
    {
        $this->db->select('s.*, jp.position_name');
        $this->db->from(db_prefix() . 'staff as s');
        $this->db->join('tblhr_job_position as jp', 'jp.position_id = s.job_position', 'LEFT');
        $this->db->where('s.email', $email);

        $user = $this->db->get()->row();

        if ($user && app_hasher()->CheckPassword($password, $user->password)) {
            return $user;
        }

        return false;
    }
}
