<?php

namespace App\Services\Setting;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingService
{
    public function getSetting()
    {
        try {
            $setting = Setting::first();

            return $setting;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request)
    {
        try {
            $rules = [
                'limit' => 'required|integer',
                'display' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];
    
            $validator = Validator::make($request->all(), $rules);
    
            if ($validator->fails()) throw new Exception($validator->errors());
    
            $settingToUpdate = Setting::first();
    
            if(!isset($settingToUpdate)) throw new Exception('Configuração não encontrada');
    
            $dataToUpdate = $validator->validated();
    
            if ($request->hasFile('display')) {
                $displayFile = $request->file('display');
                $displayPath = $displayFile->store('public/display');
                $displayFilename = basename($displayPath);
    
                $dataToUpdate['display'] = $displayFilename;
            }
    
            $settingToUpdate->update($dataToUpdate);

            return ['status' => true, 'data' => $settingToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
    
}
