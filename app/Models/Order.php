<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;


class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    protected $fillable = ['customer_id','create_by','total_price','phone','address','city','district','ward','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_details');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data)
    {
        $validators = [
            'customer_id' => v::notEmpty()->setName('group_customer_id')->setTemplate('Khách hàng không được rỗng'),
            'create_by' => v::notEmpty()->setName('create_by')->setTemplate('Người tạo đơn hàng không được rỗng'),
            'total_price' => v::notEmpty()->setName('total_price')->setTemplate('Tổng tiền không được rỗng'),
            'phone' => v::notEmpty()->setName('phone')->setTemplate('Số điện thoại không được rỗng'),
            'address' => v::notEmpty()->setName('address')->setTemplate('Địa chỉ không được rỗng'),
            'city' => v::notEmpty()->setName('city')->setTemplate('Thành phố không được rỗng'),
            'district' => v::notEmpty()->setName('district')->setTemplate('Đường không được rỗng'),
            'ward' => v::notEmpty()->setName('ward')->setTemplate('Phường không được rỗng'),
            'status' => v::notEmpty()->setName('status')->setTemplate('Trạng thái không được rỗng'),
        ];

        $errors = [];
        foreach ($validators as $field => $validator) {
            try {
                $validator->assert(isset($data[$field]) ? $data[$field] : null);
            } catch (ValidationException $exception) {
                $errors[$field] = $exception->getMessages();
            }
        }

        if (isset($data['customer_id']) && !Customer::find($data['customer_id'])) {
            $errors['customer_id'] = ['Khách hàng không tồn tại'];
        }

        if (isset($data['create_by']) && !Profile::find($data['create_by'])) {
            $errors['create_by'] = ['Người dùng không tồn tại'];
        }

        if (!empty($errors)) {
            throw new Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}