<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeCallApiService{
    private $client;

    public function __construct(HttpClientInterface $client){
        $this->client = $client;
    }

    /**
     * Fonction permettant d'appeler l'API pour obtenir les meilleurs animés de cette saison
     */
    public function getBestAnimeThisSeason(): array{
        $currentMonth = date('n'); // Mois actuel

        // Définition de la saison en fonction du mois actuel
        if($currentMonth >= 1 && $currentMonth <= 3){
            $currentSeason = 'WINTER';
        }
        elseif($currentMonth >= 4 && $currentMonth <= 6){
            $currentSeason = 'SPRING';
        }
        elseif($currentMonth >= 7 && $currentMonth <= 9){
            $currentSeason = 'SUMMER';
        }
        elseif($currentMonth >= 10 && $currentMonth <= 12){
            $currentSeason = 'FALL';
        }

        $currentYear = date('Y'); // Année actuelle

        // Définition de la query
        $query = '
            query ($currentSeason: MediaSeason, $currentYear: Int) {
                Page(page: 1, perPage: 6) {
                    media(season: $currentSeason, seasonYear: $currentYear, sort: SCORE_DESC){
                        id
                        title {
                            romaji
                        }
                        coverImage {
                            large
                            color
                        }
                        genres
                        episodes
                        format
                        studios {
                            edges {
                                isMain
                                node {
                                    name
                                }
                            }
                        }
                    }
                }
            }
        ';

        // Définition des variables utilisées dans la query
        $variables = [
            'currentSeason' => $currentSeason,
            'currentYear' => $currentYear,
        ];

        // Appel à l'API
        $response = $this->client->request('POST', 'https://graphql.anilist.co', [
            'json' => [
                'query' => $query,
                'variables' => $variables,
            ]
        ]);

        return $response->toArray(); // On retourne les données sous forme de tableau
    }

    public function getNextSeasonAnime(): array{
        $currentMonth = date('n'); // Mois actuel
        $currentYear = date('Y'); // Année actuelle

        // Définition de la prochaine saison en fonction du mois actuel
        if($currentMonth >= 1 && $currentMonth <= 3){
            $nextSeason = 'SPRING';
            $nextSeasonYear = $currentYear;
        }
        elseif($currentMonth >= 4 && $currentMonth <= 6){
            $nextSeason = 'SUMMER';
            $nextSeasonYear = $currentYear;
        }
        elseif($currentMonth >= 7 && $currentMonth <= 9){
            $nextSeason = 'FALL';
            $nextSeasonYear = $currentYear;
        }
        elseif($currentMonth >= 10 && $currentMonth <= 12){
            $nextSeason = 'WINTER';
            $nextSeasonYear = $currentYear + 1;
        }

        // Définition de la query
        $query = '
            query ($nextSeason: MediaSeason, $nextSeasonYear: Int) {
                Page(page: 1, perPage: 6) {
                    media(season: $nextSeason, seasonYear: $nextSeasonYear, sort: POPULARITY_DESC){
                        id
                        title {
                            romaji
                        }
                        coverImage {
                            large
                            color
                        }
                        genres
                        episodes
                        format
                        studios {
                            edges {
                                isMain
                                node {
                                    name
                                }
                            }
                        }
                    }
                }
            }
        ';

        // Définition des variables utilisées dans la query
        $variables = [
            'nextSeason' => $nextSeason,
            'nextSeasonYear' => $nextSeasonYear,
        ];

        // Appel à l'API
        $response = $this->client->request('POST', 'https://graphql.anilist.co', [
            'json' => [
                'query' => $query,
                'variables' => $variables,
            ]
        ]);

        return $response->toArray(); // On retourne les données sous forme de tableau
    }
}