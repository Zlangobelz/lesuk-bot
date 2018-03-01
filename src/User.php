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

    private function authorize($login, $password): Response
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
            echo "csrf-token: " . $this->getCsrfToken() . PHP_EOL;
            $this->setSessionId($this->sessionId($response));
            echo "sessionID: " . $this->getSessionId() . PHP_EOL;
            $this->setUserId($this->userId());
            echo "userId: " . $this->getUserId() . PHP_EOL;


        } catch (\Exception $e) {
            echo $e->getMessage();
            die();
        }

        return $response;
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

    protected function userId(): string
    {
        if (!$this->isAuthorised) return null;

        $headers = [
            "Cookie" => "sessionid=" . $this->getSessionId() . "; csrftoken=" . $this->getCsrfToken() . ";",
            "accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "accept-encoding" => "gzip, deflate, br",
            "user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36",
            "accept-language" => "en,uk;q=0.9,ru-RU;q=0.8,ru;q=0.7,en-US;q=0.6",
            "upgrade-insecure-requests" => "1"
        ];

        $response = Bot::request('get', Bot::INSTAGRAM_DOMEN. "/" . $this->login . "/", [
            'headers' => $headers,
        ]);

        $content = (string) $response->getBody();
        $pattern = '/fbq\(\'init\', \'(.*?)\'\);/';
        preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        if (!$matches) {
            return null;
        }

        return $matches[0][1];
    }

}