<?php
// app/Http/Controllers/MdFileController.php

namespace App\Http\Controllers\Api;

use App\Models\MdFile;
use Illuminate\Http\JsonResponse;

class MdFileController extends BaseController
{
    /**
     * جلب كل الملفات
     */
    public function index(): JsonResponse
    {
        $mdFile = MdFile::first();

        if (!$mdFile) {
            return response()->json(['message' => 'No data found'], 404);
        }

        return response()->json([
            'experiment' => $mdFile->experiment_name,
            'description' => $mdFile->description,
            'total_files' => count($mdFile->getAllFiles()),
            'files' => $mdFile->getAllFiles(),
            'by_type' => [
                'pdb' => $mdFile->getFilesByExtension('pdb'),
                'dcd' => $mdFile->getFilesByExtension('dcd'),
                'crd' => $mdFile->getFilesByExtension('crd'),
                'prmtop' => $mdFile->getFilesByExtension('prmtop'),
                'rst' => $mdFile->getFilesByExtension('rst'),
                'csv' => $mdFile->getFilesByExtension('csv'),
                'png' => $mdFile->getFilesByExtension('png'),
                'html' => $mdFile->getFilesByExtension('html'),
                'dat' => $mdFile->getFilesByExtension('dat'),
            ]
        ]);
    }

    /**
     * جلب ملف معين
     */
    public function show(string $filename): JsonResponse
    {
        $mdFile = MdFile::first();

        if (!$mdFile) {
            return response()->json(['message' => 'No data found'], 404);
        }

        // فك الـ URL encoding للأسماء اللي فيها أقواس
        $decodedName = urldecode($filename);

        if (!$mdFile->hasFile($decodedName)) {
            return response()->json([
                'message' => 'File not found',
                'requested' => $decodedName,
                'available' => array_keys($mdFile->getAllFiles())
            ], 404);
        }

        return response()->json([
            'filename' => $decodedName,
            'url' => $mdFile->getFileUrl($decodedName),
            'extension' => pathinfo($decodedName, PATHINFO_EXTENSION)
        ]);
    }

    /**
     * جلب ملفات حسب النوع
     */
    public function byType(string $type): JsonResponse
    {
        $mdFile = MdFile::first();

        if (!$mdFile) {
            return response()->json(['message' => 'No data found'], 404);
        }

        $files = $mdFile->getFilesByExtension($type);

        return response()->json([
            'type' => $type,
            'count' => count($files),
            'files' => $files
        ]);
    }
}
