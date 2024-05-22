<?php
require 'Database.php';

use App\Database;
use Dotenv\Dotenv;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\RouteCollector;

header("Content-Type: application/json; charset=UTF-8");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: *');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

new Database();

$router = new RouteCollector();

$router->filter('auth', function () {
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        http_response_code(401);
        error_log("Không có giá trị Token");
        return false;
    }

    $parser = new Parser(new JoseEncoder());
    try {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (str_starts_with($authorizationHeader, 'Bearer ')) {
            $token = $parser->parse(substr($authorizationHeader, 7));
            assert($token instanceof Plain);
            $now = new DateTimeImmutable();
            if ($token->isExpired($now)) {
                error_log("Token is expired");
                http_response_code(401);
                return false;
            }
        }
    } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
        error_log($e->getMessage());
        http_response_code(401);
        return false;
    }
});

$router->group(array('prefix' => '/api'), function (RouteCollector $router) {
    $router->group(array('prefix' => '/v1/auth'), function (RouteCollector $router) {
        $router->post('/login', ['App\Controllers\AuthController', 'login']);
        $router->post('/refreshtoken', ['App\Controllers\AuthController', 'refreshToken']);
        $router->post('/register', ['App\Controllers\AuthController', 'register']);
        $router->post('/changepassword', ['App\Controllers\AuthController', 'changePassword']);
        $router->get('/profile', ['App\Controllers\AuthController', 'getProfile']);
    });

    $router->group(array('prefix' => '/v1/products'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\ProductController', 'getProducts'], ['before' => 'auth']);
        $router->get('/{id}', ['App\Controllers\ProductController', 'getProductById']);
        $router->get('/{id}/materials', ['App\Controllers\ProductController', 'getMaterialByProduct']);
        $router->get('/{id}/categories', ['App\Controllers\ProductController', 'getCategoryByProduct']);
        $router->get('/{id}/discounts', ['App\Controllers\ProductController', 'getDiscountByProduct']);
        $router->get('/{id}/orders', ['App\Controllers\ProductController', 'getOrderByProduct']);
        $router->get('/{id}/inventories', ['App\Controllers\ProductController', 'getProductIventoryByProduct']);
        $router->post('/{id}/categories', ['App\Controllers\ProductController', 'addCategoryToProduct']);
        $router->post('/{id}/discounts', ['App\Controllers\ProductController', 'addDiscountToProduct']);
        $router->post('/{id}/orders', ['App\Controllers\ProductController', 'addOrderToProduct']);
        $router->post('/', ['App\Controllers\ProductController', 'createProduct']);
        $router->put('/{id}', ['App\Controllers\ProductController', 'updateProductById']);
        $router->delete('/{id}', ['App\Controllers\ProductController', 'deleteProduct']);
    });

    $router->group(array('prefix' => '/v1/product_prices'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\ProductPriceController', 'getProductPrices'], ['before' => 'auth']);
        $router->post('/', ['App\Controllers\ProductPriceController', 'createProductPrice']);
        $router->get('/{id}', ['App\Controllers\ProductPriceController', 'getProductPriceById']);
        $router->put('/{id}', ['App\Controllers\ProductPriceController', 'updateProductPriceById']);
        $router->delete('/{id}', ['App\Controllers\ProductPriceController', 'deleteProductPrice']);
    });

    $router->group(array('prefix' => '/v1/product_inventories'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\ProductInventoryController', 'getProductInventories']);
        $router->get('/{id}', ['App\Controllers\ProductInventoryController', 'getProductInventoryById']);
        $router->post('/', ['App\Controllers\ProductInventoryController', 'createProductInventory']);
        $router->put('/{id}', ['App\Controllers\ProductInventoryController', 'updateProductInventoryById']);
        $router->delete('/{id}', ['App\Controllers\ProductInventoryController', 'deleteProductInventory']);
    });

    $router->group(array('prefix' => '/v1/categories'), function (RouteCollector $router) {
        $router->get('/{id}', ['App\Controllers\CategoryController', 'getCategoryById']);
        $router->get('/{id}/products', ['App\Controllers\CategoryController', 'getProductByCategory']);
        $router->get('/{id}/discounts', ['App\Controllers\CategoryController', 'getDiscountByCategory']);
        $router->get('/{id}/materials', ['App\Controllers\CategoryController', 'getMaterialByCategory']);
        $router->post('/{id}/products', ['App\Controllers\CategoryController', 'addProductToCategory']);
        $router->post('/{id}/discounts', ['App\Controllers\CategoryController', 'addDiscountToCategory']);
        $router->post('/{id}/materials', ['App\Controllers\CategoryController', 'addMaterialToCategory']);
        $router->put('/{id}', ['App\Controllers\CategoryController', 'updateCategoryById']);
        $router->delete('/{id}', ['App\Controllers\CategoryController', 'deleteCategory']);
        $router->get('/', ['App\Controllers\CategoryController', 'getCategories']);
        $router->post('/', ['App\Controllers\CategoryController', 'createCategory']);
    });

    $router->group(array('prefix' => '/v1/discounts'), function (RouteCollector $router) {
        $router->get('/{id}', ['App\Controllers\DiscountController', 'getDiscountById']);
        $router->get('/{id}/products', ['App\Controllers\DiscountController', 'getProductByDiscount']);
        $router->get('/{id}/categories', ['App\Controllers\DiscountController', 'getCategoryByDiscount']);
        $router->post('/{id}/products', ['App\Controllers\DiscountController', 'addProductToDiscount']);
        $router->post('/{id}/categories', ['App\Controllers\DiscountController', 'addCategoryToDiscount']);
        $router->put('/{id}', ['App\Controllers\DiscountController', 'updateDiscountById']);
        $router->delete('/{id}', ['App\Controllers\DiscountController', 'deleteDiscount']);
        $router->get('/', ['App\Controllers\DiscountController', 'getDiscounts']);
        $router->post('/', ['App\Controllers\DiscountController', 'createDiscount']);
    });

    $router->group(array('prefix' => '/v1/materials'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\MaterialController', 'getMaterials']);
        $router->get('/{id}', ['App\Controllers\MaterialController', 'getMaterialById']);
        $router->get('/{id}/providers', ['App\Controllers\MaterialController', 'getProviderByMaterial']);
        $router->get('/{id}/categories', ['App\Controllers\MaterialController', 'getCategoryByMaterial']);
        $router->get('/{id}/export_receipt_details', ['App\Controllers\MaterialController', 'getExportReceiptDetailsByMaterial']);
        $router->get('/{id}/import_receipt_details', ['App\Controllers\MaterialController', 'getImportReceiptDetailsByMaterial']);
        $router->post('/{id}/providers', ['App\Controllers\MaterialController', 'addProviderToMaterial']);
        $router->post('/{id}/categories', ['App\Controllers\MaterialController', 'addCategoryToMaterial']);
        $router->post('/{id}/export_receipt_details', ['App\Controllers\MaterialController', 'addExportReceiptDetailToMaterial']);
        $router->post('/', ['App\Controllers\MaterialController', 'createMaterial']);
        $router->put('/{id}', ['App\Controllers\MaterialController', 'updateMaterialById']);
        $router->delete('/{id}', ['App\Controllers\MaterialController', 'deleteMaterial']);
    });

    $router->group(array('prefix' => '/v1/providers'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\ProviderController', 'getProviders']);
        $router->get('/{id}', ['App\Controllers\ProviderController', 'getProviderById']);
        $router->get('/{id}/materials', ['App\Controllers\ProviderController', 'getMaterialByProvider']);
        $router->post('/{id}/materials', ['App\Controllers\ProviderController', 'addMaterialToProvider']);
        $router->post('/', ['App\Controllers\ProviderController', 'createProvider']);
        $router->put('/{id}', ['App\Controllers\ProviderController', 'updateProviderById']);
        $router->delete('/{id}', ['App\Controllers\ProviderController', 'deleteProvider']);
    });

    $router->group(array('prefix' => '/v1/material_export_receipts'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\MaterialExportReceiptController', 'getMaterialExportReceipts']);
        $router->post('/', ['App\Controllers\MaterialExportReceiptController', 'createMaterialExportReceipt']);
        $router->get('/{id}', ['App\Controllers\MaterialExportReceiptController', 'getMaterialExportReceiptById']);
        $router->get('/{id}/details', ['App\Controllers\MaterialExportReceiptController', 'getExportReceiptDetailsByExportReceipt']);
        $router->put('/{id}', ['App\Controllers\MaterialExportReceiptController', 'updateMaterialExportReceiptById']);
        $router->delete('/{id}', ['App\Controllers\MaterialExportReceiptController', 'deleteMaterialExportReceipt']);
    });

    $router->group(array('prefix' => '/v1/material_import_receipts'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\MaterialImportReceiptController', 'getMaterialImportReceipts']);
        $router->post('/', ['App\Controllers\MaterialImportReceiptController', 'createMaterialImportReceipt']);
        $router->get('/{id}', ['App\Controllers\MaterialImportReceiptController', 'getMaterialImportReceiptById']);
        $router->get('/{id}/details', ['App\Controllers\MaterialImportReceiptController', 'getImportReceiptDetailsByImportReceipt']);
        $router->put('/{id}', ['App\Controllers\MaterialImportReceiptController', 'updateMaterialImportReceiptById']);
        $router->delete('/{id}', ['App\Controllers\MaterialImportReceiptController', 'deleteMaterialImportReceipt']);
    });

    $router->group(array('prefix' => '/v1/product_export_receipts'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\ProductExportReceiptController', 'getProductExportReceipts']);
        $router->post('/', ['App\Controllers\ProductExportReceiptController', 'createProductExportReceipt']);
        $router->get('/{id}', ['App\Controllers\ProductExportReceiptController', 'getProductExportReceiptById']);
        $router->get('/{id}/details', ['App\Controllers\ProductExportReceiptController', 'getExportReceiptDetailsByExportReceipt']);
        $router->put('/{id}', ['App\Controllers\ProductExportReceiptController', 'updateProductExportReceiptById']);
        $router->delete('/{id}', ['App\Controllers\ProductExportReceiptController', 'deleteProductExportReceipt']);
    });

    $router->group(array('prefix' => '/v1/product_import_receipts'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\ProductImportReceiptController', 'getProductImportReceipts']);
        $router->post('/', ['App\Controllers\ProductImportReceiptController', 'createProductImportReceipt']);
        $router->get('/{id}', ['App\Controllers\ProductImportReceiptController', 'getProductImportReceiptById']);
        $router->get('/{id}/details', ['App\Controllers\ProductImportReceiptController', 'getImportReceiptDetailsByExportReceipt']);
        $router->put('/{id}', ['App\Controllers\ProductImportReceiptController', 'updateProductImportReceiptById']);
        $router->delete('/{id}', ['App\Controllers\ProductImportReceiptController', 'deleteProductImportReceipt']);
    });

    $router->group(array('prefix' => '/v1/warehouses'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\WarehouseController', 'getWarehouses']);
        $router->post('/', ['App\Controllers\WarehouseController', 'createWarehouse']);
        $router->get('/{id}', ['App\Controllers\WarehouseController', 'getWarehouseById']);
        $router->get('/{id}/inventories', ['App\Controllers\WarehouseController', 'getProductInventoryByWarehouse']);
        $router->put('/{id}', ['App\Controllers\WarehouseController', 'updateWarehouseById']);
        $router->delete('/{id}', ['App\Controllers\WarehouseController', 'deleteWarehouse']);
    });

    $router->group(array('prefix' => '/v1/orders'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\OrderController', 'getOrders']);
        $router->get('/{id}', ['App\Controllers\OrderController', 'getOrderById']);
        $router->get('/{id}/products', ['App\Controllers\OrderController', 'getProductByOrder']);
        $router->get('/{id}/details', ['App\Controllers\OrderController', 'getOrderDetailByOrder']);
        $router->post('/{id}/products', ['App\Controllers\OrderController', 'addProductToOrder']);
        $router->post('/', ['App\Controllers\OrderController', 'createOrder']);
        $router->put('/{id}', ['App\Controllers\OrderController', 'updateOrderById']);
        $router->delete('/{id}', ['App\Controllers\OrderController', 'deleteOrder']);
    });

    $router->group(array('prefix' => '/v1/order_details'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\OrderController', 'getOrderDetails']);
        $router->delete('/{id}', ['App\Controllers\OrderController', 'deleteOrder']);
    });

    $router->group(array('prefix' => '/v1/users'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\UserController', 'getUsers']);
        $router->get('/{id}', ['App\Controllers\UserController', 'getUserById']);
        $router->get('/{id}/inventorytransactions', ['App\Controllers\UserController', 'getInventoryTransactionByUser']);
        $router->get('/{id}/profile', ['App\Controllers\UserController', 'getProfileByUser']);
        $router->get('/{id}/orders', ['App\Controllers\UserController', 'getOrderByUser']);
        $router->put('/{id}', ['App\Controllers\UserController', 'updateUserById']);
        $router->delete('/{id}', ['App\Controllers\UserController', 'deleteUser']);
    });

    $router->group(array('prefix' => '/v1/profiles'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\ProfileController', 'getProfile']);
        $router->post('/', ['App\Controllers\ProfileController', 'createProfile']);
        $router->get('/{id}', ['App\Controllers\ProfileController', 'getProfileById']);
        $router->put('/{id}', ['App\Controllers\ProfileController', 'updateProfileById']);
        $router->delete('/{id}', ['App\Controllers\ProfileController', 'deleteProfile']);
    });

    $router->group(array('prefix' => '/v1/group_customers'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\GroupCustomerController', 'getGroupCustomers']);
        $router->post('/', ['App\Controllers\GroupCustomerController', 'createGroupCustomer']);
        $router->get('/{id}', ['App\Controllers\GroupCustomerController', 'getGroupCustomerById']);
        $router->get('/{id}/customers', ['App\Controllers\GroupCustomerController', 'getCustomerByGroupCustomer']);
        $router->put('/{id}', ['App\Controllers\GroupCustomerController', 'updateGroupCustomerById']);
        $router->delete('/{id}', ['App\Controllers\GroupCustomerController', 'deleteGroupCustomer']);
    });

    $router->group(array('prefix' => '/v1/customers'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\CustomerController', 'getCustomers']);
        $router->post('/', ['App\Controllers\CustomerController', 'createCustomer']);
        $router->get('/{id}', ['App\Controllers\CustomerController', 'getCustomerById']);
        $router->get('/{id}/orders', ['App\Controllers\CustomerController', 'getOrderByCustomer']);
        $router->put('/{id}', ['App\Controllers\CustomerController', 'updateCustomerById']);
        $router->delete('/{id}', ['App\Controllers\CustomerController', 'deleteCustomer']);
    });

    $router->group(array('prefix' => '/v1/roles'), function (RouteCollector $router) {
        $router->get('/', ['App\Controllers\RoleController', 'getRoles']);
        $router->post('/', ['App\Controllers\RoleController', 'createRole']);
        $router->get('/{id}', ['App\Controllers\RoleController', 'getRoleById']);
        $router->get('/{id}/users', ['App\Controllers\RoleController', 'getUserByRole']);
        $router->put('/{id}', ['App\Controllers\RoleController', 'updateRoleById']);
        $router->delete('/{id}', ['App\Controllers\RoleController', 'deleteRole']);
    });
});


$dispatcher = new Dispatcher($router->getData());

$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

echo $response;
