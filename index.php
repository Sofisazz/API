<?php
header("Content-Type: application/json; charset=UTF-8");

// Включить вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Определяем базовый путь API
$base_path = '/3';
$request_uri = $_SERVER['REQUEST_URI'];

// Убираем базовый путь из URI
if (strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Подключение файлов конфигурации
include_once 'config/Database.php';
include_once 'config/Auth.php';
include_once 'models/SupplierModel.php';

// Обработка CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-Key, Content-Type, Authorization");

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Создание подключения к БД
    $database = new Database();
    $db = $database->getConnection();

    // Инициализация классов
    $auth = new Auth($db);
    $supplierModel = new SupplierModel($db);

    // Получение метода запроса
    $method = $_SERVER['REQUEST_METHOD'];

    // Получение API ключа из заголовка
    $headers = getallheaders();
    $api_key = '';
    
    if (isset($headers['X-API-Key'])) {
        $api_key = $headers['X-API-Key'];
    } elseif (isset($headers['x-api-key'])) {
        $api_key = $headers['x-api-key'];
    } elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    }

    // Проверка аутентификации для всех методов
    if (!$auth->validateApiKey($api_key)) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Доступ запрещен. Неверный или отсутствующий API ключ."
        ]);
        exit();
    }

    // Разбор URL (используем очищенный request_uri)
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path_parts = array_filter(explode('/', $path));
    
    $id = null;
    
    // Ищем ID в URL
    foreach ($path_parts as $index => $part) {
        if ($part === 'suppliers' && isset($path_parts[$index + 1])) {
            $next_part = $path_parts[$index + 1];
            if (is_numeric($next_part)) {
                $id = (int)$next_part;
                break;
            }
        }
    }

    // Если не нашли ID в пути, проверяем последнюю часть
    if (!$id && count($path_parts) > 0) {
        $last_part = end($path_parts);
        if (is_numeric($last_part)) {
            $id = (int)$last_part;
        }
    }

    // Маршрутизация запросов
    switch ($method) {
        case 'GET':
            if ($id) {
                // GET /api/suppliers/{id} - получить одного поставщика
                $supplier = $supplierModel->getById($id);
                if ($supplier) {
                    http_response_code(200);
                    echo json_encode([
                        "status" => "success",
                        "data" => $supplier
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        "status" => "error", 
                        "message" => "Поставщик не найден."
                    ]);
                }
            } else {
                // GET /api/suppliers - получить всех поставщиков
                $stmt = $supplierModel->getAll();
                $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "data" => $suppliers,
                    "count" => count($suppliers)
                ]);
            }
            break;

        case 'POST':
            // POST /api/suppliers - создать нового поставщика
            $input_data = json_decode(file_get_contents("php://input"), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Неверный формат JSON."
                ]);
                break;
            }
            
            // Валидация обязательных полей
            if (!isset($input_data['company_name']) || empty($input_data['company_name'])) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Поле 'company_name' обязательно для заполнения."
                ]);
                break;
            }

            if (!isset($input_data['contact_name']) || empty($input_data['contact_name'])) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error", 
                    "message" => "Поле 'contact_name' обязательно для заполнения."
                ]);
                break;
            }

            $new_id = $supplierModel->create($input_data);
            if ($new_id) {
                http_response_code(201);
                echo json_encode([
                    "status" => "success",
                    "message" => "Поставщик создан.",
                    "id" => $new_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => "Ошибка при создании поставщика."
                ]);
            }
            break;

        case 'PUT':
            // PUT /api/suppliers/{id} - обновить поставщика
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "ID поставщика не указан."
                ]);
                break;
            }

            // Проверка существования поставщика
            if (!$supplierModel->exists($id)) {
                http_response_code(404);
                echo json_encode([
                    "status" => "error",
                    "message" => "Поставщик не найден."
                ]);
                break;
            }

            $input_data = json_decode(file_get_contents("php://input"), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Неверный формат JSON."
                ]);
                break;
            }

            if ($supplierModel->update($id, $input_data)) {
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Поставщик обновлен."
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => "Ошибка при обновлении поставщика."
                ]);
            }
            break;

        case 'DELETE':
            // DELETE /api/suppliers/{id} - удалить поставщика
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "ID поставщика не указан."
                ]);
                break;
            }

            // Проверка существования поставщика
            if (!$supplierModel->exists($id)) {
                http_response_code(404);
                echo json_encode([
                    "status" => "error",
                    "message" => "Поставщик не найден."
                ]);
                break;
            }

            if ($supplierModel->delete($id)) {
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Поставщик удален."
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "status" => "error", 
                    "message" => "Ошибка при удалении поставщика."
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                "status" => "error",
                "message" => "Метод не поддерживается."
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Внутренняя ошибка сервера: " . $e->getMessage()
    ]);
}
?>