<?php

namespace App\Controllers;

class Reports extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_team_members();
    }

    public function index() {
        $redirect_to = "";
        $reports = get_reports_topbar(true);

        // Prefer timesheets as the default landing report when available
        foreach ($reports as $report) {
            $url = get_array_value($report, "url");
            if ($url === "projects/all_timesheets") {
                $redirect_to = $url;
                break;
            }
        }

        if (!$redirect_to) {
            foreach ($reports as $report) {
                if (get_array_value($report, "single_button") == 1) {
                    $redirect_to = get_array_value($report, "url");
                    break;
                }

                $dropdown = get_array_value($report, "dropdown_item");
                if (is_array($dropdown) && count($dropdown)) {
                    $first = array_values($dropdown)[0];
                    $redirect_to = get_array_value($first, "url");
                    break;
                }
            }
        }

        $view_data["redirect_to"] = $redirect_to;
        return $this->template->rander("reports/index", $view_data);
    }
}

/* End of file Reports.php */
/* Location: ./app/controllers/Reports.php */
