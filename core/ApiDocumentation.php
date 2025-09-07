<?php

namespace TPT\ERP\Core;

use ReflectionClass;
use ReflectionMethod;

/**
 * API Documentation Generator
 *
 * Generates OpenAPI/Swagger documentation from PHP controllers
 */
class ApiDocumentation
{
    private array $spec = [];
    private array $controllers = [];

    public function __construct()
    {
        $this->initializeSpec();
    }

    /**
     * Initialize OpenAPI specification
     */
    private function initializeSpec(): void
    {
        $this->spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'TPT Free ERP API',
                'description' => 'Comprehensive ERP system API with 32+ modules',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'TPT Open Source',
                    'url' => 'https://github.com/PhillipC05/tpt-free-erp'
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT'
                ]
            ],
            'servers' => [
                [
                    'url' => '/api/v1',
                    'description' => 'API v1'
                ]
            ],
            'security' => [
                [
                    'bearerAuth' => []
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ],
                'schemas' => $this->getCommonSchemas()
            ],
            'paths' => [],
            'tags' => []
        ];
    }

    /**
     * Get common schemas for API documentation
     */
    private function getCommonSchemas(): array
    {
        return [
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'success' => [
                        'type' => 'boolean',
                        'example' => false
                    ],
                    'message' => [
                        'type' => 'string',
                        'example' => 'Error message'
                    ],
                    'errors' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'type' => 'string'
                        ]
                    ]
                ]
            ],
            'Success' => [
                'type' => 'object',
                'properties' => [
                    'success' => [
                        'type' => 'boolean',
                        'example' => true
                    ],
                    'message' => [
                        'type' => 'string',
                        'example' => 'Success message'
                    ],
                    'data' => [
                        'type' => 'object',
                        'description' => 'Response data'
                    ]
                ]
            ],
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'uuid' => ['type' => 'string', 'format' => 'uuid'],
                    'username' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'first_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'suspended']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'Pagination' => [
                'type' => 'object',
                'properties' => [
                    'page' => ['type' => 'integer', 'minimum' => 1],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                    'data' => [
                        'type' => 'array',
                        'items' => ['type' => 'object']
                    ]
                ]
            ]
        ];
    }

    /**
     * Add controller to documentation
     */
    public function addController(string $controllerClass): void
    {
        if (!class_exists($controllerClass)) {
            return;
        }

        $reflection = new ReflectionClass($controllerClass);
        $classDoc = $reflection->getDocComment();

        // Extract controller info from docblock
        $controllerInfo = $this->parseControllerDoc($classDoc, $controllerClass);

        if ($controllerInfo) {
            $this->controllers[] = $controllerInfo;
            $this->spec['tags'][] = [
                'name' => $controllerInfo['tag'],
                'description' => $controllerInfo['description']
            ];
        }

        // Process methods
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $controllerClass) {
                $this->processMethod($method, $controllerInfo);
            }
        }
    }

    /**
     * Parse controller docblock
     */
    private function parseControllerDoc(?string $doc, string $className): ?array
    {
        if (!$doc) {
            return null;
        }

        $lines = explode("\n", $doc);
        $description = '';
        $tag = '';

        foreach ($lines as $line) {
            $line = trim($line, " \t/*");
            if (strpos($line, '@') === 0) {
                if (strpos($line, '@apiGroup') === 0) {
                    $tag = trim(str_replace('@apiGroup', '', $line));
                }
            } elseif (!empty($line)) {
                $description .= $line . ' ';
            }
        }

        if (empty($tag)) {
            // Generate tag from class name
            $tag = str_replace(['Controller', 'TPT\\ERP\\Api\\Controllers\\'], '', $className);
            $tag = preg_replace('/(?<!^)[A-Z]/', ' $0', $tag);
        }

        return [
            'class' => $className,
            'tag' => $tag,
            'description' => trim($description)
        ];
    }

    /**
     * Process method for API documentation
     */
    private function processMethod(ReflectionMethod $method, ?array $controllerInfo): void
    {
        $doc = $method->getDocComment();
        if (!$doc) {
            return;
        }

        $apiInfo = $this->parseMethodDoc($doc);
        if (!$apiInfo) {
            return;
        }

        $path = $apiInfo['path'];
        $httpMethod = strtolower($apiInfo['method']);

        if (!isset($this->spec['paths'][$path])) {
            $this->spec['paths'][$path] = [];
        }

        $operation = [
            'tags' => [$controllerInfo['tag'] ?? 'General'],
            'summary' => $apiInfo['summary'],
            'description' => $apiInfo['description'],
            'operationId' => $method->getName(),
            'responses' => $this->buildResponses($apiInfo)
        ];

        // Add parameters
        if (!empty($apiInfo['parameters'])) {
            $operation['parameters'] = $apiInfo['parameters'];
        }

        // Add request body
        if (!empty($apiInfo['body'])) {
            $operation['requestBody'] = $apiInfo['body'];
        }

        // Add security
        if (!empty($apiInfo['auth'])) {
            $operation['security'] = [['bearerAuth' => []]];
        }

        $this->spec['paths'][$path][$httpMethod] = $operation;
    }

    /**
     * Parse method docblock for API information
     */
    private function parseMethodDoc(string $doc): ?array
    {
        $lines = explode("\n", $doc);
        $api = [
            'method' => 'GET',
            'path' => '',
            'summary' => '',
            'description' => '',
            'auth' => false,
            'parameters' => [],
            'body' => null
        ];

        $inDescription = false;
        $currentParam = null;

        foreach ($lines as $line) {
            $line = trim($line, " \t/*");

            if (strpos($line, '@api') === 0) {
                if (strpos($line, '@apiMethod') === 0) {
                    $api['method'] = trim(str_replace('@apiMethod', '', $line));
                } elseif (strpos($line, '@apiPath') === 0) {
                    $api['path'] = trim(str_replace('@apiPath', '', $line));
                } elseif (strpos($line, '@apiSummary') === 0) {
                    $api['summary'] = trim(str_replace('@apiSummary', '', $line));
                } elseif (strpos($line, '@apiDescription') === 0) {
                    $api['description'] = trim(str_replace('@apiDescription', '', $line));
                    $inDescription = true;
                } elseif (strpos($line, '@apiAuth') === 0) {
                    $api['auth'] = true;
                } elseif (strpos($line, '@apiParam') === 0) {
                    $param = $this->parseApiParam($line);
                    if ($param) {
                        $api['parameters'][] = $param;
                    }
                } elseif (strpos($line, '@apiBody') === 0) {
                    $api['body'] = $this->parseApiBody($line);
                }
            } elseif ($inDescription && !empty($line) && strpos($line, '@') !== 0) {
                $api['description'] .= ' ' . $line;
            } elseif (empty($line) && $inDescription) {
                $inDescription = false;
            }
        }

        // Clean up description
        $api['description'] = trim($api['description']);

        // Return null if no API path specified
        if (empty($api['path'])) {
            return null;
        }

        return $api;
    }

    /**
     * Parse @apiParam annotation
     */
    private function parseApiParam(string $line): ?array
    {
        // @apiParam (type) name [description]
        $pattern = '/@apiParam\s*\(([^)]+)\)\s*(\w+)\s*(.*)/';
        if (preg_match($pattern, $line, $matches)) {
            $type = $matches[1];
            $name = $matches[2];
            $description = trim($matches[3]);

            $param = [
                'name' => $name,
                'in' => 'query', // Default to query parameter
                'description' => $description,
                'schema' => [
                    'type' => $this->mapType($type)
                ]
            ];

            // Check if it's a path parameter
            if (strpos($line, '{') !== false && strpos($line, '}') !== false) {
                $param['in'] = 'path';
                $param['required'] = true;
            }

            return $param;
        }

        return null;
    }

    /**
     * Parse @apiBody annotation
     */
    private function parseApiBody(string $line): ?array
    {
        // @apiBody (type) [description]
        $pattern = '/@apiBody\s*\(([^)]+)\)\s*(.*)/';
        if (preg_match($pattern, $line, $matches)) {
            $type = $matches[1];
            $description = trim($matches[2]);

            return [
                'description' => $description ?: 'Request body',
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => $this->mapType($type)
                        ]
                    ]
                ]
            ];
        }

        return null;
    }

    /**
     * Map PHP type to OpenAPI type
     */
    private function mapType(string $type): string
    {
        $typeMap = [
            'int' => 'integer',
            'integer' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object'
        ];

        return $typeMap[$type] ?? 'string';
    }

    /**
     * Build responses for operation
     */
    private function buildResponses(array $apiInfo): array
    {
        $responses = [
            '200' => [
                'description' => 'Success',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Success'
                        ]
                    ]
                ]
            ],
            '400' => [
                'description' => 'Bad Request',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
            '401' => [
                'description' => 'Unauthorized',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
            '403' => [
                'description' => 'Forbidden',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
            '404' => [
                'description' => 'Not Found',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ]
        ];

        return $responses;
    }

    /**
     * Generate OpenAPI JSON specification
     */
    public function generateJson(): string
    {
        return json_encode($this->spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate OpenAPI YAML specification
     */
    public function generateYaml(): string
    {
        // For YAML generation, we'd need a YAML library
        // For now, return JSON as YAML is less common for APIs
        return $this->generateJson();
    }

    /**
     * Save specification to file
     */
    public function saveToFile(string $path, string $format = 'json'): bool
    {
        $content = $format === 'yaml' ? $this->generateYaml() : $this->generateJson();

        return file_put_contents($path, $content) !== false;
    }

    /**
     * Get specification array
     */
    public function getSpec(): array
    {
        return $this->spec;
    }

    /**
     * Auto-discover controllers in the API directory
     */
    public function autoDiscoverControllers(string $apiPath = __DIR__ . '/../api/controllers'): void
    {
        if (!is_dir($apiPath)) {
            return;
        }

        $files = scandir($apiPath);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = 'TPT\\ERP\\Api\\Controllers\\' . pathinfo($file, PATHINFO_FILENAME);
                $this->addController($className);
            }
        }
    }
}
