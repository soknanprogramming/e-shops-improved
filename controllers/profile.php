<?php
/**
 * controllers/profile.php
 * Handles POST from views/profile.php
 * Place at: controllers/profile.php
 */
session_start();

// ── 1. Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

// ── 2. Only accept POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/profile.php");
    exit();
}

// ── 3. CSRF check ──────────────────────────────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header("Location: ../views/profile.php?error=" . urlencode('Invalid request. Please try again.'));
    exit();
}
// Rotate token after use
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── 4. Dependencies ────────────────────────────────────────────────────────────
require_once '../configs/connect.php';
require_once '../repos/UserRepository.php';
require_once '../repos/ProfileRepository.php';

$userRepo    = new UserRepository($conn);
$profileRepo = new ProfileRepository($conn);
$userId      = (int) $_SESSION['user_id'];

// ── 5. Validate text fields ────────────────────────────────────────────────────
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name']  ?? '');
$bio       = trim($_POST['bio']        ?? '');
$phone1    = trim($_POST['phone1']     ?? '');
$phone2    = trim($_POST['phone2']     ?? '');

if ($firstName === '' || $lastName === '') {
    header("Location: ../views/profile.php?error=" . urlencode('First and last name are required.'));
    exit();
}
if (strlen($firstName) > 50 || strlen($lastName) > 50) {
    header("Location: ../views/profile.php?error=" . urlencode('Name must not exceed 50 characters.'));
    exit();
}
if (strlen($bio) > 200) {
    header("Location: ../views/profile.php?error=" . urlencode('Bio must not exceed 200 characters.'));
    exit();
}
if ($phone1 === '') {
    header("Location: ../views/profile.php?error=" . urlencode('Phone number 1 is required.'));
    exit();
}

// Phone: digits, spaces, +, -, () only — 7 to 20 chars
$phonePattern = '/^[0-9\s\+\-\(\)]{7,20}$/';
if (!preg_match($phonePattern, $phone1)) {
    header("Location: ../views/profile.php?error=" . urlencode('Phone 1 is not a valid phone number.'));
    exit();
}
if ($phone2 !== '' && !preg_match($phonePattern, $phone2)) {
    header("Location: ../views/profile.php?error=" . urlencode('Phone 2 is not a valid phone number.'));
    exit();
}

// ── 6. Handle file uploads ─────────────────────────────────────────────────────
$uploadDir   = __DIR__ . '/../uploads/profiles/';
$allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxBytes    = 2 * 1024 * 1024; // 2 MB

/**
 * Validates and moves an uploaded file.
 * Returns the new filename on success, null if no file, or throws on error.
 */
function handleUpload(string $field, string $uploadDir, array $allowedMime, int $maxBytes): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no new file — keep existing
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload error on {$field}: code {$file['error']}");
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException("Image must be under 2 MB.");
    }

    // Check MIME from actual file content (not trusting browser header)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMime, true)) {
        throw new RuntimeException("Only JPEG, PNG, WEBP, and GIF images are allowed.");
    }

    // Safe extension from MIME
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $ext      = $extMap[$mimeType];
    $newName  = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException("Failed to save uploaded file.");
    }

    return $newName;
}

$newUserImage = null;
$newBgImage   = null;

try {
    $newUserImage = handleUpload('user_image',       $uploadDir, $allowedMime, $maxBytes);
    $newBgImage   = handleUpload('background_image', $uploadDir, $allowedMime, $maxBytes);
} catch (RuntimeException $e) {
    header("Location: ../views/profile.php?error=" . urlencode($e->getMessage()));
    exit();
}

// ── 7. Delete old image file if replaced ──────────────────────────────────────
$existing = $profileRepo->getByUserId($userId);
if ($existing) {
    if ($newUserImage !== null && !empty($existing['user_image'])) {
        $oldPath = $uploadDir . $existing['user_image'];
        if (file_exists($oldPath)) @unlink($oldPath);
    }
    if ($newBgImage !== null && !empty($existing['background_image'])) {
        $oldPath = $uploadDir . $existing['background_image'];
        if (file_exists($oldPath)) @unlink($oldPath);
    }
}

// ── 8. Save profile ────────────────────────────────────────────────────────────
$profileRepo->save($userId, [
    'phone1'           => $phone1,
    'phone2'           => $phone2 !== '' ? $phone2 : null,
    'bio'              => $bio    !== '' ? $bio    : null,
    'user_image'       => $newUserImage,   // null = keep existing (repo handles this)
    'background_image' => $newBgImage,
]);

// ── 9. Update name in User table ───────────────────────────────────────────────
$stmtName = $conn->prepare(
    "UPDATE user SET first_name = :fn, last_name = :ln, name = :n WHERE id = :id"
);
$stmtName->execute([
    ':fn' => $firstName,
    ':ln' => $lastName,
    ':n'  => $firstName . ' ' . $lastName,
    ':id' => $userId,
]);

// ── 10. Redirect with success ──────────────────────────────────────────────────
header("Location: ../views/profile.php?success=" . urlencode('Profile updated successfully.'));
exit();