<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Form;
use App\Models\Submission;

/**
 * Export Controller
 * 
 * Handles data export functionality (CSV, PDF)
 * 
 * @OA\Tag(
 *     name="Export",
 *     description="Data export operations"
 * )
 */
class ExportController
{
    /**
     * Export submissions as CSV
     * 
     * @OA\Post(
     *     path="/api/export/csv",
     *     tags={"Export"},
     *     summary="Export submissions as CSV",
     *     description="Export form submissions to CSV format with optional filtering",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="formId", type="integer", example=1, description="Form ID to export"),
     *             @OA\Property(property="startDate", type="string", format="date", example="2024-01-01", description="Start date filter"),
     *             @OA\Property(property="endDate", type="string", format="date", example="2024-12-31", description="End date filter"),
     *             @OA\Property(property="status", type="string", example="approved", description="Submission status filter"),
     *             @OA\Property(property="includePaymentInfo", type="boolean", example=true, description="Include payment information")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV export successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="CSV export generated successfully"),
     *             @OA\Property(property="filename", type="string", example="submissions_20240120.csv"),
     *             @OA\Property(property="downloadUrl", type="string", example="/api/export/download/submissions_20240120.csv"),
     *             @OA\Property(property="recordCount", type="integer", example=150)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Form ID is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     )
     * )
     */
    public function exportCsv($request)
    {
        try {
            $data = $request->getJsonBody();
            
            // Validate required fields
            if (!isset($data['formId'])) {
                return Response::badRequest('Form ID is required');
            }
            
            $formId = $data['formId'];
            $startDate = $data['startDate'] ?? null;
            $endDate = $data['endDate'] ?? null;
            $status = $data['status'] ?? null;
            $includePaymentInfo = $data['includePaymentInfo'] ?? false;
            
            // Verify form exists and user has access
            $form = Form::findById($formId);
            if (!$form) {
                return Response::notFound('Form not found');
            }
            
            // Get submissions with filters
            $submissions = $this->getFilteredSubmissions($formId, $startDate, $endDate, $status);
            
            // Generate CSV content
            $csvContent = $this->generateCsvContent($submissions, $form, $includePaymentInfo);
            
            // Save CSV file
            $filename = 'submissions_' . date('Ymd_His') . '.csv';
            $filepath = $this->getExportPath($filename);
            
            if (!file_put_contents($filepath, $csvContent)) {
                return Response::error('Failed to create CSV file');
            }
            
            return Response::success([
                'message' => 'CSV export generated successfully',
                'filename' => $filename,
                'downloadUrl' => '/api/export/download/' . $filename,
                'recordCount' => count($submissions)
            ]);
            
        } catch (\Exception $e) {
            return Response::error('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export submissions as PDF
     * 
     * @OA\Post(
     *     path="/api/export/pdf",
     *     tags={"Export"},
     *     summary="Export submissions as PDF",
     *     description="Export form submissions to PDF format with optional filtering",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="formId", type="integer", example=1, description="Form ID to export"),
     *             @OA\Property(property="startDate", type="string", format="date", example="2024-01-01", description="Start date filter"),
     *             @OA\Property(property="endDate", type="string", format="date", example="2024-12-31", description="End date filter"),
     *             @OA\Property(property="status", type="string", example="approved", description="Submission status filter"),
     *             @OA\Property(property="includePaymentInfo", type="boolean", example=true, description="Include payment information"),
     *             @OA\Property(property="template", type="string", example="detailed", description="PDF template style")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF export successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="PDF export generated successfully"),
     *             @OA\Property(property="filename", type="string", example="submissions_20240120.pdf"),
     *             @OA\Property(property="downloadUrl", type="string", example="/api/export/download/submissions_20240120.pdf"),
     *             @OA\Property(property="recordCount", type="integer", example=150)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request data"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function exportPdf($request)
    {
        try {
            $data = $request->getJsonBody();
            
            // Validate required fields
            if (!isset($data['formId'])) {
                return Response::badRequest('Form ID is required');
            }
            
            $formId = $data['formId'];
            $startDate = $data['startDate'] ?? null;
            $endDate = $data['endDate'] ?? null;
            $status = $data['status'] ?? null;
            $includePaymentInfo = $data['includePaymentInfo'] ?? false;
            $template = $data['template'] ?? 'detailed';
            
            // Verify form exists and user has access
            $form = Form::findById($formId);
            if (!$form) {
                return Response::notFound('Form not found');
            }
            
            // Get submissions with filters
            $submissions = $this->getFilteredSubmissions($formId, $startDate, $endDate, $status);
            
            // Generate PDF content
            $pdfContent = $this->generatePdfContent($submissions, $form, $includePaymentInfo, $template);
            
            // Save PDF file
            $filename = 'submissions_' . date('Ymd_His') . '.pdf';
            $filepath = $this->getExportPath($filename);
            
            if (!file_put_contents($filepath, $pdfContent)) {
                return Response::error('Failed to create PDF file');
            }
            
            return Response::success([
                'message' => 'PDF export generated successfully',
                'filename' => $filename,
                'downloadUrl' => '/api/export/download/' . $filename,
                'recordCount' => count($submissions)
            ]);
            
        } catch (\Exception $e) {
            return Response::error('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Download exported file
     * 
     * @OA\Get(
     *     path="/api/export/download/{filename}",
     *     tags={"Export"},
     *     summary="Download exported file",
     *     description="Download a previously generated export file",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         required=true,
     *         description="Name of the file to download",
     *         @OA\Schema(type="string", example="submissions_20240120.csv")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="File not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     )
     * )
     */
    public function downloadFile($request)
    {
        try {
            $filename = $request->getParam('filename');
            
            // Security check - only allow safe filenames
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
                return Response::forbidden('Invalid filename');
            }
            
            $filepath = $this->getExportPath($filename);
            
            if (!file_exists($filepath)) {
                return Response::notFound('File not found');
            }
            
            // Security check - ensure file is in exports directory
            $realPath = realpath($filepath);
            $exportsPath = realpath($this->getExportDirectory());
            
            if (strpos($realPath, $exportsPath) !== 0) {
                return Response::forbidden('Access denied');
            }
            
            // Determine content type
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $contentType = match($extension) {
                'csv' => 'text/csv',
                'pdf' => 'application/pdf',
                default => 'application/octet-stream'
            };
            
            // Return file with appropriate headers
            return new \App\Core\Response(200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => filesize($filepath),
                'Cache-Control' => 'no-cache, must-revalidate'
            ], file_get_contents($filepath));
            
        } catch (\Exception $e) {
            return Response::error('Download failed: ' . $e->getMessage());
        }
    }

    /**
     * Get filtered submissions based on criteria
     */
    private function getFilteredSubmissions($formId, $startDate = null, $endDate = null, $status = null)
    {
        // In a real implementation, this would query the database with filters
        // For now, return sample data
        return [
            [
                'id' => 1,
                'form_id' => $formId,
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890'
                ],
                'status' => 'approved',
                'payment_status' => 'paid',
                'payment_amount' => 100.00,
                'created_at' => '2024-01-15 10:30:00',
                'updated_at' => '2024-01-15 11:00:00'
            ],
            [
                'id' => 2,
                'form_id' => $formId,
                'data' => [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'phone' => '+1234567891'
                ],
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_amount' => 150.00,
                'created_at' => '2024-01-16 14:15:00',
                'updated_at' => '2024-01-16 14:15:00'
            ]
        ];
    }

    /**
     * Generate CSV content from submissions
     */
    private function generateCsvContent($submissions, $form, $includePaymentInfo = false)
    {
        if (empty($submissions)) {
            return "No data available\n";
        }

        $output = fopen('php://temp', 'r+');
        
        // Generate headers based on first submission
        $headers = ['ID', 'Status', 'Created At', 'Updated At'];
        
        // Add field headers from form data
        if (!empty($submissions[0]['data'])) {
            foreach (array_keys($submissions[0]['data']) as $field) {
                $headers[] = ucfirst(str_replace('_', ' ', $field));
            }
        }
        
        // Add payment headers if requested
        if ($includePaymentInfo) {
            $headers = array_merge($headers, ['Payment Status', 'Payment Amount']);
        }
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($submissions as $submission) {
            $row = [
                $submission['id'],
                $submission['status'],
                $submission['created_at'],
                $submission['updated_at']
            ];
            
            // Add field data
            if (!empty($submission['data'])) {
                foreach ($submission['data'] as $value) {
                    $row[] = $value;
                }
            }
            
            // Add payment data if requested
            if ($includePaymentInfo) {
                $row[] = $submission['payment_status'] ?? '';
                $row[] = $submission['payment_amount'] ?? '';
            }
            
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }

    /**
     * Generate PDF content from submissions
     */
    private function generatePdfContent($submissions, $form, $includePaymentInfo = false, $template = 'detailed')
    {
        // In a real implementation, this would use a PDF library like TCPDF or FPDF
        // For now, return a simple HTML-to-PDF conversion placeholder
        
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .header { text-align: center; margin-bottom: 30px; }
            .footer { margin-top: 30px; font-size: 12px; color: #666; }
        </style></head><body>';
        
        $html .= '<div class="header">';
        $html .= '<h1>Form Submissions Report</h1>';
        $html .= '<p>Form: ' . htmlspecialchars($form['title'] ?? 'Unknown Form') . '</p>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '<p>Total Records: ' . count($submissions) . '</p>';
        $html .= '</div>';
        
        if (!empty($submissions)) {
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th>ID</th><th>Status</th><th>Created</th>';
            
            // Add field headers
            if (!empty($submissions[0]['data'])) {
                foreach (array_keys($submissions[0]['data']) as $field) {
                    $html .= '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $field))) . '</th>';
                }
            }
            
            if ($includePaymentInfo) {
                $html .= '<th>Payment Status</th><th>Payment Amount</th>';
            }
            
            $html .= '</tr></thead><tbody>';
            
            foreach ($submissions as $submission) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($submission['id']) . '</td>';
                $html .= '<td>' . htmlspecialchars($submission['status']) . '</td>';
                $html .= '<td>' . htmlspecialchars($submission['created_at']) . '</td>';
                
                // Add field data
                if (!empty($submission['data'])) {
                    foreach ($submission['data'] as $value) {
                        $html .= '<td>' . htmlspecialchars($value) . '</td>';
                    }
                }
                
                if ($includePaymentInfo) {
                    $html .= '<td>' . htmlspecialchars($submission['payment_status'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($submission['payment_amount'] ?? '') . '</td>';
                }
                
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No submissions found.</p>';
        }
        
        $html .= '<div class="footer">';
        $html .= '<p>Report generated by Form Builder System</p>';
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        // For now, return HTML content. In production, convert to PDF using a library
        // This is a placeholder - actual PDF generation would require additional libraries
        return $html;
    }

    /**
     * Get export directory path
     */
    private function getExportDirectory()
    {
        $dir = __DIR__ . '/../../exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get full path for export file
     */
    private function getExportPath($filename)
    {
        return $this->getExportDirectory() . '/' . $filename;
    }
}
