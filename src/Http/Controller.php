<?php

declare(strict_types=1);

namespace RapidRest\Http;

use RapidRest\Validation\Validator;

abstract class Controller
{
    protected Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    protected function validate(array $data, array $rules): array
    {
        return $this->validator->validate($data, $rules);
    }

    protected function json($data, int $status = 200): Response
    {
        return (new Response())->withJson($data, $status);
    }

    protected function error(string $message, int $status = 400): Response
    {
        return $this->json(['error' => $message], $status);
    }

    protected function success($data = null, string $message = null, int $status = 200): Response
    {
        $response = ['success' => true];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $this->json($response, $status);
    }
}
