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

    /**
     * Fonction qui permet d'obtenir tout les personnages d'un animé
     */
    public function getAllCharactersAnime(int $id){
        // Définition de la query
        $query = '
            query($id: Int){
                Media(id: $id, type: ANIME){
                    id
                    title{
                        romaji
                    }
                    characters{
                        edges{
                            role
                            node{
                                id
                                name{
                                    full
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

    /**
     * Fonction qui permet d'obtenir tout le staff d'un animé
     */
    public function getAllStaffAnime(int $id){
        // Définition de la query
        $query = '
            query($id: Int){
                Media(id: $id, type: ANIME){
                    id
                    title{
                        romaji
                    }
                    staff{
                        edges{
                            role
                            node{
                                id
                                name{
                                    full
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

    /**
     * Fonction qui permet d'obtenir les meilleurs animés selon l'API
     */
    public function getTopAnimes(){
        // Définition de la query
        $query = '
            query{
                Page(page: 1, perPage: 50){
                    media(type: ANIME, sort: SCORE_DESC){
                        id
                        title{
                            romaji
                        }
                        coverImage{
                            large
                            color
                        }
                        genres
                        episodes
                        format
                        studios{
                            edges{
                                isMain
                                node{
                                    name
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        // Appel à l'API
        $response = $this->client->request('POST', 'https://graphql.anilist.co', [
            'json' => [
                'query' => $query,
            ]
        ]);

        return $response->toArray(); // On retourne les données sous forme de tableau
    }

    /**
     * Fonction qui permet d'obtenir les animés de la saison
     */
    public function getSeasonalAnimes(int $pageNumber){
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
            query($currentSeason: MediaSeason, $currentYear: Int, $pageNumber: Int){
                Page(page: $pageNumber, perPage: 50){
                    media(type: ANIME, sort: POPULARITY_DESC, season: $currentSeason, seasonYear: $currentYear, isAdult: false){
                        id
                        title{
                            romaji
                        }
                        coverImage{
                            large
                            color
                        }
                        genres
                        episodes
                        format
                        studios{
                            edges{
                                isMain
                                node{
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
            'pageNumber' => $pageNumber
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

    /**
     * Fonction qui permet de récupérer tout les animés en fonction de critères
     */
    public function getAllAnimes(int $pageNumber){
        // Définition de la query
        $query = '
            query($pageNumber: Int){
                Page(page: $pageNumber, perPage: 50){
                    media(type: ANIME, sort: TITLE_ROMAJI, isAdult: false){
                        id
                        title{
                            romaji
                        }
                        coverImage{
                            large
                            color
                        }
                        genres
                        episodes
                        format
                        studios{
                            edges{
                                isMain
                                node{
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
            'pageNumber' => $pageNumber,
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