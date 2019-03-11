<?php

namespace App\Irc\Responders;

use App\Repositories\FactRepository;
use Jerodev\PhpIrcClient\IrcChannel;

/**
 *  A responder that can learn and tell facts
 */
class FactResponder extends Responder
{
    public function handlePrivmsg(string $from, IrcChannel $to, string $message, bool $respond = true): ?string
    {
        if ($respond === false) {
            return null;
        }

        // Find a fact
        if ($message[0] === '!') {
            return $this->respondToFact($from, $to, $message);
        }

        // Learn a fact
        if (substr($message, 0, strlen('Pokedex: !')) === 'Pokedex: !') {
            return $this->learnFact($from, $to, $message);
        }

        return null;
    }

    private function learnFact(string $from, IrcChannel $to, string $message): ?string
    {
        $message = substr($message, strlen('Pokedex: !'));
        $command = strstr($message, ' ', true);
        $response = trim(substr($message, strlen($command)));

        FactRepository::learnFact($from, $to->getName(), $command, $response);

        return null;
    }

    private function respondToFact(string $from, IrcChannel $to, string $message): ?string
    {
        $command = substr((strpos($message, ' ') !== false ? strstr($message, ' ', true) : $message), 1);

        if ($response = FactRepository::getResponseString($command, $to->getName(), true)) {
            return $this->parseResponse($response, $from, $to, $message);
        }

        return null;
    }

    private function parseResponse(string $response, string $user, IrcChannel $channel, string $message): string
    {
        // Replace %randomuser% with a random user in the channel.
        $response = str_replace('%randomuser%', $channel->getUsers()[array_rand($channel->getUsers())], $response);

        // Replace %user% with the current users nickname.
        $response = str_replace('%user%', $user, $response);

        // Replace %param% with the payload after the command
        $param = trim(strstr($message, ' '));
        $response = str_replace('%param%', $param, $response);

        // Replace %param:fallback% with the payload or fallback if there is no payload
        if (strpos($response, '%param:') !== false) {
            $response = preg_replace_callback('/%param:([^%]+)%/', function ($matches) use ($param) {
                if (!empty($param)) {
                    return $param;
                } else {
                    return $matches[1];
                }
            }, $response);
        }

        // Replace %dice:x:y% with a random number between x and y.
        if (strpos($response, '%dice:') !== false) {
            $response = preg_replace_callback('/%dice:(\d+):(\d+)%/', function ($matches) {
                return rand($matches[1], $matches[2]);
            }, $response);
        }

        return $response;
    }
}