<?php

namespace App\Http\Controllers;

use Spatie\PdfToText\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ContractReviewController extends Controller
{
    public function index()
    {
        return view('pages.index');
    }

    public function review(Request $request)
    {
        $contractText = $request->input('contractText');
        $contractName = $request->input('contractName');

        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'API key not found in environment variables.'], 401);
        }
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key={$apiKey}";

        $response = Http::post($endpoint, [
            'contents' => [[
                'parts' => [[
                    'text' => "Review this contract in 3 paragraphs, identify key risks, contradictory clauses, and potential issues. should be very brief.\n\nContract Text:\n\n" . $contractText
                ]]
                // 'parts' => [[
                //     'text' => "Review this contract agreement named. '{$contractName}' and highlight key risks, contradictory clauses, and potential issues.\n\nContract Text:\n\n" . $contractText
                // ]]
            ]]
        ]);

        return response()->json($response->json());
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
                $contractText = (new Pdf())->setPdf($filePath)->text();
            } elseif ($extension === 'docx') {
                $phpWord = IOFactory::load($filePath);
                $contractText = $phpWord->getSections()[0]->getText();
            } else {
                return response()->json(['error' => 'Unsupported file format.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not read file contents: ' . $e->getMessage()], 500);
        }

        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'API key not found.'], 401);
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key={$apiKey}";

        $response = Http::post($endpoint, [
            'contents' => [[
                'parts' => [[
                    'text' => "Review this contract agreement from a file and highlight key risks:\n\n" . $contractText
                ]]
            ]]
        ]);

        return response()->json($response->json());
    }

    public function geminiai()
    {

        $result = Gemini::geminiPro()->generateContent('Hello');
    }   

    public function ping(Request $request)
    {
        return response()->json(['message' => 'Pong!']);
    }
}
