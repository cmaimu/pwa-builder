<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PwaBuilder\Http\Controllers\PwaController;

class PwaBuilderController extends CI_Controller {
    private $controller;

    public function __construct() {
        parent::__construct();
        $this->controller = new PwaController();
    }

    public function index() {
        $this->controller->index();
    }

    public function login() {
        $this->controller->login();
    }

    public function logout() {
        $this->controller->logout();
    }

    public function save() {
        $this->controller->saveSettings();
    }

    public function upload_logo() {
        $this->controller->uploadLogo();
    }

    public function generate() {
        $this->controller->generateAssets();
    }

    public function delete_icons() {
        $this->controller->deleteIcons();
    }

    public function manifest() {
        $this->controller->serveManifest();
    }

    public function sw() {
        $this->controller->serveServiceWorker();
    }

    public function offline() {
        $this->controller->serveOffline();
    }
}

