<?php

namespace Bot;

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class Bot
{
    //TODO: Request for more posts
    const INSTAGRAM_DOMEN = "https://www.instagram.com";
    const LOGIN = "zvzvzvzvzvzvzvzvzvzv";
    const PASSWORD = "ibunar1998vladbelz2";
    const HASHTAG = "likeforfollow";

    //I have no idea how does instagram return`s this param
    const COUNT_OF_POSTS = 7;

    protected $client;
    protected $sessionId;
    protected $csrf_token;
    protected $user_id;
    public $counter;

    public function run()
    {
        //TODO: need to add provisional headers to be non-detected
        //TODO: add logger
        //TODO: set user id
        $this->user_id = 4277241110;
        $query_hash = "298b92c8d7cad703f7565aa892ede943";

        echo "logining..." . PHP_EOL;

        $this->client = new Client();
        $res = $this->authorize();

        echo "Login success." . PHP_EOL;
        $this->sessionId = $this->getSessionId($res);

        echo "Requesting on hashtag..." . PHP_EOL;

        $headers = [
            "Cookie" => "sessionid=" . $this->sessionId . ";"
        ];

        $res = $this->client->request("post", self::INSTAGRAM_DOMEN . "/explore/tags/" . self::HASHTAG . "/?__a=1", [
            'headers' => $headers
        ]);

        $result = json_decode($res->getBody());
        $posts = $result->graphql->hashtag->edge_hashtag_to_media->edges;
        foreach ($posts as $post) {
            $this->likePost($post);
        }

        $end_cursor = $result->graphql->hashtag->edge_hashtag_to_media->page_info->end_cursor;

        while (true) {
            $variables = [
                "tag_name" => self::HASHTAG,
                "first" => self::COUNT_OF_POSTS,
                "after" => $end_cursor
            ];

            $headers = [
                "referer" => self::INSTAGRAM_DOMEN . "/explore/tags/" . self::HASHTAG . "/",
                "cookie" => "csrftoken=" . $this->csrf_token . "; ds_user_id=" . $this->user_id . "; sessionid=" . $this->sessionId . ";",
                "x-requested-with" => "XMLHttpRequest"
            ];

            $url = self::INSTAGRAM_DOMEN . "/graphql/query/?query_hash=" . $query_hash . "&variables=" . json_encode($variables);

            echo "Getting data from " . $url . PHP_EOL;

            $res = $this->client->request("get", $url, [
                'headers' => $headers
            ]);
            $result = json_decode($res->getBody());

            //$posts = $result->graphql->hashtag->edge_hashtag_to_top_posts->edges;

            $posts = $result->data->hashtag->edge_hashtag_to_media->edges;

            foreach ($posts as $post) {
                $this->likePost($post);
            }
            echo $this->counter . PHP_EOL;

            $end_cursor = $result->data->hashtag->edge_hashtag_to_media->page_info->end_cursor;
        }
    }

    protected function getCsrfToken(Response $response): string
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

    private function authorize(): Response
    {
        $response = $this->client->request('get', self::INSTAGRAM_DOMEN);
        $this->csrf_token = $this->getCsrfToken($response);

        $headers = [
            "x-csrftoken" => $this->csrf_token,
            "referer" => self::INSTAGRAM_DOMEN,
            "cookie" => "csrftoken=" . $this->csrf_token . ";",
            "origin" => self::INSTAGRAM_DOMEN,
        ];

        $form_params = [
            'username' => self::LOGIN,
            'password' => self::PASSWORD
        ];

        try {
            $response = $this->client->request("post", self::INSTAGRAM_DOMEN . "/accounts/login/ajax/", [
                'headers' => $headers,
                'form_params' => $form_params
            ]);

            $response_body = json_decode($response->getBody());

            $this->csrf_token = $this->getCsrfToken($response);

            if (!$response_body->authenticated) {
                throw new \Exception("Something was wrong with logining.");
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
            die();
        }

        return $response;
    }

    private function getSessionId(Response $res): string
    {
        $sessionid = $res->getHeader("set-cookie")[5];
        $pattern = '/sessionid=(.*?);/';
        preg_match($pattern, $sessionid, $matches, PREG_OFFSET_CAPTURE);
        return $matches[1][0];
    }

    private function likePost($post)
    {
        $random = rand(2, 6);

        echo "Sleeping " . $random . " seconds...\n";

        sleep($random);

        $post_id = $post->node->id;
        $post_shortcode = $post->node->shortcode;
        $headers = [
            "x-csrftoken" => $this->csrf_token,
            "referer" => "https://www.instagram.com/p/" . $post_shortcode . "/",
            "cookie" => "csrftoken=" . $this->csrf_token . "; ds_user_id=" . $this->user_id . "; sessionid=" . $this->sessionId . ";",
            "content-type" => "application/x-www-form-urlencoded",

        ];

        $res = $this->client->request("post", self::INSTAGRAM_DOMEN . "/web/likes/" . $post_id . "/like/", [
            'headers' => $headers
        ]);


        if ($res->getStatusCode() != 200) {
            echo "Something wrong with liking post: https://www.instagram.com/p/ " . $post_shortcode . "/" .
                "   ID : " . $post_id . "   CSRF-Token : " . $this->csrf_token . PHP_EOL;
            $this->counter++;
        } else {
            echo "LIKED: https://www.instagram.com/p/" . $post_shortcode . "/" .
                "   ID : " . $post_id . "   CSRF-Token : " . $this->csrf_token . PHP_EOL;
        }
    }
}

$bot = new Bot();
$bot->run();