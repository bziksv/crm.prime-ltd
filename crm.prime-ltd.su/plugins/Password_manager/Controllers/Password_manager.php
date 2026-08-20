<?php

namespace Password_manager\Controllers;

class Password_manager extends \App\Controllers\Security_Controller {

    protected $Password_manager_categories_model;
    protected $Password_manager_general_model;
    protected $Password_manager_email_model;
    protected $Password_manager_credit_card_model;
    protected $Password_manager_bank_account_model;
    protected $Password_manager_software_license_model;

    function __construct() {
        parent::__construct();
        $this->Password_manager_categories_model = new \Password_manager\Models\Password_manager_categories_model();
        $this->Password_manager_general_model = new \Password_manager\Models\Password_manager_general_model();
        $this->Password_manager_email_model = new \Password_manager\Models\Password_manager_email_model();
        $this->Password_manager_credit_card_model = new \Password_manager\Models\Password_manager_credit_card_model();
        $this->Password_manager_bank_account_model = new \Password_manager\Models\Password_manager_bank_account_model();
        $this->Password_manager_software_license_model = new \Password_manager\Models\Password_manager_software_license_model();
    }

    //categories view
    function categories() {
        $this->access_only_team_members();

        return $this->template->rander("Password_manager\Views\categories\index");
    }

    //show add/edit category modal
    function category_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $view_data['model_info'] = $this->Password_manager_categories_model->get_one($id);
        return $this->template->view('Password_manager\Views\categories\modal_form', $view_data);
    }

    //save category
    function save_category() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "title" => "required",
        ));

        $id = $this->request->getPost('id');
        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "status" => $this->request->getPost('status')
        );

        $save_id = $this->Password_manager_categories_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_category_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //delete/undo a category
    function delete_category() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');
        if ($this->request->getPost('undo')) {
            if ($this->Password_manager_categories_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_category_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Password_manager_categories_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    //prepare categories list data
    function categories_list_data() {
        $list_data = $this->Password_manager_categories_model->get_details()->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_category_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //get a row of category row
    private function _category_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Password_manager_categories_model->get_details($options)->getRow();
        return $this->_make_category_row($data);
    }

    //make a row of category row
    private function _make_category_row($data) {
        return array(
            $data->title,
            $data->description ? $data->description : "-",
            app_lang($data->status),
            modal_anchor(get_uri("password_manager/category_modal_form/"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_category'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_category'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("password_manager/delete_category"), "data-action" => "delete"))
        );
    }

    //Password manager index view
    function index() {
        if ($this->login_user->user_type === "staff") {
            return $this->template->rander("Password_manager\Views\password_manager\index");
        } else {
            $view_data['client_id'] = $this->login_user->client_id;

            return $this->template->rander("Password_manager\Views\clients\password_manager\index", $view_data);
        }
    }

    //Password manager general view
    function general() {
        return $this->template->view("Password_manager\Views\password_manager\general\index");
    }

    //general modal form
    function general_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $model_info = $this->Password_manager_general_model->get_one($id);

        $view_data['categories_dropdown'] = $this->Password_manager_categories_model->get_dropdown_list(array("title"), "id");
        $view_data['members_and_teams_dropdown'] = json_encode(get_team_members_and_teams_select2_data_list());

        $password = $model_info->password;
        $model_info->password = decode_id($password, "password");

        $view_data['model_info'] = $model_info;

        $view_data['clients_dropdown'] = $this->_get_clients_dropdown();
        $view_data['client_id'] = $this->request->getPost('client_id');

        return $this->template->view('Password_manager\Views\password_manager\general\modal_form', $view_data);
    }

    //save general password
    function save_general() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "name" => "required",
            "password" => "required",
        ));

        $id = $this->request->getPost('id');

        //prepare share with data
        $share_with_team_members = $this->request->getPost('share_with_team_members');
        if ($share_with_team_members == "specific") {
            $share_with_team_members = $this->request->getPost('share_with_specific_team_members');
        }

        $share_with_client = $this->request->getPost('share_with_client');
        if ($share_with_client == "specific") {
            $share_with_client = $this->request->getPost('share_with_specific_client_contact');
        }

        $password = encode_id($this->request->getPost('password'), "password");
        $client_id = $this->request->getPost('client_id');
        $created_by_client = $this->request->getPost('created_by_client');

        $data = array(
            "name" => $this->request->getPost('name'),
            "category_id" => $this->request->getPost('category_id'),
            "url" => $this->request->getPost('url'),
            "username" => $this->request->getPost('username'),
            "password" => $password,
            "description" => $this->request->getPost('description'),
            "client_id" => $client_id ? $client_id : 0,
            "share_with" => $share_with_team_members,
            "share_with_client" => $share_with_client
        );

        if (!$id) {
            $data["created_by"] = $this->login_user->id;
            $data["created_at"] = get_current_utc_time();
            $data["created_by_client"] = $created_by_client ? $created_by_client : 0;
        }

        $save_id = $this->Password_manager_general_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_general_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //prepare general list data
    function list_data_of_general() {
        $options = array(
            "is_admin" => $this->login_user->is_admin,
            "user_id" => $this->login_user->id,
            "team_ids" => $this->login_user->team_ids
        );

        $list_data = $this->Password_manager_general_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_general_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //get a row of general row
    private function _general_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Password_manager_general_model->get_details($options)->getRow();
        return $this->_make_general_row($data);
    }

    //make a row of general row
    private function _make_general_row($data) {
        $name = modal_anchor(get_uri("password_manager/view_general"), $data->name, array("title" => app_lang('general') . " #$data->id", "data-post-id" => encode_id($data->id, "general_password_id")));

        //only creator can edit/delete passwords
        $actions = modal_anchor(get_uri("password_manager/view_general"), "<i data-feather='tablet' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_password_details'), "data-modal-title" => app_lang('general') . " #$data->id", "data-post-id" => encode_id($data->id, "general_password_id")));
        if ($data->created_by == $this->login_user->id || ($data->created_by_client == 1 && $this->login_user->is_admin)) {
            $actions = modal_anchor(get_uri("password_manager/general_modal_form/"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_edit_general'), "data-post-id" => $data->id))
                    . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('password_manager_delete_general'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("password_manager/delete_general"), "data-action" => "delete-confirmation"));
        }

        return array(
            $data->id,
            $name,
            $data->category_title,
            $data->url,
            $data->username,
            $actions
        );
    }

    //prepare general view
    function view_general() {
        $this->validate_submitted_data(array(
            "id" => "required"
        ));

        $encrypted_id = $this->request->getPost('id');

        $id = decode_id($encrypted_id, "general_password_id");
        $model_info = $this->Password_manager_general_model->get_details(array("id" => $id))->getRow();

        if ($id && $model_info->id) {
            $password = $model_info->password;
            $model_info->password = decode_id($password, "password");

            $view_data['model_info'] = $model_info;

            return $this->template->view('Password_manager\Views\password_manager\general\view', $view_data);
        } else {
            show_404();
        }
    }

    //delete a general password
    function delete_general() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $model_info = $this->Password_manager_general_model->get_one($id);

        if ($model_info->created_by != $this->login_user->id) {
            app_redirect("forbidden");
        }

        if ($this->Password_manager_general_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    //Password manager email view
    function email() {
        return $this->template->view("Password_manager\Views\password_manager\\email\index");
    }

    //email modal form
    function email_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $model_info = $this->Password_manager_email_model->get_one($id);

        $view_data['categories_dropdown'] = $this->Password_manager_categories_model->get_dropdown_list(array("title"), "id");
        $view_data['members_and_teams_dropdown'] = json_encode(get_team_members_and_teams_select2_data_list());

        $password = $model_info->password;
        $model_info->password = decode_id($password, "password");

        $smtp_password = $model_info->smtp_password;
        $model_info->smtp_password = decode_id($smtp_password, "smtp_password");

        $view_data['model_info'] = $model_info;
        $view_data['clients_dropdown'] = $this->_get_clients_dropdown();
        $view_data['client_id'] = $this->request->getPost('client_id');

        return $this->template->view('Password_manager\Views\password_manager\email\modal_form', $view_data);
    }

    //save email password
    function save_email() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "name" => "required",
            "password" => "required",
        ));

        $id = $this->request->getPost('id');

        //prepare share with data
        $share_with_team_members = $this->request->getPost('share_with_team_members');
        if ($share_with_team_members == "specific") {
            $share_with_team_members = $this->request->getPost('share_with_specific_team_members');
        }

        $share_with_client = $this->request->getPost('share_with_client');
        if ($share_with_client == "specific") {
            $share_with_client = $this->request->getPost('share_with_specific_client_contact');
        }

        $password = encode_id($this->request->getPost('password'), "password");
        $smtp_password = encode_id($this->request->getPost('smtp_password'), "smtp_password");
        $client_id = $this->request->getPost('client_id');
        $created_by_client = $this->request->getPost('created_by_client');

        $data = array(
            "name" => $this->request->getPost('name'),
            "category_id" => $this->request->getPost('category_id'),
            "email_type" => $this->request->getPost('email_type'),
            "auth_method" => $this->request->getPost('auth_method'),
            "host" => $this->request->getPost('host'),
            "port" => $this->request->getPost('port'),
            "username" => $this->request->getPost('username'),
            "password" => $password,
            "description" => $this->request->getPost('description'),
            "smtp_auth_method" => $this->request->getPost('smtp_auth_method'),
            "smtp_host" => $this->request->getPost('smtp_host'),
            "smtp_port" => $this->request->getPost('smtp_port'),
            "smtp_user_name" => $this->request->getPost('smtp_user_name'),
            "smtp_password" => $smtp_password,
            "client_id" => $client_id ? $client_id : 0,
            "share_with" => $share_with_team_members,
            "share_with_client" => $share_with_client
        );
        
        if (!$id) {
            $data["created_by"] = $this->login_user->id;
            $data["created_at"] = get_current_utc_time();
            $data["created_by_client"] = $created_by_client ? $created_by_client : 0;
        }

        $save_id = $this->Password_manager_email_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_email_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //prepare email list data
    function list_data_of_email() {
        $options = array(
            "is_admin" => $this->login_user->is_admin,
            "user_id" => $this->login_user->id,
            "team_ids" => $this->login_user->team_ids
        );

        $list_data = $this->Password_manager_email_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_email_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //get a row of email row
    private function _email_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Password_manager_email_model->get_details($options)->getRow();
        return $this->_make_email_row($data);
    }

    //make a row of email row
    private function _make_email_row($data) {
        $name = modal_anchor(get_uri("password_manager/view_email"), $data->name, array("title" => app_lang('email') . " #$data->id", "data-post-id" => encode_id($data->id, "email_password_id")));

        //only creator can edit/delete passwords
        $actions = modal_anchor(get_uri("password_manager/view_email"), "<i data-feather='tablet' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_password_details'), "data-modal-title" => app_lang('email') . " #$data->id", "data-post-id" => encode_id($data->id, "email_password_id")));
        if ($data->created_by == $this->login_user->id || ($data->created_by_client == 1 && $this->login_user->is_admin)) {
            $actions = modal_anchor(get_uri("password_manager/email_modal_form/"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_edit_email'), "data-post-id" => $data->id))
                    . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('password_manager_delete_email'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("password_manager/delete_email"), "data-action" => "delete-confirmation"));
        }

        return array(
            $data->id,
            $name,
            $data->category_title,
            $data->email_type,
            $data->host,
            $data->port,
            $actions
        );
    }

    //prepare email view
    function view_email() {
        $this->validate_submitted_data(array(
            "id" => "required"
        ));

        $encrypted_id = $this->request->getPost('id');

        $id = decode_id($encrypted_id, "email_password_id");
        $model_info = $this->Password_manager_email_model->get_details(array("id" => $id))->getRow();

        if ($id && $model_info->id) {
            $password = $model_info->password;
            $model_info->password = decode_id($password, "password");

            $smtp_password = $model_info->smtp_password;
            $model_info->smtp_password = decode_id($smtp_password, "smtp_password");

            $view_data['model_info'] = $model_info;

            return $this->template->view('Password_manager\Views\password_manager\email\view', $view_data);
        } else {
            show_404();
        }
    }

    //delete a email
    function delete_email() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $model_info = $this->Password_manager_email_model->get_one($id);

        if ($model_info->created_by != $this->login_user->id) {
            app_redirect("forbidden");
        }

        if ($this->Password_manager_email_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    //Password manager credit card view
    function credit_card() {
        return $this->template->view("Password_manager\Views\password_manager\credit_card\index");
    }

    //credit card modal form
    function credit_card_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $model_info = $this->Password_manager_credit_card_model->get_one($id);

        $view_data['categories_dropdown'] = $this->Password_manager_categories_model->get_dropdown_list(array("title"), "id");
        $view_data['members_and_teams_dropdown'] = json_encode(get_team_members_and_teams_select2_data_list());

        $pin = $model_info->pin;
        $model_info->pin = decode_id($pin, "credit_card_pin");

        $view_data['model_info'] = $model_info;
        $view_data['clients_dropdown'] = $this->_get_clients_dropdown();
        $view_data['client_id'] = $this->request->getPost('client_id');

        return $this->template->view('Password_manager\Views\password_manager\credit_card\modal_form', $view_data);
    }

    //save credit card
    function save_credit_card() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "name" => "required",
            "credit_card_type" => "required",
            "card_number" => "required|numeric",
            "valid_from" => "required",
            "valid_to" => "required",
        ));

        $id = $this->request->getPost('id');

        //prepare share with data
        $share_with_team_members = $this->request->getPost('share_with_team_members');
        if ($share_with_team_members == "specific") {
            $share_with_team_members = $this->request->getPost('share_with_specific_team_members');
        }

        $share_with_client = $this->request->getPost('share_with_client');
        if ($share_with_client == "specific") {
            $share_with_client = $this->request->getPost('share_with_specific_client_contact');
        }

        $pin = encode_id($this->request->getPost('pin'), "credit_card_pin");
        $client_id = $this->request->getPost('client_id');
        $created_by_client = $this->request->getPost('created_by_client');

        $data = array(
            "name" => $this->request->getPost('name'),
            "category_id" => $this->request->getPost('category_id'),
            "credit_card_type" => $this->request->getPost('credit_card_type'),
            "card_number" => $this->request->getPost('card_number'),
            "card_cvc" => $this->request->getPost('card_cvc'),
            "pin" => $pin,
            "valid_from" => $this->request->getPost('valid_from'),
            "valid_to" => $this->request->getPost('valid_to'),
            "username" => $this->request->getPost('username'),
            "description" => $this->request->getPost('description'),
            "client_id" => $client_id ? $client_id : 0,
            "share_with" => $share_with_team_members,
            "share_with_client" => $share_with_client
        );
        
        if (!$id) {
            $data["created_by"] = $this->login_user->id;
            $data["created_at"] = get_current_utc_time();
            $data["created_by_client"] = $created_by_client ? $created_by_client : 0;
        }

        $save_id = $this->Password_manager_credit_card_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_credit_card_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //prepare credit card list data
    function list_data_of_credit_card() {
        $options = array(
            "is_admin" => $this->login_user->is_admin,
            "user_id" => $this->login_user->id,
            "team_ids" => $this->login_user->team_ids
        );

        $list_data = $this->Password_manager_credit_card_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_credit_card_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //get a row of credit card row
    private function _credit_card_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Password_manager_credit_card_model->get_details($options)->getRow();
        return $this->_make_credit_card_row($data);
    }

    //make a row of credit card row
    private function _make_credit_card_row($data) {
        $name = modal_anchor(get_uri("password_manager/view_credit_card"), $data->name, array("title" => app_lang('password_manager_credit_card') . " #$data->id", "data-post-id" => encode_id($data->id, "credit_card_password_id")));

        //only creator can edit/delete passwords
        $actions = modal_anchor(get_uri("password_manager/view_credit_card"), "<i data-feather='tablet' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_password_details'), "data-modal-title" => app_lang('password_manager_credit_card') . " #$data->id", "data-post-id" => encode_id($data->id, "credit_card_password_id")));
        if ($data->created_by == $this->login_user->id || ($data->created_by_client == 1 && $this->login_user->is_admin)) {
            $actions = modal_anchor(get_uri("password_manager/credit_card_modal_form/"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_edit_credit_card'), "data-post-id" => $data->id))
                    . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('password_manager_delete_credit_card'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("password_manager/delete_credit_card"), "data-action" => "delete-confirmation"));
        }

        return array(
            $data->id,
            $name,
            $data->category_title,
            $data->credit_card_type,
            $data->valid_from,
            $data->valid_to,
            $actions
        );
    }

    //prepare credit card view
    function view_credit_card() {
        $this->validate_submitted_data(array(
            "id" => "required"
        ));

        $encrypted_id = $this->request->getPost('id');

        $id = decode_id($encrypted_id, "credit_card_password_id");
        $model_info = $this->Password_manager_credit_card_model->get_details(array("id" => $id))->getRow();

        if ($id && $model_info->id) {
            $pin = $model_info->pin;
            $model_info->pin = decode_id($pin, "credit_card_pin");

            $view_data['model_info'] = $model_info;

            return $this->template->view('Password_manager\Views\password_manager\credit_card\view', $view_data);
        } else {
            show_404();
        }
    }

    //delete a credit card
    function delete_credit_card() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $model_info = $this->Password_manager_credit_card_model->get_one($id);

        if ($model_info->created_by != $this->login_user->id) {
            app_redirect("forbidden");
        }

        if ($this->Password_manager_credit_card_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    //Password manager bank account view
    function bank_account() {
        return $this->template->view("Password_manager\Views\password_manager\bank_account\index");
    }

    //bank account modal form
    function bank_account_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $model_info = $this->Password_manager_bank_account_model->get_one($id);

        $view_data['categories_dropdown'] = $this->Password_manager_categories_model->get_dropdown_list(array("title"), "id");
        $view_data['members_and_teams_dropdown'] = json_encode(get_team_members_and_teams_select2_data_list());

        $pin = $model_info->pin;
        $model_info->pin = decode_id($pin, "bank_account_pin");

        $view_data['model_info'] = $model_info;
        $view_data['clients_dropdown'] = $this->_get_clients_dropdown();
        $view_data['client_id'] = $this->request->getPost('client_id');

        return $this->template->view('Password_manager\Views\password_manager\bank_account\modal_form', $view_data);
    }

    //save bank account
    function save_bank_account() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "name" => "required",
            "username" => "required",
            "pin" => "required|numeric",
        ));

        $id = $this->request->getPost('id');

        //prepare share with data
        $share_with_team_members = $this->request->getPost('share_with_team_members');
        if ($share_with_team_members == "specific") {
            $share_with_team_members = $this->request->getPost('share_with_specific_team_members');
        }

        $share_with_client = $this->request->getPost('share_with_client');
        if ($share_with_client == "specific") {
            $share_with_client = $this->request->getPost('share_with_specific_client_contact');
        }

        $pin = encode_id($this->request->getPost('pin'), "bank_account_pin");
        $client_id = $this->request->getPost('client_id');
        $created_by_client = $this->request->getPost('created_by_client');

        $data = array(
            "name" => $this->request->getPost('name'),
            "category_id" => $this->request->getPost('category_id'),
            "url" => $this->request->getPost('url'),
            "username" => $this->request->getPost('username'),
            "pin" => $pin,
            "bank_name" => $this->request->getPost('bank_name'),
            "bank_code" => $this->request->getPost('bank_code'),
            "account_holder" => $this->request->getPost('account_holder'),
            "account_number" => $this->request->getPost('account_number'),
            "iban" => $this->request->getPost('iban'),
            "description" => $this->request->getPost('description'),
            "client_id" => $client_id ? $client_id : 0,
            "share_with" => $share_with_team_members,
            "share_with_client" => $share_with_client
        );
        
        if (!$id) {
            $data["created_by"] = $this->login_user->id;
            $data["created_at"] = get_current_utc_time();
            $data["created_by_client"] = $created_by_client ? $created_by_client : 0;
        }

        $save_id = $this->Password_manager_bank_account_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_bank_account_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //prepare list data for bank account
    function list_data_of_bank_account() {
        $options = array(
            "is_admin" => $this->login_user->is_admin,
            "user_id" => $this->login_user->id,
            "team_ids" => $this->login_user->team_ids
        );

        $list_data = $this->Password_manager_bank_account_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_bank_account_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //get a row of bank account row
    private function _bank_account_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Password_manager_bank_account_model->get_details($options)->getRow();
        return $this->_make_bank_account_row($data);
    }

    //make a row of bank account row
    private function _make_bank_account_row($data) {
        $name = modal_anchor(get_uri("password_manager/view_bank_account"), $data->name, array("title" => app_lang('password_manager_bank_account') . " #$data->id", "data-post-id" => encode_id($data->id, "bank_account_password_id")));

        //only creator can edit/delete passwords
        $actions = modal_anchor(get_uri("password_manager/view_bank_account"), "<i data-feather='tablet' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_password_details'), "data-modal-title" => app_lang('password_manager_bank_account') . " #$data->id", "data-post-id" => encode_id($data->id, "bank_account_password_id")));
        if ($data->created_by == $this->login_user->id || ($data->created_by_client == 1 && $this->login_user->is_admin)) {
            $actions = modal_anchor(get_uri("password_manager/bank_account_modal_form/"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_edit_bank_account'), "data-post-id" => $data->id))
                    . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('password_manager_delete_bank_account'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("password_manager/delete_bank_account"), "data-action" => "delete-confirmation"));
        }

        return array(
            $data->id,
            $name,
            $data->category_title,
            $data->url,
            $data->username,
            $actions
        );
    }

    //prepare bank account view
    function view_bank_account() {
        $this->validate_submitted_data(array(
            "id" => "required"
        ));

        $encrypted_id = $this->request->getPost('id');

        $id = decode_id($encrypted_id, "bank_account_password_id");
        $model_info = $this->Password_manager_bank_account_model->get_details(array("id" => $id))->getRow();

        if ($id && $model_info->id) {
            $pin = $model_info->pin;
            $model_info->pin = decode_id($pin, "bank_account_pin");

            $view_data['model_info'] = $model_info;

            return $this->template->view('Password_manager\Views\password_manager\bank_account\view', $view_data);
        } else {
            show_404();
        }
    }

    //delete a bank account
    function delete_bank_account() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $model_info = $this->Password_manager_bank_account_model->get_one($id);

        if ($model_info->created_by != $this->login_user->id) {
            app_redirect("forbidden");
        }

        if ($this->Password_manager_bank_account_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    //Password manager software license view
    function software_license() {
        return $this->template->view("Password_manager\Views\password_manager\software_license\index");
    }

    //software license modal form
    function software_license_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $model_info = $this->Password_manager_software_license_model->get_one($id);

        $view_data['categories_dropdown'] = $this->Password_manager_categories_model->get_dropdown_list(array("title"), "id");
        $view_data['members_and_teams_dropdown'] = json_encode(get_team_members_and_teams_select2_data_list());

        $license_key = $model_info->license_key;
        $model_info->license_key = decode_id($license_key, "license_key");

        $view_data['model_info'] = $model_info;
        $view_data['clients_dropdown'] = $this->_get_clients_dropdown();
        $view_data['client_id'] = $this->request->getPost('client_id');

        return $this->template->view('Password_manager\Views\password_manager\software_license\modal_form', $view_data);
    }

    //save software license
    function save_software_license() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "name" => "required",
            "license_key" => "required",
        ));

        $id = $this->request->getPost('id');

        //prepare share with data
        $share_with_team_members = $this->request->getPost('share_with_team_members');
        if ($share_with_team_members == "specific") {
            $share_with_team_members = $this->request->getPost('share_with_specific_team_members');
        }

        $share_with_client = $this->request->getPost('share_with_client');
        if ($share_with_client == "specific") {
            $share_with_client = $this->request->getPost('share_with_specific_client_contact');
        }

        $license_key = encode_id($this->request->getPost('license_key'), "license_key");
        $client_id = $this->request->getPost('client_id');
        $created_by_client = $this->request->getPost('created_by_client');

        $data = array(
            "name" => $this->request->getPost('name'),
            "category_id" => $this->request->getPost('category_id'),
            "url" => $this->request->getPost('url'),
            "version" => $this->request->getPost('version'),
            "license_key" => $license_key,
            "description" => $this->request->getPost('description'),
            "client_id" => $client_id ? $client_id : 0,
            "share_with" => $share_with_team_members,
            "share_with_client" => $share_with_client
        );
        
        if (!$id) {
            $data["created_by"] = $this->login_user->id;
            $data["created_at"] = get_current_utc_time();
            $data["created_by_client"] = $created_by_client ? $created_by_client : 0;
        }

        $save_id = $this->Password_manager_software_license_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_software_license_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //prepare software license list data
    function list_data_of_software_license() {
        $options = array(
            "is_admin" => $this->login_user->is_admin,
            "user_id" => $this->login_user->id,
            "team_ids" => $this->login_user->team_ids
        );

        $list_data = $this->Password_manager_software_license_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_software_license_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //get a row of software license row
    private function _software_license_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Password_manager_software_license_model->get_details($options)->getRow();
        return $this->_make_software_license_row($data);
    }

    //make a row of software license row
    private function _make_software_license_row($data) {
        $name = modal_anchor(get_uri("password_manager/view_software_license"), $data->name, array("title" => app_lang('password_manager_software_license') . " #$data->id", "data-post-id" => encode_id($data->id, "software_license_password_id")));

        //only creator can edit/delete passwords
        $actions = modal_anchor(get_uri("password_manager/view_software_license"), "<i data-feather='tablet' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_password_details'), "data-modal-title" => app_lang('password_manager_software_license') . " #$data->id", "data-post-id" => encode_id($data->id, "software_license_password_id")));
        if ($data->created_by == $this->login_user->id || ($data->created_by_client == 1 && $this->login_user->is_admin)) {
            $actions = modal_anchor(get_uri("password_manager/software_license_modal_form/"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('password_manager_edit_software_license'), "data-post-id" => $data->id))
                    . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('password_manager_delete_software_license'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("password_manager/delete_software_license"), "data-action" => "delete-confirmation"));
        }

        return array(
            $data->id,
            $name,
            $data->category_title,
            $data->version,
            $actions
        );
    }

    //prepare software license view
    function view_software_license() {
        $this->validate_submitted_data(array(
            "id" => "required"
        ));

        $encrypted_id = $this->request->getPost('id');

        $id = decode_id($encrypted_id, "software_license_password_id");
        $model_info = $this->Password_manager_software_license_model->get_details(array("id" => $id))->getRow();

        if ($id && $model_info->id) {
            $license_key = $model_info->license_key;
            $model_info->license_key = decode_id($license_key, "license_key");

            $view_data["model_info"] = $model_info;

            return $this->template->view('Password_manager\Views\password_manager\software_license\view', $view_data);
        } else {
            show_404();
        }
    }

    //delete a software license
    function delete_software_license() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $model_info = $this->Password_manager_software_license_model->get_one($id);

        if ($model_info->created_by != $this->login_user->id) {
            app_redirect("forbidden");
        }

        if ($this->Password_manager_software_license_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    //get all contacts of a selected client
    function get_all_contacts_of_client($client_id) {

        $client_access_info = $this->get_access_info("client");
        validate_numeric_value($client_id);
        if ($client_id && ($this->login_user->is_admin || $client_access_info->access_type == "all")) {
            $client_contacts = $this->Users_model->get_all_where(array("status" => "active", "client_id" => $client_id, "deleted" => 0))->getResult();
            $client_contacts_array = array();

            if ($client_contacts) {
                foreach ($client_contacts as $contacts) {
                    $client_contacts_array[] = array("type" => "contact", "id" => "contact:" . $contacts->id, "text" => $contacts->first_name . " " . $contacts->last_name);
                }
            }
            echo json_encode($client_contacts_array);
        }
    }

    //get clients dropdown
    private function _get_clients_dropdown() {
        //prepare clients dropdown, check if user has permission to access the client
        $client_access_info = $this->get_access_info("client");
        $clients_dropdown = array(array("id" => "", "text" => "- " . app_lang("client") . " -"));

        if ($this->login_user->is_admin || $client_access_info->access_type == "all") {
            $clients = $this->Clients_model->get_dropdown_list(array("company_name"), "id", array("is_lead" => 0));
            foreach ($clients as $key => $value) {
                $clients_dropdown[] = array("id" => $key, "text" => $value);
            }
        }
        return $clients_dropdown;
    }

    //Password manager general view for client
    function general_for_client($client_id) {
        if ($client_id) {
            $view_data['client_id'] = clean_data($client_id);
            return $this->template->view("Password_manager\Views\clients\password_manager\general\index", $view_data);
        }
    }

    //prepare general list data for client
    function general_list_data_of_client($client_id = 0) {
        $list_data = $this->Password_manager_general_model->get_details(array("client_id" => $client_id, "contact_id" => $this->login_user->id))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_general_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //Password manager email view for client
    function email_for_client($client_id) {
        if ($client_id) {
            $view_data['client_id'] = clean_data($client_id);
            return $this->template->view("Password_manager\Views\clients\password_manager\\email\index", $view_data);
        }
    }

    //prepare email list data for client
    function email_list_data_of_client($client_id = 0) {
        $list_data = $this->Password_manager_email_model->get_details(array("client_id" => $client_id, "contact_id" => $this->login_user->id))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_email_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //Password manager credit card view for client
    function credit_card_for_client($client_id) {
        if ($client_id) {
            $view_data['client_id'] = clean_data($client_id);
            return $this->template->view("Password_manager\Views\clients\password_manager\credit_card\index", $view_data);
        }
    }

    //prepare credit card list data for client
    function credit_card_list_data_of_client($client_id = 0) {
        $list_data = $this->Password_manager_credit_card_model->get_details(array("client_id" => $client_id, "contact_id" => $this->login_user->id))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_credit_card_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //Password manager credit card view for client
    function bank_account_for_client($client_id) {
        if ($client_id) {
            $view_data['client_id'] = clean_data($client_id);
            return $this->template->view("Password_manager\Views\clients\password_manager\bank_account\index", $view_data);
        }
    }

    //prepare credit card list data for client
    function bank_account_list_data_of_client($client_id = 0) {
        $list_data = $this->Password_manager_bank_account_model->get_details(array("client_id" => $client_id, "contact_id" => $this->login_user->id))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_bank_account_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //Password manager software license view for client
    function software_license_for_client($client_id) {
        if ($client_id) {
            $view_data['client_id'] = clean_data($client_id);
            return $this->template->view("Password_manager\Views\clients\password_manager\software_license\index", $view_data);
        }
    }

    //prepare software license list data for client
    function software_license_list_data_of_client($client_id = 0) {
        $list_data = $this->Password_manager_software_license_model->get_details(array("client_id" => $client_id, "contact_id" => $this->login_user->id))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_software_license_row($data);
        }
        echo json_encode(array("data" => $result));
    }

}

/* End of file Password_manager.php */
/* Location: ./plugins/Password_manager/controllers/Password_manager.php */