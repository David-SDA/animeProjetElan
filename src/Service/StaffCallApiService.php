<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class StaffCallApiService{
    private $client;

    public function __construct(HttpClientInterface $client){
        $this->client = $client;
    }

    /**
     * Fonction qui permet d'obtenir les informations d'un membre de staff
     */
    public function getStaffDetails(int $id){
        // Définition de la query
        $query = '
            query($id: Int){
                Staff(id: $id){
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
                    primaryOccupations
                    staffMedia(type: ANIME, sort: START_DATE_DESC){
                        edges{
                            staffRole
                            roleNotes
                            node{
                                id
                                title {
                                    romaji
                                }
                                coverImage{
                                    large
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