<?php

namespace App\Irc\Responders;

use DateTime;
use DateTimeZone;
use Exception;
use Jerodev\PhpIrcClient\IrcChannel;

/**
 *  `!giphy {search}`
 *  Returns a random giphy image for the provided search string.
 */
class GiphyResponder extends Responder
{
    public function handlePrivmsg(string $from, IrcChannel $to, string $message, bool $respond = true): ?string
    {
        $apikey = env('GIPHY_API');

        if ($respond === false || empty($apikey) || strstr($message, ' ', true) !== '!giphy') {
            return null;
        }

        $payload = trim(strstr($message, ' '));
        $response = json_decode(file_get_contents("https://api.giphy.com/v1/gifs/random?api_key=$apikey&tag=$payload&rating=R"));
        return $response->data->images->original->url;
    }
}