<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

// Enable error reporting
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load necessary models, libraries, etc.
        $this->load->model('User_model');
        
    }

  
  public function login() {
    $username = $this->input->post('username');
    $password = $this->input->post('password');
    
    var_dump($this->input->post()); // For debugging, check what is being submitted

    log_message('debug', 'Username: ' . $username);
    log_message('debug', 'Password: ' . $password); // Note: Avoid logging passwords in production

    // Fetch user from the database
    $user = $this->User_model->check_user($username, $password);
    
    // Log fetched user object
    log_message('debug', 'Fetched user: ' . print_r($user, true));

    // Check if the user is found
    if ($user) {
        // Valid user found
        log_message('debug', 'User logged in: ' . $user->username); // Logging the username for debugging
        
        // Set session data
        $this->session->set_userdata([
            'user_id' => $user->user_id,
            'username' => $user->username,
            'role' => $user->role,
            'logged_in' => TRUE
        ]);

        // Redirect based on role
        switch ($user->role) {
            case 'doctor':
                redirect('doctor');
                break;
            case 'receptionist':
                redirect('receptionist');
                break;
            case 'inventory_manager':
                redirect('inventory_manager');
                break;
            default:
                log_message('debug', 'Role not recognized, redirecting to login');
                redirect('auth/login'); // Fallback for unknown roles
        }
    } else {
        // Handle invalid login (password mismatch or user not found)
        log_message('debug', 'Invalid login attempt');
        redirect('auth/login');
    }
}



   public function login_view() {
    // Check if user is already logged in
    if ($this->session->userdata('logged_in')) {
        // Redirect to the appropriate controller based on the role
        if ($this->session->userdata('role') === 'doctor') {
            redirect('doctor');
        } elseif ($this->session->userdata('role') === 'receptionist') {
            redirect('receptionist');
        } elseif ($this->session->userdata('role') === 'inventory_manager') {
            redirect('inventory_manager');
        }
    } else {
        // Load login page view
        // $data = array();
        $data=array();
        $data['base_url'] = base_url();
        $this->load->view('common/head',$data); // Load header
        $this->load->view('auth/login'); // Load login view
        $this->load->view('common/footer',$data); // Load footer
    }
}



    public function logout() {
        $this->session->sess_destroy();
        redirect('home');
    }
}
