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
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Str;


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
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $files;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            Log::info('Pra testar o log');
            $rules = [
                'positionX'         => 'nullable|numeric',
                'positionY'         => 'nullable|numeric',
                'page'              => 'nullable|integer',
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:51200',
                'access_token'      => 'required|string',
                'certificate_alias' => 'required|string',
            ];

            $auth = Auth::user();

            $userFilesCount = File::where('user_id', $auth->id)
                ->whereMonth('created_at', Carbon::now())
                ->count();

            if ($userFilesCount >= $auth->file_limit) {
                throw new Exception('Você chegou ao seu limite mensal de assinaturas');
            }

            if (!$request->hasFile('file')) {
                Log::error('Arquivo não encontrado na requisição.');
                throw new Exception('Arquivo para assinatura é obrigatório');
            }

            $requestData = $request->all();
            $requestData['user_id'] = $auth->id;

            $uuid = (string) Str::uuid();

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }
            
            $requestData['filename'] = $request->file('file')->getClientOriginalName();

            $path = $this->processFileAndStore($request);
            $tempFilePath = storage_path("app/public/{$path}");

            $tempFilePath = $this->convertPdfToCompatibleFormat($tempFilePath);

            $path = $this->addUserNameToPDF($tempFilePath, $auth->name, $request->positionX, $request->positionY, $request->page);
            unlink($tempFilePath);

            $signatureResponse = $this->generateSignature($request->input('access_token'), $request->input('certificate_alias'), $path, $uuid);
            if (!isset($signatureResponse['signatures']) && count($signatureResponse['signatures'])) {
                throw new Exception('Erro ao assinar o documento: ' . $signatureResponse['error']);
            }

            $signature = $signatureResponse['signatures'][0]['raw_signature'];
            $assignId = $signatureResponse['signatures'][0]['id'];

            $signatureData = [
                'docName' => $requestData['filename'],
                'uuid' => $uuid,
                'raw_signature' => $signature,
                'name' => $auth->name,
                'cpf_cnpj' => $this->formatCpfCnpj($auth->cpf_cnpj),
                'date' => Carbon::now()->format('d M Y \à\s H:i:s'),
            ];
            
            $signedFilePath = $this->addSignatureToPDF($path, $signatureData);
            unlink($path);

            $requestData['path'] = $signedFilePath;
            $requestData['signature'] = $signature;
            $requestData['uuid'] = $uuid;
            $requestData['assign_id'] = $assignId;            

            $file = File::create($requestData);

            return ['status' => true, 'data' => ['file' => $file, 'path' => $file->path]];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function convertPdfToCompatibleFormat($filePath)
    {
        $outputPath = $filePath;
    
        $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o " . escapeshellarg($outputPath) . " " . escapeshellarg($filePath);
        exec($command, $output, $returnVar);
    
        if ($returnVar !== 0) {
            throw new Exception("Erro ao converter o PDF para formato compatível: " . implode("\n", $output));
        }
    
        return $outputPath;
    }
    

    public function generateSignature($accessToken, $certificateAlias, $filePath, $uuid)
    {
        $hash = hash_file('sha256', $filePath);
    
        $hashes = [
            [
                'id' => $uuid,
                'alias' => 'Documento Assinado pelo CEC',
                'hash' => $hash,
                'hash_algorithm' => '2.16.840.1.101.3.4.2.1',
                'signature_format' => 'CMS'
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
        define('FPDF_FONTPATH', resource_path('fonts/'));

        if (!file_exists($filePath)) {
            throw new Exception("Arquivo PDF não encontrado: {$filePath}");
        }

        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile($filePath);

        $pdf->AddFont('Handwritten', '', 'handwriting.php'); // Sem o caminho completo
    
        for ($i = 1; $i <= $pageCount; $i++) {
            $pdfWidth = $pdf->getTemplateSize($pdf->importPage(1))['width'];
            $pdfHeight = $pdf->getTemplateSize($pdf->importPage(1))['height'];
        
            $positionX = ($positionXPercent / 100) * $pdfWidth - 2;
            $positionY = (($positionYPercent / 100) * $pdfHeight) + 4;

            $pdf->AddPage();
            $tplIdx = $pdf->importPage($i);
            $pdf->useTemplate($tplIdx);
    
            if ($i == $page) {
                $pdf->SetFont('Handwritten', '', 24);
                $pdf->SetTextColor(50, 50, 50);
                $pdf->SetXY($positionX, $positionY);
                $pdf->Write(0, $name);
            }
        }
    
        $tempFilePath = str_replace('.pdf', '_temp.pdf', $filePath);
        $pdf->Output($tempFilePath, 'F');
        unset($GLOBALS['FPDF_FONTPATH']);

        return $tempFilePath;
    }

    private function addSignatureToPDF($filePath, $signature)
    {
        if (!file_exists($filePath)) {
            throw new Exception("Arquivo PDF não encontrado: {$filePath}");
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $pdf->AddPage();
            $tplIdx = $pdf->importPage($i);
            $pdf->useTemplate($tplIdx);
        }

        $this->addSignaturePage($pdf, $signature);

        $uniqueFileName = Str::random(40) . '.pdf';
        $newFilePath = storage_path('app/public/files_assign/' . basename($uniqueFileName));

        $directory = dirname($newFilePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->Output($newFilePath, 'F');
        return $uniqueFileName;
    }

    private function addSignaturePage($pdf, $signature)
    {
        $pdf->AddPage();

        // Posiciona o logo
        $pdf->Image(resource_path('images/cec-logo.png'), 10, 10, 40);

        // Configura a fonte personalizada
        $pdf->AddFont('arial', '', 'arial.php');
        $pdf->AddFont('arial', 'B', 'arialb.php');

        // Nome do documento com `MultiCell` para quebrar o texto longo
        $pdf->SetXY(55, 10); // Ajuste a posição para o lado direito da imagem
        $pdf->SetFont('arial', 'B', 14);
        $pdf->MultiCell(0, 8, mb_convert_encoding($signature['docName'], 'ISO-8859-1', 'UTF-8'), 0, 'C');

        // UUID do documento
        $pdf->SetFont('arial', '', 10);
        $pdf->Cell(0, 8, "CECID da assinatura: {$signature['uuid']}", 0, 1, 'C');

        $pdf->Ln(10); // Espaço

        // Seção de assinatura
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(0, 10, 'Assinatura:', 0, 1);

        $pdf->SetFont('arial', '', 10);
        
        $cpfCnpj = $signature['cpf_cnpj'];
        $label = strlen($cpfCnpj) === 14 ? ' - CPF: ' : ' - CNPJ: ';

        $pdf->Cell(0, 6, mb_convert_encoding($signature['name'] . $label . $cpfCnpj, 'ISO-8859-1', 'UTF-8'), 0, 1);

        $pdf->Cell(0, 6, mb_convert_encoding('Assinou em ' . $signature['date'], 'ISO-8859-1', 'UTF-8'), 0, 1);

        $pdf->Ln(10); // Espaço adicional

        // Seção de assinatura digital
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(0, 10, 'Assinatura digital:', 0, 1);

        $pdf->SetFont('arial', '', 10);
        $pdf->MultiCell(0, 5, wordwrap($signature['raw_signature'], 80, "\n", true), 0, 'L');
    }
    

    private function formatCpfCnpj($value) {
        // Remove qualquer caractere não numérico
        $value = preg_replace("/\D/", '', $value);
    
        if (strlen($value) === 11) {
            // Formata como CPF: 000.000.000-00
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $value);
        } elseif (strlen($value) === 14) {
            // Formata como CNPJ: 00.000.000/0000-00
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $value);
        }
    
        // Retorna o valor sem formatação se não for CPF nem CNPJ
        return $value;
    }

}