<?php

namespace App\Services\User;

use App\Helpers\Helper;
use App\Mail\EmailValidationMail;
use App\Models\PasswordRecovery;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordRecoveryMail;
use App\Models\UserEmailValidation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserService
{

    public function all()
    {
        try {
            $users = User::get();

            return ['status' => true, 'data' => $users];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;

            $users = User::with('files');

            if(isset($search_term)){
                $users->where('name', 'LIKE', "%{$search_term}%")
                    ->orWhere('email', 'LIKE', "%{$search_term}%");
            }

            $users = $users->paginate($perPage);

            return $users;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }  

    public function getUser()
    {
        try {
            $user = auth()->user();
    
            if ($user) {
                // Cast para o tipo correto
                $user = $user instanceof \App\Models\User ? $user : \App\Models\User::find($user->id);
    
                return ['status' => true, 'data' => $user];
            }
    
            return ['status' => false, 'error' => 'Usuário não autenticado', 'statusCode' => 401];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $request['photo'] = $request['photo'] == 'null' ? null : $request['photo'];

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string',
                'whatsapp' => 'nullable|string',
                'cpf_cnpj' => 'nullable|string',
                'birth_date' => 'nullable|date',
                'file_limit' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
                'is_admin' => 'nullable|boolean',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ];

            $requestData = $request->all();

            $duplicateUser = User::where('email', $requestData['email'])
                ->orWhere('cpf_cnpj', $requestData['cpf_cnpj'])
                ->first();
            
            if(isset($duplicateUser)){
                throw new Exception ('Email ou CPF/CNPJ já estão em uso.');
            }


            $globalLimit = Helper::getGlobalLimit();

            $requestData['file_limit'] = $globalLimit ?? 10;
            $requestData['is_active'] = $requestData['is_active'] ?? true;
            $requestData['is_admin'] = $requestData['is_admin'] ?? false;
            $requestData['password'] = Hash::make($requestData['password']);
    
            $validator = Validator::make($requestData, $rules);
    
            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }
    
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('photos', 'public');
                $fullPath = asset('storage/' . $path);
                $requestData['photo'] = $fullPath;
            }
    
            $user = User::create($requestData);

            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $user['userEmailValidation'] = UserEmailValidation::create([
                'user_id' => $user->id,
                'code' => $code
            ]);
    
            Mail::to($user->email)->send(new EmailValidationMail($code));
    
            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function email_validate($code){
        try{
            $userEmailValidation = UserEmailValidation::where('code', $code)
                ->orderBy('id', 'desc')
                ->first();
    
            if (!isset($userEmailValidation)) throw new Exception('Código inválido');

            $user = User::find($userEmailValidation->user_id);
            $user->email_verified_at = Carbon::now();
            $user->save();
            return ['status' => true, 'data' => $user];

        }catch(Exception $error){
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $user_id)
    {
        try {
            $request['photo'] = $request['photo'] == 'null' ? null : $request['photo'];

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'phone' => 'nullable|string',
                'whatsapp' => 'nullable|string',
                'cpf_cnpj' => 'nullable|string',
                'birth_date' => 'nullable|date',
                'file_limit' => 'nullable|integer|default:10',
                'is_active' => 'nullable|boolean|default:true',
                'is_admin' => 'nullable|boolean|default:false',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $userToUpdate = User::find($user_id);

            if(!isset($userToUpdate)) throw new Exception('Usuário não encontrado');

            $requestData = $validator->validated();

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('photos', 'public');
                $fullPath = asset('storage/' . $path);
                $requestData['photo'] = $fullPath;
            }

            $userToUpdate->update($requestData);

            return ['status' => true, 'data' => $userToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function userBlock($user_id)
    {
        try {
            $user = User::find($user_id);

            if (!$user) throw new Exception('Usuário não encontrado');

            $user->is_active = !$user->is_active;
            $user->save();

            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function changeLimit($request, $user_id)
    {
        try {
            $user = User::find($user_id);
    
            if (!$user) throw new Exception('Usuário não encontrado', 404);
    
            $rules = [
                "file_limit" => ['required', 'integer']
            ];
    
            $validator = Validator::make($request->all(), $rules);
    
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 422);
            }
    
            $validatedData = $validator->validated();
    
            $user->file_limit = $validatedData['file_limit'];
            $user->save();
    
            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => $error->getCode()];
        }
    }

    public function requestRecoverPassword($request)
    {
        try {
            $email = $request->email;
            $user = User::where('email', $email)->first();

            if (!isset($user)) throw new Exception('Usuário não encontrado.');

            $code = bin2hex(random_bytes(10));

            $recovery = PasswordRecovery::create([
                'code' => $code,
                'user_id' => $user->id
            ]);

            if (!$recovery) {
                throw new Exception('Erro ao tentar recuperar senha');
            }

            Mail::to($email)->send(new PasswordRecoveryMail($code));
            return ['status' => true, 'data' => $user];

        } catch (Exception $error) {
            Log::error('Erro na recuperação de senha: ' . $error->getMessage());
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }


    public function updatePassword($request){
        try{
            $code = $request->code;
            $password = $request->password;

            $recovery = PasswordRecovery::orderBy('id', 'desc')->where('code', $code)->first();

            if(!$recovery) throw new Exception('Código enviado não é válido.');

            $user = User::find($recovery->user_id);
            $user->password = Hash::make($password);
            $user->save();
            $recovery->delete();

            return ['status' => true, 'data' => $user];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

}
