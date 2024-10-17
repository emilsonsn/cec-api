<?php

namespace App\Services\Setting;

use App\Models\Setting;
use Exception;
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

    public function update($request, $settingId)
    {
        try {
            $rules = [
                'limit' => 'required|integer',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $settingToUpdate = Setting::find($settingId);

            if(!isset($settingToUpdate)) throw new Exception('Configuração não encontradas');            

            $settingToUpdate->update($validator->validated());

            return ['status' => true, 'data' => $settingToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}
