<?php

class LikeRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hasLiked($userId, $productId) {
        $sql = "SELECT id FROM product_likes WHERE user_id = :uid AND product_id = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        return $stmt->fetch() ? true : false;
    }

    public function toggle($userId, $productId) {
        if ($this->hasLiked($userId, $productId)) {
            $sql = "DELETE FROM product_likes WHERE user_id = :uid AND product_id = :pid";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':uid' => $userId, ':pid' => $productId]);
            return false; // Unliked
        } else {
            $sql = "INSERT INTO product_likes (user_id, product_id) VALUES (:uid, :pid)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':uid' => $userId, ':pid' => $productId]);
            return true; // Liked
        }
    }

    public function getCount($productId) {
        $sql = "SELECT COUNT(*) as total FROM product_likes WHERE product_id = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}