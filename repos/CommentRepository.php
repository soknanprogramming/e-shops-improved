<?php

class CommentRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function add($productId, $userId, $comment) {
        $sql = "INSERT INTO product_comments (product_id, user_id, comment) VALUES (:pid, :uid, :comment)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':pid' => $productId,
            ':uid' => $userId,
            ':comment' => $comment
        ]);
        return $this->conn->lastInsertId();
    }

    public function getAllByProductId($productId) {
        $sql = "SELECT c.*, u.name as user_name, up.user_image
                FROM product_comments c
                JOIN user u ON c.user_id = u.id
                LEFT JOIN user_profile up ON up.user_id = c.user_id
                WHERE c.product_id = :pid
                ORDER BY c.created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($commentId, $userId) {
        $sql = "DELETE FROM product_comments WHERE id = :id AND user_id = :uid";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $commentId, ':uid' => $userId]);
    }
}
