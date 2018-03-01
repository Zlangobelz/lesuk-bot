<?php

namespace Bot;

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

class Bot
{
    private static $client;
    private $user;

    //TODO: Request for more posts
    const INSTAGRAM_DOMEN = "https://www.instagram.com";
    const LOGIN = "zvzvzvzvzvzvzvzvzvzv";
    const PASSWORD = "ibunar1998vladbelz2";
    const HASHTAG = "likeforfollow";

    //I have no idea how does instagram return`s this param
    const COUNT_OF_POSTS = 7;

    protected $sessionId;
    protected $csrf_token;
    protected $user_id;
    public $counter;

    public function __construct()
    {
        self::$client = new Client();
        echo "Authentificating..." . PHP_EOL;
        $this->user = new User(self::$client, self::LOGIN, self::PASSWORD);

    }


    public function run()
    {
        //TODO: need to add provisional headers to be non-detected
        //TODO: add logger

        echo "Login success." . PHP_EOL;

        $this->likeUserPage("zvzvzvzvzvzvzvzvzvzvzv");


//        echo "Requesting on hashtag..." . PHP_EOL;
//        $query_hash_tags = "298b92c8d7cad703f7565aa892ede943";
//
//        $headers = [
//            "Cookie" => "sessionid=" . $user->getSessionId() . ";"
//        ];
//
//        $res = $this->client->request("post", self::INSTAGRAM_DOMEN . "/explore/tags/" . self::HASHTAG . "/?__a=1", [
//            'headers' => $headers
//        ]);
//
//        $result = json_decode($res->getBody());
//        $posts = $result->graphql->hashtag->edge_hashtag_to_media->edges;
//        foreach ($posts as $post) {
//            $this->likePost($post);
//        }
//
//        $end_cursor = $result->graphql->hashtag->edge_hashtag_to_media->page_info->end_cursor;
//
//        while (true) {
//            $variables = [
//                "tag_name" => self::HASHTAG,
//                "first" => self::COUNT_OF_POSTS,
//                "after" => $end_cursor
//            ];
//
//            $headers = [
//                "referer" => self::INSTAGRAM_DOMEN . "/explore/tags/" . self::HASHTAG . "/",
//                "cookie" => "csrftoken=" . $this->csrf_token . "; ds_user_id=" . $this->user_id . "; sessionid=" . $this->sessionId . ";",
//                "x-requested-with" => "XMLHttpRequest"
//            ];
//
//            $url = self::INSTAGRAM_DOMEN . "/graphql/query/?query_hash=" . $query_hash . "&variables=" . json_encode($variables);
//
//            echo "Getting data from " . $url . PHP_EOL;
//
//            $res = $this->client->request("get", $url, [
//                'headers' => $headers
//            ]);
//            $result = json_decode($res->getBody());
//
//            //$posts = $result->graphql->hashtag->edge_hashtag_to_top_posts->edges;
//
//            $posts = $result->data->hashtag->edge_hashtag_to_media->edges;
//
//            foreach ($posts as $post) {
//                $this->likePost($post);
//            }
//            echo $this->counter . PHP_EOL;
//
//            $end_cursor = $result->data->hashtag->edge_hashtag_to_media->page_info->end_cursor;
//        }
    }

    private function likePost($post_id, $post_code)
    {
        $random = rand(2, 6);

        echo "Sleeping " . $random . " seconds...\n";

        sleep($random);

        $headers = [
            "x-csrftoken" => $this->user->getCsrfToken(),
            "referer" => "https://www.instagram.com/p/" . $post_code . "/",
            "cookie" => "csrftoken=" . $this->user->getCsrfToken() . "; ds_user_id=" . $this->user->getUserId() . "; sessionid=" . $this->user->getSessionId() . ";",
            "content-type" => "application/x-www-form-urlencoded",

        ];

        $res = self::$client->request("post", self::INSTAGRAM_DOMEN . "/web/likes/" . $post_id . "/like/", [
            'headers' => $headers
        ]);


        if ($res->getStatusCode() != 200) {
            echo "Something wrong with liking post: https://www.instagram.com/p/ " . $post_code . "/" .
                "   ID : " . $post_id . PHP_EOL;
        } else {
            echo "LIKED: https://www.instagram.com/p/" . $post_code . "/" .
                "   ID : " . $post_id . PHP_EOL;
            $this->counter++;
        }
    }

    public static function request(string $method, string $url, array $params = [])
    {
        $response = self::$client->request($method, $url, $params);
        return $response;
    }

    private function likeUserPage($login)
    {
        $headers = [
            "Cookie" => "sessionid=" . $this->user->getSessionId() . ";"
        ];

        $res = self::$client->request("get", self::INSTAGRAM_DOMEN . "/" . $login . "/", [
            'headers' => $headers
        ]);

        $content = (string)$res->getBody();
        $pattern = "/media\"\s?:\s?(\{.*\})\s?\,\s?\"saved_media/";
        preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        if (!$matches) {
            return null;
        }
        $r = json_decode($matches[1][0]);
        foreach ($r->nodes as $node)
        {
            $this->likePost($node->id, $node->code);
        }
    }
}

$bot = new Bot();
$bot->run();