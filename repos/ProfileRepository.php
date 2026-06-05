<?php
/**
 * ProfileRepository.php
 * Place at: repos/ProfileRepository.php
 */
class ProfileRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ── Basic fetch ────────────────────────────────────────────────────────────
    public function getByUserId(int $userId): ?array {
        $stmt = $this->conn->prepare(
            "SELECT * FROM user_profile WHERE user_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ── Full profile with gender join (used by admin panel) ───────────────────
    public function getFullProfileByUserId(int $userId): ?array {
        $stmt = $this->conn->prepare(
            "SELECT up.*, g.name AS gender
             FROM user_profile up
             LEFT JOIN gender g ON g.id = up.gender_id
             WHERE up.user_id = :uid
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ── Public save: decides insert vs update ──────────────────────────────────
    /**
     * $data keys: phone1 (required), phone2, bio, user_image, background_image
     * Images are only replaced when a non-null value is supplied.
     */
    public function save(int $userId, array $data): void {
        $existing = $this->getByUserId($userId);
        if ($existing) {
            $this->update($userId, $data, $existing);
        } else {
            $this->create($userId, $data);
        }
    }

    // ── Private: INSERT ────────────────────────────────────────────────────────
    private function create(int $userId, array $data): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO user_profile
                (user_id, phone1, phone2, bio, user_image, background_image)
             VALUES
                (:uid, :p1, :p2, :bio, :uimg, :bimg)"
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':p1'   => $data['phone1'],
            ':p2'   => $data['phone2']          ?? null,
            ':bio'  => $data['bio']             ?? null,
            ':uimg' => $data['user_image']       ?? null,
            ':bimg' => $data['background_image'] ?? null,
        ]);
    }

    // ── Private: UPDATE (preserves existing images if no new file supplied) ───
    private function update(int $userId, array $data, array $existing): void {
        // Only overwrite images when a new filename was actually provided
        $userImage = (isset($data['user_image']) && $data['user_image'] !== null && $data['user_image'] !== '')
            ? $data['user_image']
            : $existing['user_image'];

        $bgImage = (isset($data['background_image']) && $data['background_image'] !== null && $data['background_image'] !== '')
            ? $data['background_image']
            : $existing['background_image'];

        $stmt = $this->conn->prepare(
            "UPDATE user_profile
             SET phone1            = :p1,
                 phone2            = :p2,
                 bio               = :bio,
                 user_image        = :uimg,
                 background_image  = :bimg
             WHERE user_id = :uid"
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':p1'   => $data['phone1'],
            ':p2'   => $data['phone2'] ?? null,
            ':bio'  => $data['bio']    ?? null,
            ':uimg' => $userImage,
            ':bimg' => $bgImage,
        ]);
    }

    // ── Delete images (called when user removes their photo) ──────────────────
    public function removeUserImage(int $userId): void {
        $stmt = $this->conn->prepare(
            "UPDATE user_profile SET user_image = NULL WHERE user_id = :uid"
        );
        $stmt->execute([':uid' => $userId]);
    }

    public function removeBackgroundImage(int $userId): void {
        $stmt = $this->conn->prepare(
            "UPDATE user_profile SET background_image = NULL WHERE user_id = :uid"
        );
        $stmt->execute([':uid' => $userId]);
    }
}