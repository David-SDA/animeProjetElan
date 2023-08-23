<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AnimeCallApiService{
    private $client;

    public function __construct(HttpClientInterface $client){
        $this->client = $client;
    }

    /**
     * Fonction qui permet d'obtenir toutes les informations d'un animé
     */
    public function getAnimeDetails(int $id): array{
        return ['test1', 'test2', $id];
    }
}