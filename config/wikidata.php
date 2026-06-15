<?php

return [
    'query_endpoint' => 'https://query.wikidata.org/sparql',
    'api_endpoint' => 'https://www.wikidata.org/w/api.php',

    'entities' => [
        'wcw' => 'Q130171',
    ],

    'properties' => [
        'instance_of' => 'P31',
        'organizer' => 'P664',
        'point_in_time' => 'P585',
        'location' => 'P276',
        'located_in' => 'P131',
        'participant' => 'P710',
        'part_of_series' => 'P179',
    ],

    'event_types' => [
        'professional_wrestling_event' => 'Q17361156',
        'wrestling_event' => 'Q18608583',
    ],
];
