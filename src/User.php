<?php

namespace Bot;

use GuzzleHttp\Psr7\Response;

class User
{
    public $isAuthorised = false;
    public $login;
    public $auth_time;

    protected $sessionId;
    protected $csrf_token;
    protected $user_id;

    public function __construct($client, $login, $password)
    {
        echo "Logining...\n";
        $this->authorize($login, $password);
        $this->login = $login;
        echo "Login success\n";
        $this->auth_time = time();
        echo "Login time: " . $this->auth_time . PHP_EOL;
        echo "Serialising user...\n";
        $this->serialise();
        echo "Serialising success\n";
    }

    public function serialise()
    {
        $serialised = serialize($this);
        $file = fopen("user.txt", "w") or die("Can`t create file");
        fwrite($file, $serialised);
        fclose($file);
    }

    private function authorize($login, $password)
    {
        $response = Bot::request('get', Bot::INSTAGRAM_DOMEN);
        $this->setCsrfToken($this->csrfToken($response));

        $headers = [
            "x-csrftoken" => $this->getCsrfToken(),
            "referer" => Bot::INSTAGRAM_DOMEN,
            "cookie" => "csrftoken=" . $this->getCsrfToken() . ";",
            "origin" => Bot::INSTAGRAM_DOMEN,
        ];

        $form_params = [
            'username' => $login,
            'password' => $password
        ];

        try {
            $response = Bot::request("post", Bot::INSTAGRAM_DOMEN . "/accounts/login/ajax/", [
                'headers' => $headers,
                'form_params' => $form_params
            ]);

            $response_body = json_decode($response->getBody());

            if (!$response_body->authenticated) {
                throw new \Exception("Something was wrong with logining.");
            }
            $this->isAuthorised = true;
            $this->setCsrfToken($this->csrfToken($response));
            //echo "csrf-token: " . $this->getCsrfToken() . PHP_EOL;
            $this->setSessionId($this->sessionId($response));
            //echo "sessionID: " . $this->getSessionId() . PHP_EOL;
            $this->setUserId($this->userId());
            //echo "userId: " . $this->getUserId() . PHP_EOL;

        } catch (\Exception $e) {
            echo $e->getMessage();
            die();
        }
    }

    public function __sleep()
    {
        return ["csrf_token", "user_id", "sessionId"];
    }

    public function __wakeup()
    {
        $this->auth_time = time();
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