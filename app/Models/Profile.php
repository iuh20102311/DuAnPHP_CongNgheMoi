<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class Profile extends Model
{
    use HasFactory;
    protected $table = 'profiles';
    protected $fillable = ['user_id','first_name','last_name','phone','birthday','avatar','gender','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * @throws Exception
     */
    public function validate(array $data)
    {
        $validators = [
            'user_id' => v::notEmpty()->setName('user_id')->setTemplate('Người dùng không được rỗng'),
            'first_name' => v::notEmpty()->setName('first_name')->setTemplate('Tên lót không được rỗng'),
            'last_name' => v::notEmpty()->setName('last_name')->setTemplate('Tên không được rỗng'),
            'phone' => v::notEmpty()->setName('phone')->setTemplate('Số điện thoại không được rỗng'),
            'gender' => v::notEmpty()->setName('gender')->setTemplate('Giới tính không được rỗng'),
            'avatar' => v::notEmpty()->setName('email')->setTemplate('Email không được rỗng'),
        ];

        $errors = [];
        foreach ($validators as $field => $validator) {
            try {
                $validator->assert(isset($data[$field]) ? $data[$field] : null);
            } catch (ValidationException $exception) {
                $errors[$field] = $exception->getMessages();
            }
        }

        if (isset($data['user_id']) && !User::find($data['user_id'])) {
            $errors['user_id'] = ['Người dùng không tồn tại'];
        }

        if (!empty($errors)) {
            throw new Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}