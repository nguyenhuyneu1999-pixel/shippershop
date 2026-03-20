<?php
/**
 * ShipperShop Input Validator
 * Usage: $errors = validate($data, ['email'=>'required|email', 'name'=>'required|min:2|max:100']);
 */

/**
 * Validate data against rules
 * @param array $data input data
 * @param array $rules ['field' => 'rule1|rule2:param']
 * @return array errors ['field' => 'error message'] or empty if valid
 */
function validate($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $ruleStr) {
        $value = isset($data[$field]) ? $data[$field] : null;
        $ruleList = explode('|', $ruleStr);
        
        foreach ($ruleList as $rule) {
            $parts = explode(':', $rule, 2);
            $ruleName = $parts[0];
            $param = isset($parts[1]) ? $parts[1] : null;
            
            $error = _validate_rule($field, $value, $ruleName, $param);
            if ($error) {
                $errors[$field] = $error;
                break; // Stop at first error per field
            }
        }
    }
    
    return $errors;
}

function _validate_rule($field, $value, $rule, $param) {
    $labels = [
        'email' => 'Email',
        'password' => 'Mật khẩu',
        'fullname' => 'Họ tên',
        'content' => 'Nội dung',
        'name' => 'Tên',
        'username' => 'Tên người dùng',
        'bio' => 'Giới thiệu',
        'phone' => 'Số điện thoại',
        'pin' => 'Mã PIN',
    ];
    $label = isset($labels[$field]) ? $labels[$field] : $field;
    
    switch ($rule) {
        case 'required':
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                return $label . ' là bắt buộc';
            }
            break;
            
        case 'email':
            if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'Email không hợp lệ';
            }
            break;
            
        case 'min':
            if ($value && mb_strlen($value) < intval($param)) {
                return $label . ' tối thiểu ' . $param . ' ký tự';
            }
            break;
            
        case 'max':
            if ($value && mb_strlen($value) > intval($param)) {
                return $label . ' tối đa ' . $param . ' ký tự';
            }
            break;
            
        case 'numeric':
            if ($value && !is_numeric($value)) {
                return $label . ' phải là số';
            }
            break;
            
        case 'in':
            $allowed = explode(',', $param);
            if ($value && !in_array($value, $allowed)) {
                return $label . ' không hợp lệ';
            }
            break;
            
        case 'url':
            if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                return 'URL không hợp lệ';
            }
            break;
            
        case 'date':
            if ($value && !strtotime($value)) {
                return 'Ngày không hợp lệ';
            }
            break;
            
        case 'string':
            if ($value && !is_string($value)) {
                return $label . ' phải là chuỗi';
            }
            break;
            
        case 'integer':
            if ($value !== null && $value !== '' && !ctype_digit(strval($value))) {
                return $label . ' phải là số nguyên';
            }
            break;
            
        case 'password':
            // Min 8 chars
            if ($value && mb_strlen($value) < 8) {
                return 'Mật khẩu tối thiểu 8 ký tự';
            }
            break;
            
        case 'unique':
            // param = "table,column"
            if ($value && $param) {
                $tc = explode(',', $param);
                $table = $tc[0];
                $col = isset($tc[1]) ? $tc[1] : $field;
                $existing = db()->fetchOne("SELECT id FROM `$table` WHERE `$col` = ? LIMIT 1", [$value]);
                if ($existing) {
                    return $label . ' đã tồn tại';
                }
            }
            break;
    }
    
    return null; // No error
}

/**
 * Sanitize HTML — prevent XSS
 * @param string $str
 * @return string
 */
function sanitize_html($str) {
    if (!$str) return '';
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate uploaded image file
 * @param array $file $_FILES element
 * @param int $maxMB max size in MB
 * @return array ['valid' => bool, 'error' => string|null, 'mime' => string]
 */
function validate_image($file, $maxMB = 5) {
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload thất bại', 'mime' => null];
    }
    
    if ($file['size'] > $maxMB * 1024 * 1024) {
        return ['valid' => false, 'error' => 'File tối đa ' . $maxMB . 'MB', 'mime' => null];
    }
    
    // Check real MIME type via finfo (NOT trusting $_FILES['type'])
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowed)) {
        return ['valid' => false, 'error' => 'Chỉ chấp nhận JPG, PNG, WebP, GIF', 'mime' => $mime];
    }
    
    return ['valid' => true, 'error' => null, 'mime' => $mime];
}

/**
 * Validate and return errors as JSON 400 response, or return clean data
 * @param array $data
 * @param array $rules
 * @return array clean data (exits on error)
 */
function validate_or_fail($data, $rules) {
    $errors = validate($data, $rules);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => reset($errors), 'errors' => $errors]);
        exit;
    }
    return $data;
}
