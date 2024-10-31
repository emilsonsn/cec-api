<?php

namespace App\Services\File;

use App\Models\File;
use App\Models\User;
use App\Trait\VaultIDTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use setasign\Fpdi\Fpdi;
use PhpOffice\PhpWord\TemplateProcessor;
use Dompdf\Dompdf;
use PhpOffice\PhpWord\IOFactory;

class FileService
{

    use VaultIDTrait;

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
                'page' => 'nullable|integer',
                'file' => 'required|mimes:doc,docx,pdf|max:5120',
                'access_token' => 'required|string',
                'certificate_alias' => 'required|string',
            ];
    
            $auth = Auth::user();
    
            $userFilesCount = User::where('user_id', $auth->id)
                ->whereDate('created_at', Carbon::now())
                ->files->count();
    
            if ($userFilesCount >= $auth->file_limit) {
                throw new Exception('Você chegou ao seu limite mensal de assinaturas');
            }
    
            $requestData = $request->all();
            $requestData['user_id'] = $auth->id;
    
            $validator = Validator::make($requestData, $rules);
    
            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }
    
            if (!$request->hasFile('file')) {
                throw new Exception('Arquivo para assinatura é obrigatório');
            }
            
            $requestData['filename'] = $request->file('file')->getClientOriginalName();

            $path = $this->processFileAndStore($request);

            $signatureResponse = $this->generateSignature($request->input('access_token'), $request->input('certificate_alias'), $path);
            if (!isset($signatureResponse['signatures']) && count($signatureResponse['signatures'])) {
                throw new Exception('Erro ao assinar o documento: ' . $signatureResponse['error']);
            }

            $signature = $signatureResponse['signatures'][0]['raw_signature'];

            $signedFilePath = $this->addSignatureToPDF(storage_path("app/public/{$path}"), Auth::user()->name, $request->positionX, $request->positionY, $signature);

            $requestData['path'] = $signedFilePath;

            $file = File::create($requestData);
    
            return ['status' => true, 'data' => $file];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
    
    public function generateSignature($accessToken, $certificateAlias, $filePath)
    {
        $fileContent = file_get_contents(storage_path("app/public/{$filePath}"));
        $hash = hash('sha256', $fileContent);

        $hashes = [
            [
                'id' => '1',
                'alias' => 'Documento Assinado',
                'hash' => $hash,
                'algorithm' => 'SHA-256',
                'signature_format' => 'PKCS7'
            ]
        ];

        return $this->signDocument($accessToken, $certificateAlias, $hashes);
    }

    private function processFileAndStore($request)
    {
        $filePath = $request->file('file')->store('files', 'public');
        $extension = $request->file('file')->getClientOriginalExtension();
    
        if (in_array($extension, ['doc', 'docx'])) {
            $filePath = $this->convertDocxToPdf(storage_path("app/public/{$filePath}"));
        }
    
        return 'files/' . basename($filePath);
    }
    
    private function convertDocxToPdf($filePath)
    {
        $phpWord = IOFactory::load($filePath);

        $htmlFilePath = str_replace('.docx', '.html', $filePath);
        $pdfWriter = IOFactory::createWriter($phpWord, 'HTML');
        $pdfWriter->save($htmlFilePath);

        $dompdf = new Dompdf();
        $dompdf->loadHtml(file_get_contents($htmlFilePath));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfFilePath = str_replace('.docx', '.pdf', $filePath);
        file_put_contents($pdfFilePath, $dompdf->output());

        return $pdfFilePath;
    }

    private function addSignatureToPDF($filePath, $name, $positionX, $positionY, $signature = '')
    {
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->setSourceFile($filePath);
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        $pdf->SetFont('Courier', 'I', 12);
        $pdf->SetXY($positionX, $positionY);
        $pdf->Write(0, $name);

        $pdf->AddPage();
        $pdf->SetFont('Courier', '', 10);
        $pdf->MultiCell(0, 10, "Assinatura Digital: " . base64_decode($signature));

        $newFilePath = storage_path('app/public/files_assign/' . basename($filePath));
        $pdf->Output($newFilePath, 'F');
        return $newFilePath;
    }
}
