<?php

namespace App\Services\File;

use App\Models\File;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use setasign\Fpdi\Fpdi;
use setasign\Fpdf\Fpdf; // Certifique-se de importar o FPDF também
use PhpOffice\PhpWord\TemplateProcessor;



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
                'positionX' => 'nullable|numeric',
                'positionY' => 'nullable|numeric',
                'path' => 'required|mimes:doc,docx,pdf|max:5120',
            ];
            
            $requestData = $request->all();
            $requestData['user_id'] = Auth::user()->id;
    
            // Validação dos dados
            $validator = Validator::make($requestData, $rules);
    
            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }
    
            if (!$request->hasFile('path')) throw new Exception('Arquivo para assinatura é obrigatório');
    
            $filePath = $request->file('path')->store('files', 'public');
            $requestData['path'] = $filePath;
    
            $extension = $request->file('path')->getClientOriginalExtension();
    
            $signedFilePath = '';
    
            if ($extension === 'pdf') {
                $signedFilePath = $this->addSignatureToPDF(storage_path('app/public/' . $filePath), Auth::user()->name, $request->positionX, $request->positionY);
            } elseif (in_array($extension, ['doc', 'docx'])) {
                $signedFilePath = $this->addSignatureToWord(storage_path('app/public/' . $filePath), Auth::user()->name, $request->positionX, $request->positionY);
            } else {
                throw new Exception('Tipo de arquivo não suportado para assinatura');
            }
    
            $finalPath = 'files_assign/' . basename($signedFilePath);
            Storage::disk('public')->put($finalPath, file_get_contents($signedFilePath));
    
            $requestData['path'] = $finalPath;
    
            $file = File::create($requestData);
        
            return ['status' => true, 'data' => $file];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function addSignatureToPDF($filePath, $name, $positionX, $positionY)
    {
        $pdf = new Fpdi();
    
        $pdf->AddPage();
        
        $pdf->setSourceFile($filePath);
        
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);
        
        $pdf->SetFont('Courier', 'I', 12);
        $pdf->SetXY($positionX, $positionY);
        $pdf->Write(0, $name);
        // Salva o novo PDF
        $newFilePath = 'path/to/signed_document.pdf';
        $pdf->Output($newFilePath, 'F');
    
        return $newFilePath;
    }

    private function addSignatureToWord($filePath, $name, $positionX, $positionY)
    {
        $templateProcessor = new TemplateProcessor($filePath);

        $templateProcessor->setValue('signature', $name);

        $newFilePath = 'path/to/signed_document.docx';
        $templateProcessor->saveAs($newFilePath);

        return $newFilePath;
    }

}
