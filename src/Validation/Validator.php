<?php

declare(strict_types=1);

namespace RapidRest\Validation;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class Validator
{
    private $validator;

    public function __construct()
    {
        $this->validator = Validation::createValidator();
    }

    public function validate(array $data, array $rules): array
    {
        $constraints = $this->parseRules($rules);
        $violations = $this->validator->validate($data, $constraints);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $errors;
    }

    private function parseRules(array $rules): Assert\Collection
    {
        $constraints = [];

        foreach ($rules as $field => $rule) {
            $constraints[$field] = $this->parseRule($rule);
        }

        return new Assert\Collection($constraints);
    }

    private function parseRule(array|string $rule): Assert\Composite
    {
        if (is_string($rule)) {
            $rules = explode('|', $rule);
        } else {
            $rules = $rule;
        }

        $constraints = [];
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $parts = explode(':', $rule);
                $name = $parts[0];
                $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

                switch ($name) {
                    case 'required':
                        $constraints[] = new Assert\NotBlank();
                        break;
                    case 'email':
                        $constraints[] = new Assert\Email();
                        break;
                    case 'min':
                        $constraints[] = new Assert\Length(['min' => (int) $params[0]]);
                        break;
                    case 'max':
                        $constraints[] = new Assert\Length(['max' => (int) $params[0]]);
                        break;
                    case 'numeric':
                        $constraints[] = new Assert\Type(['type' => 'numeric']);
                        break;
                    case 'alpha':
                        $constraints[] = new Assert\Regex(['pattern' => '/^[a-zA-Z]+$/']);
                        break;
                    case 'alphanumeric':
                        $constraints[] = new Assert\Regex(['pattern' => '/^[a-zA-Z0-9]+$/']);
                        break;
                }
            } else {
                $constraints[] = $rule;
            }
        }

        return new Assert\All([
            'constraints' => $constraints,
        ]);
    }
}
