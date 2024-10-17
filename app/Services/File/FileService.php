<?php

namespace App\Services\File;

use App\Models\File;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FileService
{

    public function all()
    {
        try {
            $files = File::get();

            return ['status' => true, 'data' => $files];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $userId = Auth::user()->id;

            $files = File::where('user_id', $userId)
                ->with('user')
                ->paginate($perPage);

            return $files;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
                'positionX' => 'nullable|numeric',
                'positionY' => 'nullable|numeric',
                'path' => 'required|mimes:doc,docx,pdf|max:5120', // Aceita arquivos até 5 MB
            ];
            
            $requestData = $request->all();
    
            // Validação dos dados
            $validator = Validator::make($requestData, $rules);
    
            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }
    
            if (!$request->hasFile('path')) throw new Exception('Arquivo para assinatura é obrigatório');

            $filePath = $request->file('path')->store('uploads', 'public'); 
            $requestData['path'] = $filePath;
            
            $file = File::create($requestData);
        
            return ['status' => true, 'data' => $file];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
    

}
