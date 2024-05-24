<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrderController
{
    public function getOrders(): Collection
    {
        $orders = Order::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $orders->where('status', $status);
        }

        if (isset($_GET['total_price'])) {
            $total_price = urldecode($_GET['total_price']);
            $orders->where('total_price', $total_price);
        }

        if (isset($_GET['phone'])) {
            $phone = urldecode($_GET['phone']);
            $orders->where('phone', $phone);
        }

        if (isset($_GET['city'])) {
            $city = urldecode($_GET['city']);
            $orders->where('city', 'like', '%' . $city . '%');
        }

        if (isset($_GET['district'])) {
            $district = urldecode($_GET['district']);
            $orders->where('district', 'like', '%' . $district . '%');
        }

        $orders = $orders->get();
        foreach ($orders as $index => $order) {
            $customer = Customer::query()->where('id',$order->customer_id)->first();
            unset($order->customer_id);
            $order->customer = $customer;

            $profile = Profile::query()->where('id', $order->created_by)->first();
            unset($order->created_by);
            $order->profile = $profile;
        }
        return $orders;
    }

    public function getOrderDetails(): Collection
    {
        $order_details = OrderDetail::query()->where('status', '!=' , 'DISABLE');

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $order_details->where('status', $status);
        }

        if (isset($_GET['price'])) {
            $price = urldecode($_GET['price']);
            $order_details->where('price', $price);
        }

        if (isset($_GET['quantity'])) {
            $quantity = urldecode($_GET['quantity']);
            $order_details->where('quantity', $quantity);
        }

        return $order_details->get();
    }

    public function getOrderById($id) : ?Model
    {
        $order = Order::query()->where('id', $id)->first();

        if (!$order) {
            return null;
        }
        $customer = Customer::query()->where('id', $order->customer_id)->first();
        $profile = Profile::query()->where('id', $order->created_by)->first();
        unset($order->customer_id, $order->created_by);
        $order->customer = $customer;
        $order->profile = $profile;
        return $order;
    }

    public function getProductByOrder($id)
    {
        $order = Order::query()->where('id',$id)->first();
        return $order->products;
    }

    public function addProductToOrder($id)
    {
        $order = Order::query()->where('id',$id)->first();
        $data = json_decode(file_get_contents('php://input'),true);
        $product = Product::query()->where('id',$data['product_id'])->first();
        $order->products()->attach($product);
        return 'Thêm thành công';
    }

    public function getOrderDetailByOrder($id)
    {
        $orderDetails = OrderDetail::query()->where('order_id', $id)->get();
        return $orderDetails;
    }

    public function createOrder(): Model | string
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $order = new Order();
        $error = $order->validate($data);
        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }
        $order->fill($data);
        $order->save();
        return $order;
    }

    public function updateOrderById($id): bool | int | string
    {
        $order = Order::find($id);

        if (!$order) {
            http_response_code(404);
            return json_encode(["error" => "Provider not found"]);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $error = $order->validate($data, true);

        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }

        $order->fill($data);
        $order->save();

        return $order;
    }

    public function deleteOrder($id)
    {
        $order = Order::find($id);

        if ($order) {
            $order->status = 'DELETED';
            $order->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}