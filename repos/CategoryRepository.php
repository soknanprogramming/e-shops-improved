<?php

class CategoryRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM category ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllWithFilters($search = '', $orderBy = 'id_desc') {
        $sql = "SELECT * FROM category WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        switch ($orderBy) {
            case 'id_asc':
                $sql .= " ORDER BY id ASC";
                break;
            case 'id_desc':
                $sql .= " ORDER BY id DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY name ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY name DESC";
                break;
            default:
                $sql .= " ORDER BY id DESC";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM category WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO category (name, category_image) VALUES (:name, :image)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':image' => $data['category_image']
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($data) {
        $sql = "UPDATE category SET name = :name, category_image = :image WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':image' => $data['category_image'],
            ':id' => $data['id']
        ]);
        return true;
    }

    public function delete($id) {
        $sql = "DELETE FROM category WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function hasProducts($id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM Product WHERE category_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }
}