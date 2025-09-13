<?php

namespace App\Http\Controllers;

use Spatie\PdfToText\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

class ContractReviewController extends Controller
{
    public function index()
    {
        return view('pages.index');
    }

    public function reviewFile(Request $request)
    {
        $request->validate([
            'contractFile' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB
        ]);

        $file = $request->file('contractFile');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();
        $contractText = '';

        try {
            if ($extension === 'pdf') {
                $contractText = $this->extractPdfText($filePath);
            } elseif ($extension === 'docx') {
                $contractText = $this->extractDocxText($filePath);
            } elseif ($extension === 'doc') {
                // For .doc files, try to load with PhpWord (limited support)
                $contractText = $this->extractDocText($filePath);
            } else {
                return response()->json(['error' => 'Unsupported file format.'], 400);
            }

            // Check if text extraction was successful
            if (empty(trim($contractText))) {
                return response()->json(['error' => 'Could not extract text from the file or the file appears to be empty.'], 400);
            }

        } catch (\Exception $e) {
            \Log::error('File processing error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Could not read file contents: ' . $e->getMessage(),
                'file_info' => [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType()
                ]
            ], 500);
        }

        return $this->analyzeContract($contractText, $file->getClientOriginalName());
    }

    private function extractPdfText($filePath)
    {
        try {
            // Initialize the PDF parser
            $parser = new Parser();
            
            // Parse the PDF file
            $pdf = $parser->parseFile($filePath);
            
            // Extract text from all pages
            $text = $pdf->getText();
            
            // Clean up the extracted text
            $text = $this->cleanExtractedText($text);
            
            if (empty(trim($text))) {
                throw new \Exception('PDF file appears to contain no readable text. It might be an image-based PDF.');
            }
            
            return $text;
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from PDF: ' . $e->getMessage());
        }
    }

    private function extractDocxText($filePath)
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';
            
            // Extract text from all sections
            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                foreach ($elements as $element) {
                    $text .= $this->extractElementText($element) . "\n";
                }
            }
            
            return $this->cleanExtractedText($text);
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from DOCX file: ' . $e->getMessage());
        }
    }

    private function extractDocText($filePath)
    {
        try {
            // Try to load .doc file with PhpWord (limited support)
            $phpWord = IOFactory::load($filePath);
            return $this->extractDocxText($filePath);
        } catch (\Exception $e) {
            throw new \Exception('DOC file format has limited support. Please convert to DOCX or PDF format for better results.');
        }
    }

    private function extractElementText($element)
    {
        $text = '';
        
        // Handle different element types
        if (method_exists($element, 'getText')) {
            $text .= $element->getText();
        } elseif (method_exists($element, 'getElements')) {
            // Handle nested elements (like tables, lists, etc.)
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractElementText($childElement);
            }
        } elseif (method_exists($element, 'getRows')) {
            // Handle table elements
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->extractElementText($cellElement) . ' ';
                    }
                }
                $text .= "\n";
            }
        }
        
        return $text;
    }

    private function cleanExtractedText($text)
    {
        // Remove excessive whitespace and normalize line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        $text = trim($text);
        
        return $text;
    }

    private function analyzeContract($contractText, $fileName = null)
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'API key not found.'], 401);
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key={$apiKey}";

        // Prepare analysis prompt
        $prompt = "Please analyze this contract document and provide:\n\n";
        $prompt .= "1. **Key Contract Details**: Type, parties involved, duration\n";
        $prompt .= "2. **Important Clauses**: Payment terms, termination conditions, liability\n";
        $prompt .= "3. **Risk Assessment**: Potential risks and red flags\n";
        $prompt .= "4. **Recommendations**: Suggested changes or areas for negotiation\n";
        $prompt .= "5. **Summary**: Overall assessment of the contract\n\n";
        $prompt .= "6. **My thought?**: add a savage response on the last paragraph\n\n";
        $prompt .= "Contract Text:\n" . $contractText;

        try {
            $response = Http::timeout(60)->post($endpoint, [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt
                    ]]
                ]],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ]
            ]);

            if ($response->successful()) {
                $analysisData = $response->json();
                
                return response()->json([
                    'success' => true,
                    'file_name' => $fileName,
                    'text_length' => strlen($contractText),
                    'word_count' => str_word_count($contractText),
                    'analysis' => $analysisData,
                    'processed_at' => now()->toISOString()
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to analyze contract with AI service',
                    'details' => $response->json()
                ], $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('Contract analysis API error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to analyze contract: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reviewText(Request $request)
    {
        $request->validate([
            'contractText' => 'required|string|min:50',
            'contractName' => 'nullable|string|max:255'
        ]);

        $contractText = $request->input('contractText');
        $contractName = $request->input('contractName', 'Pasted Contract');

        return $this->analyzeContract($contractText, $contractName);
    }

    public function getFileInfo(Request $request)
    {
        $request->validate([
            'contractFile' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $file = $request->file('contractFile');
        
        try {
            $info = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'size_formatted' => $this->formatBytes($file->getSize()),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'can_process' => true
            ];

            // Test if we can extract text (without full processing)
            if ($file->getClientOriginalExtension() === 'pdf') {
                try {
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file->getRealPath());
                    $text = $pdf->getText();
                    $info['has_readable_text'] = !empty(trim($text));
                    $info['estimated_word_count'] = str_word_count($text);
                } catch (\Exception $e) {
                    $info['can_process'] = false;
                    $info['error'] = 'Cannot extract text from this PDF';
                }
            }

            return response()->json($info);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not process file info: ' . $e->getMessage()
            ], 400);
        }
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $base = log($size, 1024);
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }

    public function ping()
    {
        return response('Pong', 200);
    }
}
