<?php
/**
 * Helper Functions
 */

/**
 * Sanitize input
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate unique code
 */
function generateCode(string $prefix = ''): string {
    $timestamp = date('Ymd');
    $random = strtoupper(substr(uniqid(), -4));
    return $prefix . $timestamp . '-' . $random;
}

/**
 * Format date
 */
function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime(?string $datetime, string $format = 'M d, Y h:i A'): string {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Format currency
 */
function formatCurrency(float $amount, string $symbol = '₱'): string {
    return $symbol . number_format($amount, 2);
}

/**
 * Get status badge HTML
 */
function statusBadge(string $status): string {
    $color = STATUS_COLORS[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Get priority badge HTML
 */
function priorityBadge(string $priority): string {
    $color = PRIORITY_COLORS[$priority] ?? 'secondary';
    $label = ucfirst($priority);
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Get role display name
 */
function roleName(string $role): string {
    return ROLES[$role] ?? $role;
}

/**
 * Set flash message (without redirect)
 */
function setFlashMessage(string $message, string $type = 'success'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Redirect with message
 */
function redirect(string $url, string $message = '', string $type = 'success'): void {
    if ($message) {
        setFlashMessage($message, $type);
    }
    header("Location: {$url}");
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate required fields
 */
function validateRequired(array $data, array $required): array {
    $errors = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[$field] = ucwords(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

/**
 * Upload image with optional GPS watermark
 */
function uploadImage(array $file, string $destination, ?float $lat = null, ?float $lng = null): array {
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    // Check size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File too large. Maximum size is ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_IMAGE_TYPES)];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $destination . $filename;
    
    // Create directory if not exists
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    // Add GPS watermark if coordinates provided
    if ($lat !== null && $lng !== null) {
        addGpsWatermark($filepath, $lat, $lng);
    }
    
    return ['success' => true, 'filename' => $filename, 'path' => $filepath];
}

/**
 * Add GPS watermark to image
 */
function addGpsWatermark(string $filepath, float $lat, float $lng): bool {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    
    // Load image based on type
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'png':
            $image = imagecreatefrompng($filepath);
            break;
        case 'webp':
            $image = imagecreatefromwebp($filepath);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    // Get image dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Watermark text
    $timestamp = date('Y-m-d H:i:s');
    $latDir = $lat >= 0 ? 'N' : 'S';
    $lngDir = $lng >= 0 ? 'E' : 'W';
    $text = sprintf(
        "%s | Lat: %.6f%s, Lng: %.6f%s",
        $timestamp,
        abs($lat), $latDir,
        abs($lng), $lngDir
    );
    
    // Create watermark background
    $padding = 10;
    $fontSize = max(12, min(20, $width / 40));
    $font = 5; // Built-in font
    
    $textWidth = strlen($text) * imagefontwidth($font);
    $textHeight = imagefontheight($font);
    
    // Semi-transparent background
    $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 60);
    imagefilledrectangle(
        $image, 
        0, 
        $height - $textHeight - ($padding * 2), 
        $width, 
        $height, 
        $bgColor
    );
    
    // White text
    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagestring(
        $image, 
        $font, 
        $padding, 
        $height - $textHeight - $padding, 
        $text, 
        $textColor
    );
    
    // Save image
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($image, $filepath, 90);
            break;
        case 'png':
            imagepng($image, $filepath);
            break;
        case 'webp':
            imagewebp($image, $filepath, 90);
            break;
    }
    
    imagedestroy($image);
    return true;
}

/**
 * Delete file
 */
function deleteFile(string $filepath): bool {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get pagination HTML
 */
function paginationHtml(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';
    
    $html = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    
    // Previous
    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $prevPage = max(1, $currentPage - 1);
    $html .= "<li class=\"page-item {$prevDisabled}\"><a class=\"page-link\" href=\"{$baseUrl}?page={$prevPage}\">&laquo;</a></li>";
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$baseUrl}?page=1\">1</a></li>";
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? 'active' : '';
        $html .= "<li class=\"page-item {$active}\"><a class=\"page-link\" href=\"{$baseUrl}?page={$i}\">{$i}</a></li>";
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$baseUrl}?page={$totalPages}\">{$totalPages}</a></li>";
    }
    
    // Next
    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $nextPage = min($totalPages, $currentPage + 1);
    $html .= "<li class=\"page-item {$nextDisabled}\"><a class=\"page-link\" href=\"{$baseUrl}?page={$nextPage}\">&raquo;</a></li>";
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Calculate distance between two GPS coordinates (in kilometers)
 */
function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

/**
 * Create notification
 */
function createNotification(int $userId, string $title, string $message, string $type = 'info', string $link = null): void {
    $db = Database::getInstance();
    $db->insert('notifications', [
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'link' => $link
    ]);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount(int $userId): int {
    $db = Database::getInstance();
    return $db->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
}

/**
 * Escape for JavaScript
 */
function jsEscape(string $str): string {
    return addslashes($str);
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 50, string $suffix = '...'): string {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . $suffix;
}

/**
 * Check if string is valid JSON
 */
function isValidJson(string $string): bool {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Get client IP address
 */
function getClientIp(): string {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return 'Unknown';
}