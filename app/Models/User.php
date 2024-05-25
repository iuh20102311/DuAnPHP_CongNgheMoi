<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class User extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $fillable = ['role_id','email','email_verified_at','password','reset_password_token','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;


    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }


    /**
     * @throws Exception
     */
    public function validate(array $data, bool $isUpdate = false): string
    {
        $validators = [
            'email' => v::notEmpty()->email()->setName('email')->setTemplate('Email không được rỗng và phải hợp lệ'),
            'password' => v::notEmpty()->setName('password')->setTemplate('Password không được rỗng'),
            'name' => v::notEmpty()->regex('/^[\p{Lu}\p{Ll}\sàáảãạăắằẳẵặâấầẩẫậèéẻẽẹêềếểễệđìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳỹỷỵ]+\s[\p{Lu}\p{Ll}\sàáảãạăắằẳẵặâấầẩẫậèéẻẽẹêềếểễệđìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳỹỷỵ]+\s[\p{Lu}\p{Ll}\sàáảãạăắằẳẵặâấầẩẫậèéẻẽẹêềếểễệđìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳỹỷỵ]+$/u', 'Tên phải viết theo dạng "Họ Tên" và cho phép viết chữ có dấu')->setName('name')->setTemplate('Tên không được rỗng và phải là ký tự chữ cái và khoảng trắng'),
        ];

        $error = "";
        foreach ($validators as $field => $validator) {
            if ($isUpdate && !array_key_exists($field, $data)) {
                continue;
            }

            try {
                $validator->assert(isset($data[$field]) ? $data[$field] : null);
            } catch (ValidationException $exception) {
                $error = $exception->getMessage();
                break;
            }
        }
        return $error;
    }
}