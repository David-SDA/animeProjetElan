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
                        medium
                    }
                    age
                    gender
                    description
                    media(type: ANIME){
                        nodes{
                            id
                            title{
                                romaji
                            }
                            coverImage{
                                large
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