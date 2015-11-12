<?php
class ControllerUpgrade extends Controller {
	private $error = array();

	public function index() {
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->backup();

			$this->copyOc21();

			// $this->ocfix();

			$this->saveConfig();
			
			$this->load->model('upgrade');

			$this->model_upgrade->mysql();

			$this->response->redirect($this->url->link('upgrade/success'));
		}

		$data = array();

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['action'] = $this->url->link('upgrade');

		$data['header'] = $this->load->controller('header');
		$data['footer'] = $this->load->controller('footer');

		$this->response->setOutput($this->load->view('upgrade.tpl', $data));
	}

	public function success() {
		$data = array();

		$data['header'] = $this->load->controller('header');
		$data['footer'] = $this->load->controller('footer');

		$this->response->setOutput($this->load->view('success.tpl', $data));
	}

	private function validate() {
		if (DB_DRIVER == 'mysql') {
			if (!$connection = @mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD)) {
				$this->error['warning'] = 'Error: Could not connect to the database please make sure the database server, username and password is correct in the config.php file!';
			} else {
				if (!mysql_select_db(DB_DATABASE, $connection)) {
					$this->error['warning'] = 'Error: Database "' . DB_DATABASE . '" does not exist!';
				}

				mysql_close($connection);
			}
		}

		return !$this->error;
	}

	public function backup() {
		$dir_admin = DIR_OPENCART . "admin/";
		$dir_admin_new = DIR_OPENCART . "backup/admin/";

		$dir_system = DIR_OPENCART . "system/";
		$dir_system_new = DIR_OPENCART . "backup/system/";

		$dir_catalog = DIR_OPENCART . "catalog/";
		$dir_catalog_new = DIR_OPENCART . "backup/catalog/";

		$this->xCopy($dir_admin, $dir_admin_new);
		$this->xCopy($dir_system, $dir_system_new);
		$this->xCopy($dir_catalog, $dir_catalog_new);
	}

	public function copyOc21() {
		$dir_admin = DIR_APPLICATION . "upload/admin/";
		$dir_admin_new = DIR_OPENCART . "admin/";

		$dir_system = DIR_APPLICATION . "upload/system/";
		$dir_system_new = DIR_OPENCART . "system/";

		$dir_catalog = DIR_APPLICATION . "upload/catalog/";
		$dir_catalog_new = DIR_OPENCART . "catalog/";

		$dir_index = DIR_APPLICATION . "upload/index.php";
		$dir_index_new = DIR_OPENCART . "index.php";

		$this->xCopy($dir_admin, $dir_admin_new);
		$this->xCopy($dir_system, $dir_system_new);
		$this->xCopy($dir_catalog, $dir_catalog_new);
		$this->xCopy($dir_index, $dir_index_new);
	}

	public function ocfix() {
		$dir_ocfix = DIR_APPLICATION . "ocfix/";
		$dir_ocfix_new = DIR_OPENCART;

		$this->xCopy($dir_ocfix, $dir_ocfix_new);
	}

	public function xCopy($source, $dest, $permissions = 0755) {
		// Check for symlinks
		if (is_link($source)) {
			return symlink(readlink($source), $dest);
		}

		// Simple copy for a file
		if (is_file($source)) {
			return copy($source, $dest);
		}

		// Make destination directory
		if (!is_dir($dest)) {
			mkdir($dest, $permissions, true);
		}

		// Loop through the folder
		$dir = dir($source);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue;
			}

			// Deep copy directories
			$this->xCopy("$source/$entry", "$dest/$entry", $permissions);
		}

		// Clean up
		$dir->close();
		return true;
	}

	public function saveConfig() {
		defined('DB_PORT') or define('DB_PORT', '3306');
		
		if (file_exists('../config.php')) {
			$output = '<?php' . "\n";
			$output .= '// HTTP' . "\n";
			$output .= 'define(\'HTTP_SERVER\', \'' . HTTP_OPENCART . '\');' . "\n\n";

			$output .= '// HTTPS' . "\n";
			$output .= 'define(\'HTTPS_SERVER\', \'' . HTTP_OPENCART . '\');' . "\n\n";

			$output .= '// DIR' . "\n";
			$output .= 'define(\'DIR_APPLICATION\', \'' . DIR_OPENCART . 'catalog/\');' . "\n";
			$output .= 'define(\'DIR_SYSTEM\', \'' . DIR_OPENCART . 'system/\');' . "\n";
			$output .= 'define(\'DIR_LANGUAGE\', \'' . DIR_OPENCART . 'catalog/language/\');' . "\n";
			$output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_OPENCART . 'catalog/view/theme/\');' . "\n";
			$output .= 'define(\'DIR_CONFIG\', \'' . DIR_OPENCART . 'system/config/\');' . "\n";
			$output .= 'define(\'DIR_IMAGE\', \'' . DIR_OPENCART . 'image/\');' . "\n";
			$output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/storage/cache/\');' . "\n";
			$output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/storage/download/\');' . "\n";
			$output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/storage/logs/\');' . "\n";
			$output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/storage/modification/\');' . "\n";
			$output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/storage/upload/\');' . "\n\n";

			$output .= '// DB' . "\n";
			$output .= 'define(\'DB_DRIVER\', \'' . DB_DRIVER . '\');' . "\n";
			$output .= 'define(\'DB_HOSTNAME\', \'' . DB_HOSTNAME . '\');' . "\n";
			$output .= 'define(\'DB_USERNAME\', \'' . DB_USERNAME . '\');' . "\n";
			$output .= 'define(\'DB_PASSWORD\', \'' . DB_PASSWORD . '\');' . "\n";
			$output .= 'define(\'DB_DATABASE\', \'' . DB_DATABASE . '\');' . "\n";
			$output .= 'define(\'DB_PORT\', \'' . DB_PORT . '\');' . "\n";
			$output .= 'define(\'DB_PREFIX\', \'' . DB_PREFIX . '\');' . "\n";

			$file = fopen(DIR_OPENCART . 'config.php', 'w');

			fwrite($file, $output);

			fclose($file);

			$output = '<?php' . "\n";
			$output .= '// HTTP' . "\n";
			$output .= 'define(\'HTTP_SERVER\', \'' . HTTP_OPENCART . 'admin/\');' . "\n";
			$output .= 'define(\'HTTP_CATALOG\', \'' . HTTP_OPENCART . '\');' . "\n\n";

			$output .= '// HTTPS' . "\n";
			$output .= 'define(\'HTTPS_SERVER\', \'' . HTTP_OPENCART . 'admin/\');' . "\n";
			$output .= 'define(\'HTTPS_CATALOG\', \'' . HTTP_OPENCART . '\');' . "\n\n";

			$output .= '// DIR' . "\n";
			$output .= 'define(\'DIR_APPLICATION\', \'' . DIR_OPENCART . 'admin/\');' . "\n";
			$output .= 'define(\'DIR_SYSTEM\', \'' . DIR_OPENCART . 'system/\');' . "\n";
			$output .= 'define(\'DIR_LANGUAGE\', \'' . DIR_OPENCART . 'admin/language/\');' . "\n";
			$output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_OPENCART . 'admin/view/template/\');' . "\n";
			$output .= 'define(\'DIR_CONFIG\', \'' . DIR_OPENCART . 'system/config/\');' . "\n";
			$output .= 'define(\'DIR_IMAGE\', \'' . DIR_OPENCART . 'image/\');' . "\n";
			$output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/storage/cache/\');' . "\n";
			$output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/storage/download/\');' . "\n";
			$output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/storage/logs/\');' . "\n";
			$output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/storage/modification/\');' . "\n";
			$output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/storage/upload/\');' . "\n";
			$output .= 'define(\'DIR_CATALOG\', \'' . DIR_OPENCART . 'catalog/\');' . "\n\n";

			$output .= '// DB' . "\n";
			$output .= 'define(\'DB_DRIVER\', \'' . DB_DRIVER . '\');' . "\n";
			$output .= 'define(\'DB_HOSTNAME\', \'' . DB_HOSTNAME . '\');' . "\n";
			$output .= 'define(\'DB_USERNAME\', \'' . DB_USERNAME . '\');' . "\n";
			$output .= 'define(\'DB_PASSWORD\', \'' . DB_PASSWORD . '\');' . "\n";
			$output .= 'define(\'DB_DATABASE\', \'' . DB_DATABASE . '\');' . "\n";
			$output .= 'define(\'DB_PORT\', \'' . DB_PORT . '\');' . "\n";
			$output .= 'define(\'DB_PREFIX\', \'' . DB_PREFIX . '\');' . "\n";

			$file = fopen(DIR_OPENCART . 'admin/config.php', 'w');

			fwrite($file, $output);

			fclose($file);
		} else {
			die("File config.php error.");
		}
	}
}