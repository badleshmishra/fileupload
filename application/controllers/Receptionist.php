<?php defined("BASEPATH") or exit("No direct script access allowed");

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

class Receptionist extends CI_Controller
{
    public function __construct()
    {
        $this->required_role = "receptionist"; // Set role required for this controller
        parent::__construct();
        $this->load->model("Receptionist_model"); // Load the model here
        $this->load->helper("form");
    }

    public function index()
    {
        // Receptionist's dashboard view
        $data["main_content"] = "receptionist/dashboard";
        $data["base_url"] = $this->config->item("base_url");
        $this->load->view("common/template", $data);
    }
    public function add_patient()
    {
        $data["doctors"] = $this->Receptionist_model->get_doctors();
        $data["specialists"] = $this->Receptionist_model->get_specialists();

        // Check if the request is an AJAX request
        if ($this->input->is_ajax_request()) {
            $selected_specialist = $this->input->post("specialist_id");
            $selected_doctor = $this->input->post("doctor_id");

            // Prepare response array
            $response = [];

            // Fetch doctors based on selected specialist
            if (!empty($selected_specialist)) {
                $response[
                    "doctors"
                ] = $this->Receptionist_model->fetch_doctors_by_specialist(
                    $selected_specialist
                );
            }

            // Fetch the room number for the selected doctor
            if (!empty($selected_doctor)) {
                $doctor_info = $this->Receptionist_model->get_doctor_by_id(
                    $selected_doctor
                );
                $response["room_number"] = $doctor_info
                    ? $doctor_info["room_no"]
                    : "";
            }

            // Return the response as JSON
            echo json_encode($response);
            exit();
        }

        // Normal form submission logic...
        if ($this->input->server("REQUEST_METHOD") == "POST") {
            $patientData = [
                "first_name" => $this->input->post("first_name"),
                "age" => $this->input->post("age"),
                "phone" => $this->input->post("phone"),
                "email" => $this->input->post("email"),
                "gender" => $this->input->post("gender"),
                "address" => $this->input->post("address"),
                "room_no" => $this->input->post("room_no"),
                "doctor_id" => $this->input->post("doctor_id"),
            ];

            // Add basic validation
            if (
                !empty($patientData["first_name"]) &&
                !empty($patientData["age"]) &&
                !empty($patientData["phone"]) &&
                !empty($patientData["email"]) &&
                !empty($patientData["doctor_id"])
            ) {
                if ($this->Receptionist_model->insert_patient($patientData)) {
                    $this->session->set_flashdata(
                        "success",
                        "Patient added successfully!"
                    );
                    redirect("receptionist/add_patient");
                } else {
                    $this->session->set_flashdata(
                        "error",
                        "Failed to add patient. Please try again."
                    );
                }
            } else {
                $this->session->set_flashdata(
                    "error",
                    "All fields are required."
                );
            }
        }
        $data["base_url"] = $this->config->item("base_url"); // Base URL for links

        $data["main_content"] = "receptionist/add_patient";
        $this->load->view("common/template", $data);
    }

    public function patient()
    {
        $data["patients"] = $this->Receptionist_model->patient();

        if (!$data["patients"]) {
            log_message("error", "No patients found in the database.");
        }

        // Set the main content for the template
        $data["main_content"] = "receptionist/patient";
        $data["base_url"] = $this->config->item("base_url");

        // Load the view with the data
        $this->load->view("common/template", $data);
    }

    public function getDoctors()
    {
        $specialistId = $this->input->get("specialty_id");
        $doctors = $this->Receptionist_model->fetchDoctorsBySpecialist(
            $specialistId
        );
        echo json_encode($doctors);
    }
    public function patient_details()
    {
        // Initialize the data array and the patient array
        $data = []; // This will hold the view data
        $patient = []; // This will hold patient details

        // Get the patient ID or name from the POST parameters
        $patient_id = isset($_POST["patient_id"]) ? $_POST["patient_id"] : null;
        $patient_name = isset($_POST["patient_name"])
            ? $_POST["patient_name"]
            : null;

        // Check if the form has been submitted
        if ($patient_id || $patient_name) {
            // Determine if the input is an ID or a name
            if ($patient_id) {
                // If patient_id is set, treat it as patient ID
                $patient["details"] = $this->Receptionist_model->Patient(
                    $patient_id
                );
                // Check if the patient exists
                if (empty($patient["details"])) {
                    $this->session->set_flashdata(
                        "message",
                        "Patient not found."
                    );
                }
            } elseif ($patient_name) {
                // Otherwise, treat it as patient name
                $patient["details"] = $this->Receptionist_model->Patient(
                    null,
                    $patient_name
                );
                // Check if the patient exists
                if (empty($patient["details"])) {
                    $this->session->set_flashdata(
                        "message",
                        "Patient not found."
                    );
                }
            }
        }

        // Fetch all patients to display regardless of search
        $data["all_patients"] = $this->Receptionist_model->Patient();

        // Prepare data for the view
        $data["main_content"] = "receptionist/patient_details"; // View file to load
        $data["base_url"] = $this->config->item("base_url"); // Base URL for links
        $data["patient"] = $patient; // Pass searched patient data (if any) to the view

        // Load the view with the data
        $this->load->view("common/template", $data);
    }
public function add_doctor()
    {
        // Fetch the list of specialists and roles
        $data["specialists"] = $this->Receptionist_model->get_all_specialists();
        $data["roles"] = $this->Receptionist_model->get_all_roles();

        if ($this->input->server("REQUEST_METHOD") == "POST") {
            // Process form data
            $doctorData = [
                "doctor_name" => $this->input->post("doctor_name"),
                "last_name" => $this->input->post("last_name"),
                "phone" => $this->input->post("phone"),
                "room_no" => $this->input->post("room_no"),
                "email" => $this->input->post("email"),
                "gender" => $this->input->post("gender"),
                "specialty_id" => $this->input->post("specialist_id"),
                "address" => $this->input->post("address"),
            ];

            // Check for existing email
            $email = $doctorData["email"];
            $existingUser = $this->Receptionist_model->get_user_by_email(
                $email
            );

            if ($existingUser) {
                $this->session->set_flashdata(
                    "error",
                    "This email is already registered. Please use a different email."
                );
                redirect("receptionist/add_doctor"); // Redirect to the form
            }

            // Create user data
            $userData = [
                "username" => $doctorData["doctor_name"],
                "password" => password_hash(
                    $this->input->post("password"),
                    PASSWORD_DEFAULT
                ),
                "role" => $this->input->post("role"),
                "email" => $doctorData["email"],
                "specialty_id" => $doctorData["specialty_id"],
            ];

            // Insert user first
            $user_id = $this->Receptionist_model->add_new_user($userData);
            if ($user_id) {
                // Set user_id in doctorData
                $doctorData["user_id"] = $user_id;

                // Insert based on role with specific column names
                switch ($userData["role"]) {
                    case "doctor":
                        if (
                            $this->Receptionist_model->add_new_doctor(
                                $doctorData
                            )
                        ) {
                            $this->session->set_flashdata(
                                "success",
                                "Doctor added successfully!"
                            );
                        } else {
                            $this->session->set_flashdata(
                                "error",
                                "Failed to add doctor. Please try again."
                            );
                        }
                        break;

                    case "inventory_manager":
                        // Prepare inventory manager data with user_id
                        $inventoryData = [
                            "user_id" => $user_id, // Include the user_id
                            "manager_name" => $doctorData["doctor_name"],
                            "phone" => $doctorData["phone"],
                            "email" => $doctorData["email"],
                            "address" => $doctorData["address"],
                            // Add any other specific fields for inventory manager
                        ];
                        if (
                            $this->Receptionist_model->add_new_inventory_manager(
                                $inventoryData
                            )
                        ) {
                            $this->session->set_flashdata(
                                "success",
                                "Inventory Manager added successfully!"
                            );
                        } else {
                            $this->session->set_flashdata(
                                "error",
                                "Failed to add inventory manager. Please try again."
                            );
                        }
                        break;

                    case "receptionist":
                        // Prepare receptionist data with user_id
                        $receptionistData = [
                            "user_id" => $user_id, // Include the user_id
                            "receptionist_name" => $doctorData["doctor_name"],
                            "phone" => $doctorData["phone"],
                            "email" => $doctorData["email"],
                            "address" => $doctorData["address"],
                            // Add any other specific fields for receptionist
                        ];
                        if (
                            $this->Receptionist_model->add_new_receptionist(
                                $receptionistData
                            )
                        ) {
                            $this->session->set_flashdata(
                                "success",
                                "Receptionist added successfully!"
                            );
                        } else {
                            $this->session->set_flashdata(
                                "error",
                                "Failed to add receptionist. Please try again."
                            );
                        }
                        break;

                    default:
                        $this->session->set_flashdata(
                            "error",
                            "Invalid role selected. Please try again."
                        );
                        break;
                }

                redirect("receptionist/add_doctor");
            } else {
                $this->session->set_flashdata(
                    "error",
                    "Failed to create user. Please try again."
                );
            }
        }

        // Load the view
        $data["base_url"] = $this->config->item("base_url");
        $data["main_content"] = "receptionist/add_doctor";
        $this->load->view("common/template", $data);
    }


 public function doctor_details()
{
    // Initialize the data array and the doctor array
    $data = []; // This will hold the view data
    $doctor = []; // This will hold doctor details

    // Get the doctor ID or name from the POST parameters
    $doctor_id = isset($_POST["doctor_id"]) ? $_POST["doctor_id"] : null;
    $doctor_name = isset($_POST["doctor_name"])
        ? $_POST["doctor_name"]
        : null;

    // Check if the form has been submitted
    if ($doctor_id || $doctor_name) {
        // Determine if the input is an ID or a name
        if ($doctor_id) {
            // If doctor_id is set, treat it as doctor ID
            $doctor["details"] = $this->Receptionist_model->Doctor(
                $doctor_id
            );
            // Check if the doctor exists
            if (empty($doctor["details"])) {
                $this->session->set_flashdata(
                    "message",
                    "Doctor not found."
                );
            }
        } elseif ($doctor_name) {
            // Otherwise, treat it as doctor name
            $doctor["details"] = $this->Receptionist_model->Doctor(
                null,
                $doctor_name
            );
            // Check if the doctor exists
            if (empty($doctor["details"])) {
                $this->session->set_flashdata(
                    "message",
                    "Doctor not found."
                );
            }
        }
    }

    // Fetch all doctors to display regardless of search
    $data["all_doctors"] = $this->Receptionist_model->Doctor();

    // Prepare data for the view
    $data["main_content"] = "receptionist/doctor_details"; // View file to load
    $data["base_url"] = $this->config->item("base_url"); // Base URL for links
    $data["doctor"] = $doctor; // Pass searched doctor data (if any) to the view

    // Load the view with the data
    $this->load->view("common/template", $data);
}

public function delete_doctor()
{
    // Get the doctor ID from the POST parameters
    $doctor_id = $this->input->post('delete_id');

    // Get the referring URL (previous page)
    $previous_page = $this->input->server('HTTP_REFERER');

    // Check if a doctor ID was provided
    if ($doctor_id) {
        // Call the model function to delete the doctor
        $deleted = $this->Receptionist_model->deleteDoctor($doctor_id);

        if ($deleted) {
            // Set a success message if the doctor was deleted
            $this->session->set_flashdata('message', 'Doctor deleted successfully.');
        } else {
            // Set an error message if the doctor couldn't be deleted
            $this->session->set_flashdata('message', 'Unable to delete the doctor.');
        }
    }

    // Redirect back to the previous page
    if ($previous_page) {
        redirect($previous_page);
    } else {
        // If no referring URL is available, fall back to a default page (like doctor details)
        redirect('receptionist/doctor_details');
    }
}


}