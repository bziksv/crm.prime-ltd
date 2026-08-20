<?php
namespace Migration\Controllers;

use mysql_xdevapi\Exception;

class Migration extends \App\Controllers\Security_Controller
{
    const MIGRATION_PATH = PLUGINPATH . "Migration/install/migrations";

    public function index()
    {
        include PLUGINPATH . "Migration/install/do_install.php";

        echo $this->template->view('Migration\Views\update\index');
    }

    public function settings()
    {
        $migration_files = scandir(self::MIGRATION_PATH);

        $view_data = [
            "options" => ["" => "Выбрать"]
        ];

        foreach ($migration_files as $migration_file) {
            if (strlen($migration_file) > 2) {
                $view_data["options"][$migration_file] = $migration_file;
            }
        }

        return $this->template->rander("Migration\Views\settings\index", $view_data);
    }

    public function store()
    {
        $migration_file = $this->request->getPost("file");

        if ($migration_file) {
            $migration = file_get_contents(self::MIGRATION_PATH . "/" . $migration_file);

            $migration_explode = explode('--#', $migration);

            $db = db_connect('default');

            foreach ($migration_explode as $migration_query) {
                $migration_query = trim($migration_query);
                if ($migration_query) {
                    $db->query($migration_query);
                }
            }

            echo json_encode(array("success" => true, 'message' => "Изменения базы данных приняты"));
        }
    }
}
