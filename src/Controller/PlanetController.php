<?php

namespace App\Controller;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;


class PlanetController
{
    private $client;
    private $response;


    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->response = new JsonResponse();
    }

    
    /**
     * Buscar un planeta
     * 
     * @param $only_check Si está en false devulve el objeto, en true, devuelve un boolean según exista o no
     * @return Response
     */
    public function show(int $id, Request $request, $only_check = false)
    {
        // Llamar a la API de Swapi.dev
        $planet_request = $this->client->request(
            'GET',
            'http://swapi.dev/api/planets/' . $id . '/',
            [
                'verify_peer' => false
            ]
        );

        // Comprobar si existe y devolver respuesta
        if ($planet_request->getStatusCode() != 200)
        {
            if ($only_check) return false;

            return $this->returnErrorResponse('El ID proporcionado no corresponde a ningún planeta.');
        }
        else
        {
            if ($only_check) return true;

            return $this->returnDataResponse($planet_request->toArray());
        }
    }

    
    /**
     * Crear un planeta mediante un JSON por el parámetro data
     * 
     * @return Response
     */
    public function create(Request $request)
    {
        $params = json_decode($request->get('data'), true);
        $valid_params = ['id', 'name', 'rotation_period', 'orbital_period', 'diameter'];
        
        // Convertir JSON a Array
        if (!is_array($params)) $params = json_decode($params, true);

        // Validar parámetros aceptados
        foreach ($params as $key => $value)
        {
            if (!in_array($key, $valid_params))
            {
                return $this->returnErrorResponse("El parámetro $key no es aceptado");
            }
        }

        // Buscar un planeta con el mismo ID
        $planet = $this->show($params['id'], $request, true);

        // Comprobar si existe
        if ($planet)
        {
            return $this->returnErrorResponse("Ya existe un planeta con este ID");
        }

        // Validar nombre
        if (!isset($params['name']) || strlen($params['name']) < 1)
        {
            return $this->returnErrorResponse("El nombre del planeta no es válido");
        }

        // Devolver planeta
        return $this->returnDataResponse($params);
    }


    /**
     * Devolver una respuesta de datos en formato JSON
     * 
     * @param array $data Array de datos a devolver
     * @return Response
     */
    private function returnDataResponse($data)
    {
        $this->response->setData([
            'success' => true,
            'data' => $data
        ]);

        return $this->response;
    }


    /**
     * Devolver una respuesta de error en formato JSON
     * 
     * @param string $message Mensaje de error a devolver
     * @return Response
     */
    private function returnErrorResponse($message)
    {
        $this->response->setStatusCode(400);

        $this->response->setData([
            'success' => false,
            'message' => $message
        ]);

        return $this->response;
    }
}