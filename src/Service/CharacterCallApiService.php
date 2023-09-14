<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CharacterCallApiService{
    private $client;

    public function __construct(HttpClientInterface $client){
        $this->client = $client;
    }

    /**
     * Fonction qui permet d'obtenir toutes les informations d'un personnage
     */
    public function getCharacterDetails(int $id){
        // Définition de la query
        $query = '
            query($id: Int){
                Character(id: $id){
                    id
                    name{
                        full
                    }
                    image{
                        large
                    }
                    age
                    gender
                    description
                    media(type: ANIME, sort: START_DATE_DESC){
                        edges{
                            node{
                                id
                                title{
                                    romaji
                                }
                                coverImage{
                                    large
                                }
                            }
                            voiceActors(language: JAPANESE){
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
     * Fonction qui permet d'obtenir les informations nécessaire de plusieurs personnage (pour l'affichage des personnages favoris)
     */
    public function getMultipleCharactersDetails(array $characterIds){
        // Définition de la query
        $query = '
            query($characterIds: [Int]){
                Page(page: 1, perPage: 50){
                    characters(id_in: $characterIds){
                        id
                        name{
                            full
                        }
                        image{
                            large
                        }
                    }
                }
            }
        ';

        // Définition des variables utilisées dans la query
        $variables = [
            'characterIds' => $characterIds,
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
     * Fonction qui permet de récupérer la réponse données par l'API pour savoir si le personnage existe
     */
    public function getApiResponse(int $characterId){
        // Définition de la query
        $query = '
            query($characterId: Int){
                Character(id: $characterId){
                    id
                } 
            }
        ';

        // Définition des variables utilisées dans la query
        $variables = [
            'characterId' => $characterId,
        ];

        // Appel à l'API
        $response = $this->client->request('POST', 'https://graphql.anilist.co', [
            'json' => [
                'query' => $query,
                'variables' => $variables,
            ]
        ]);

        return $response->getStatusCode();
    }
}