<?php

require_once __DIR__ . '/../vendor/autoload.php';

use app\core\BaseController;
use app\core\Helpers\AuthHelper;

class ViewController extends BaseController
{
    public function home($request, $response)
    {
        $headers = $request->getHeaders();
        $this->renderPage( 'pages/landing', ['headers' => $headers]);
    }


    public function login($request, $response)
    {
        if (AuthHelper::isLoggedIn()) {
            header("Location: /chat");
        }

        $this->renderPage('pages/login');
    }

    public function chat($request, $response)
    {
        // Check if the user is logged in
        if (!AuthHelper::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        $this->renderPage('pages/chat');
    }


    // error pages
    public function error404($request, $response)
    {
        $this->renderPage('pages/404');
    }
}
