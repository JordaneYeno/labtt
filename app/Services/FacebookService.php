<?php

namespace App\Services;

use Facebook\Facebook;

class FacebookService
{
    protected $facebook;

    public function __construct()
    {
        $this->facebook = new Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v12.0',
            // 'default_graph_version' => 'v5.7',
        ]);
    }

    public function sendMessage($userToken, $recipientId, $message)
    {
        try {
            $response = $this->facebook->post(
                "/$recipientId/messages",
                [
                    'message' => ['text' => $message]
                ],
                $userToken
            );
            return $response->getDecodedBody();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            throw new \Exception('Graph returned an error: ' . $e->getMessage());
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            throw new \Exception('Facebook SDK returned an error: ' . $e->getMessage());
        }
    }
}
