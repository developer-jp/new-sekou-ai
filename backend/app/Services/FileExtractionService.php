<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;

class FileExtractionService
{
    private const SUPPORTED_TEXT_TYPES = ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt'];
    private const SUPPORTED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Check if the file type is supported
     */
    public function isSupported(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, array_merge(self::SUPPORTED_TEXT_TYPES, self::SUPPORTED_IMAGE_TYPES));
    }

    /**
     * Check if the file is an image
     */
    public function isImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, self::SUPPORTED_IMAGE_TYPES);
    }

    /**
     * Extract content from file
     * Returns ['type' => 'text'|'image', 'content' => string, 'mime_type' => string|null]
     */
    public function extractContent(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($this->isImage($file)) {
            return [
                'type' => 'image',
                'content' => base64_encode(file_get_contents($file->getRealPath())),
                'mime_type' => $file->getMimeType(),
                'filename' => $file->getClientOriginalName(),
            ];
        }

        $text = match ($extension) {
            'pdf' => $this->extractFromPdf($file),
            'docx', 'doc' => $this->extractFromWord($file),
            'xlsx', 'xls' => $this->extractFromExcel($file),
            'pptx', 'ppt' => $this->extractFromPowerPoint($file),
            default => throw new \Exception("Unsupported file type: {$extension}"),
        };

        return [
            'type' => 'text',
            'content' => $text,
            'mime_type' => null,
            'filename' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Extract text from PDF file
     */
    private function extractFromPdf(UploadedFile $file): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file->getRealPath());
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::error('PDF extraction error', ['error' => $e->getMessage()]);
            throw new \Exception('PDFファイルの読み込みに失敗しました');
        }
    }

    /**
     * Extract text from Word document
     */
    private function extractFromWord(UploadedFile $file): string
    {
        try {
            $phpWord = WordIOFactory::load($file->getRealPath());
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractTextFromWordElement($element);
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            Log::error('Word extraction error', ['error' => $e->getMessage()]);
            throw new \Exception('Wordファイルの読み込みに失敗しました');
        }
    }

    /**
     * Recursively extract text from Word elements
     */
    private function extractTextFromWordElement($element): string
    {
        $text = '';

        if (method_exists($element, 'getText')) {
            $text .= $element->getText() . "\n";
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $text .= $this->extractTextFromWordElement($child);
            }
        }

        return $text;
    }

    /**
     * Extract text from Excel spreadsheet
     */
    private function extractFromExcel(UploadedFile $file): string
    {
        try {
            $spreadsheet = SpreadsheetIOFactory::load($file->getRealPath());
            $text = '';

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $text .= "【シート: " . $sheet->getTitle() . "】\n";
                
                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $value = $cell->getValue();
                        if ($value !== null && $value !== '') {
                            $rowData[] = $value;
                        }
                    }
                    if (!empty($rowData)) {
                        $text .= implode("\t", $rowData) . "\n";
                    }
                }
                $text .= "\n";
            }

            return trim($text);
        } catch (\Exception $e) {
            Log::error('Excel extraction error', ['error' => $e->getMessage()]);
            throw new \Exception('Excelファイルの読み込みに失敗しました');
        }
    }

    /**
     * Extract text from PowerPoint presentation
     * Note: Using basic XML parsing as phpoffice/phppresentation is complex
     */
    private function extractFromPowerPoint(UploadedFile $file): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Cannot open PowerPoint file');
            }

            $text = '';
            $slideIndex = 1;

            // PowerPoint slides are in ppt/slides/slide*.xml
            for ($i = 1; $i <= 100; $i++) {
                $xmlContent = $zip->getFromName("ppt/slides/slide{$i}.xml");
                if ($xmlContent === false) break;

                $text .= "【スライド {$slideIndex}】\n";
                
                // Strip XML tags and get text
                $xml = simplexml_load_string($xmlContent);
                if ($xml) {
                    $slideText = strip_tags($xml->asXML());
                    $slideText = preg_replace('/\s+/', ' ', $slideText);
                    $text .= trim($slideText) . "\n\n";
                }
                
                $slideIndex++;
            }

            $zip->close();
            return trim($text);
        } catch (\Exception $e) {
            Log::error('PowerPoint extraction error', ['error' => $e->getMessage()]);
            throw new \Exception('PowerPointファイルの読み込みに失敗しました');
        }
    }
}
