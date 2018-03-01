<?php

namespace Bot;

use GuzzleHttp\Psr7\Response;

class User
{
    public $isAuthorised = false;
    public $login;

    protected $sessionId;
    protected $csrf_token;
    protected $user_id;

    public function __construct($client, $login, $password)
    {
        $this->login = $login;
        $this->authorize($login, $password);
    }

    private function authorize($login, $password)
    {
        $response = Bot::request('get', Bot::INSTAGRAM_DOMEN);
        $this->setCsrfToken($this->csrfToken($response));

//        $headers = [
//            "x-csrftoken" => $this->getCsrfToken(),
//            "referer" => Bot::INSTAGRAM_DOMEN,
//            "cookie" => "csrftoken=" . $this->getCsrfToken() . ";",
//            "origin" => Bot::INSTAGRAM_DOMEN,
//        ];
//
//        $form_params = [
//            'username' => $login,
//            'password' => $password
//        ];

        try {
//            $response = Bot::request("post", Bot::INSTAGRAM_DOMEN . "/accounts/login/ajax/", [
//                'headers' => $headers,
//                'form_params' => $form_params
//            ]);

//            $response_body = json_decode($response->getBody());
//
//            if (!$response_body->authenticated) {
//                throw new \Exception("Something was wrong with logining.");
//            }
            $this->isAuthorised = true;
            //$this->setCsrfToken($this->csrfToken($response));
            $this->setCsrfToken("qZ34eFyrwn7r6qtAdNWmDOSB0S1E9cKq");
            echo "csrf-token: " . $this->getCsrfToken() . PHP_EOL;
            //$this->setSessionId($this->sessionId($response));
            $this->setSessionId("IGSC94cb0fe65054e40fdb70770427ee3149ed5b829a43abbffe31a70151b87cb748%3A13hxBsrOJpQoSwATVepINRoLeSAf1YwF%3A%7B%22_auth_user_id%22%3A4277241110%2C%22_auth_user_backend%22%3A%22accounts.backends.CaseInsensitiveModelBackend%22%2C%22_auth_user_hash%22%3A%22%22%2C%22_platform%22%3A4%2C%22_token_ver%22%3A2%2C%22_token%22%3A%224277241110%3A4ynycKCzZjW9Z3uYZpBvbdAzNar436nq%3A0cac70051f4053a905fde5bd646ad2941d6dee75ad251a6014d9e31c9c025c1d%22%2C%22last_refreshed%22%3A1519903429.5508422852%7D");
            echo "sessionID: " . $this->getSessionId() . PHP_EOL;
            $this->setUserId($this->userId());
            echo "userId: " . $this->getUserId() . PHP_EOL;


        } catch (\Exception $e) {
            echo $e->getMessage();
            die();
        }
    }

    public function setCsrfToken($value)
    {
        $this->csrf_token = $value;
    }

    public function getCsrfToken()
    {
        return $this->csrf_token;
    }

    public function setSessionId($value)
    {
        $this->sessionId = $value;
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function setUserId($value)
    {
        $this->user_id = $value;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    protected function csrfToken(Response $response): string
    {
        $headers = $response->getHeader("set-cookie");
        $token = "";

        foreach ($headers as $header) {
            $pattern = '/csrftoken=(.*?);/';
            preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE);
            if ($matches) {
                $token = $matches[1][0];
            }
        }

        return $token;
    }

    protected function sessionId(Response $response): string
    {
        $headers = $response->getHeader("set-cookie");
        $sessionId = "";
        $pattern = '/sessionid=(.*?);/';

        foreach ($headers as $header) {
            preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE);
            if ($matches) {
                $sessionId = $matches[1][0];
            }
        }
        return $sessionId;
    }

    protected function userId(): int
    {
        if (!$this->isAuthorised) return null;

        $content = urldecode($this->getSessionId());

        $pattern = "/_auth_user_id\":(\d+),/";

        preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        if (!$matches) {
            return null;
        }
        return $matches[1][0];
    }

}