<?php

namespace App\Services\File;

use App\Models\File;
use App\Trait\VaultIDTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use setasign\Fpdi\Fpdi;
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
                'positionX'         => 'nullable|numeric',
                'positionY'         => 'nullable|numeric',
                'page'              => 'nullable|integer',
                'file'              => 'required|mimes:doc,docx,pdf|max:5120',
                'access_token'      => 'required|string',
                'certificate_alias' => 'required|string',
            ];

            $auth = Auth::user();

            $userFilesCount = File::where('user_id', $auth->id)
                ->whereDate('created_at', Carbon::now())                
                ->count();

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

            // Processa e armazena o arquivo
            $path = $this->processFileAndStore($request);

            // Adiciona o nome do usuário ao PDF e apaga o arquivo original
            $tempFilePath = storage_path("app/public/{$path}");
            $path = $this->addUserNameToPDF($tempFilePath, $auth->name, $request->positionX, $request->positionY, $request->page);
            unlink($tempFilePath); // Remove o arquivo original

            // Gera a assinatura digital usando a API
            $signatureResponse = $this->generateSignature($request->input('access_token'), $request->input('certificate_alias'), $path);
            if (!isset($signatureResponse['signatures']) && count($signatureResponse['signatures'])) {
                throw new Exception('Erro ao assinar o documento: ' . $signatureResponse['error']);
            }

            $signature = $signatureResponse['signatures'][0]['raw_signature'];

            // Adiciona a assinatura digital e apaga o arquivo sem assinatura
            $signedFilePath = $this->addSignatureToPDF(storage_path("app/public/{$path}"), $signature);
            unlink(storage_path("app/public/{$path}")); // Remove o arquivo sem assinatura

            // Atualiza o caminho final no request data
            $requestData['path'] = $signedFilePath;

            // Salva o registro do arquivo no banco de dados
            $file = File::create($requestData);

            return ['status' => true, 'data' => ['file' => $file, 'path' => $signedFilePath]];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function generateSignature($accessToken, $certificateAlias, $fileContent)
    {
        $hash = hash('sha256', $fileContent);
    
        $hashes = [
            [
                'id' => '1',
                'alias' => 'Documento Assinado pelo CEC',
                'hash' => $hash,
                'hash_algorithm' => '2.16.840.1.101.3.4.2.1',
                'signature_format' => 'RAW'
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

    private function addUserNameToPDF($filePath, $name, $positionXPercent, $positionYPercent, $page)
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);

        $pdf->AddFont('Handwritten', '', 'resources/fonts/minhaFonte.ttf', true);
    
        for ($i = 1; $i <= $pageCount; $i++) {
            $pdfWidth = $pdf->getTemplateSize($pdf->importPage(1))['width'];
            $pdfHeight = $pdf->getTemplateSize($pdf->importPage(1))['height'];
        
            $positionX = ($positionXPercent / 100) * $pdfWidth - 2;
            $positionY = (($positionYPercent / 100) * $pdfHeight) + 4;

            $pdf->AddPage();
            $tplIdx = $pdf->importPage($i);
            $pdf->useTemplate($tplIdx);
    
            if ($i == $page) {
                $pdf->SetFont('Handwritten', '', 12);
                $pdf->SetTextColor(50, 50, 50);
                $pdf->SetXY($positionX, $positionY);
                $pdf->Write(0, $name);
            }
        }
    
        $tempFilePath = str_replace('.pdf', '_temp.pdf', $filePath);
        $pdf->Output($tempFilePath, 'F');
        return $tempFilePath;
    }

    private function addSignatureToPDF($filePath, $signature = '')
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);
    
        $pdf->AddPage();
        $pdf->SetFont('Courier', '', 10);
        $pdf->SetXY(10, 10);
        $pdf->MultiCell(0, 10, base64_decode($signature));
    
        for ($i = 1; $i <= $pageCount; $i++) {
            $pdf->AddPage();
            $tplIdx = $pdf->importPage($i);
            $pdf->useTemplate($tplIdx);
        }
    
        $newFilePath = storage_path('app/public/files_assign/' . basename($filePath));
        $pdf->Output($newFilePath, 'F');
        return $newFilePath;
    }
    
    
}