<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'customers';
    protected $fillable = ['group_customer_id','name','phone','gender','birthday','email','address','city','district','ward','note','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class,'customer_id');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data)
    {
        $validators = [
            'group_customer_id' => v::notEmpty()->setName('group_customer_id')->setTemplate('Nhóm khách hàng không được rỗng'),
            'name' => v::notEmpty()->setName('name')->setTemplate('Tên không được rỗng'),
            'phone' => v::notEmpty()->setName('phone')->setTemplate('Số điện thoại không được rỗng'),
            'gender' => v::notEmpty()->setName('gender')->setTemplate('Giới tính không được rỗng'),
            'email' => v::notEmpty()->setName('email')->setTemplate('Email không được rỗng'),
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

        if (isset($data['group_customer_id']) && !GroupCustomer::find($data['group_customer_id'])) {
            $errors['group_customer_id'] = ['Nhóm khách hàng không tồn tại'];
        }

        if (!empty($errors)) {
            throw new Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}