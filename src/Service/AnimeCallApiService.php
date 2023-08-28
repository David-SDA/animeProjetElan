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
        // Définition de la query
        $query = '
            query($id: Int){
                Media(id: $id, type: ANIME){
                    id
                    title{
                        romaji
                    }
                    description
                    coverImage{
                        large
                        color
                    }
                    format
                    status
                    startDate{
                        year
                        month
                        day
                    }
                    endDate{
                        year
                        month
                        day
                    }
                    season
                    seasonYear
                    episodes
                    duration
                    source
                    genres
                    studios{
                        edges{
                            isMain
                            node{
                                name
                            }
                        }
                    }
                    nextAiringEpisode{
                        episode
                        airingAt
                        timeUntilAiring
                    }
                    characters{
                        edges{
                            role
                            node{
                                id
                                name{
                                    full
                                    userPreferred
                                }
                                image{
                                    medium
                                }
                            }
                            voiceActors{
                                id
                                name{
                                    full
                                }
                                languageV2
                            }
                        }
                    }
                    staff{
                        edges{
                            role
                            node{
                                id
                                name{
                                    full
                                    userPreferred
                                }
                                image{
                                    medium
                                }
                            }
                        }
                    }
                }
            }
        ';

        // Définition des variables utilisées dans la query
        $variables = [
            'id' => $id,
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