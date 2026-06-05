<?php

class UserRepository {
    protected $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function create(array $data) {
        // can_post and request_post_permission are included so auth.php can set them on register
        $sql = "INSERT INTO `User` (name, first_name, last_name, email, password, provider, provider_id, avatar, is_admin, can_post, request_post_permission) 
                VALUES (:name, :first_name, :last_name, :email, :password, :provider, :provider_id, :avatar, :is_admin, :can_post, :request_post_permission)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->execute([
            ':name'                    => $data['name'],
            ':first_name'              => $data['first_name'],
            ':last_name'               => $data['last_name'],
            ':email'                   => $data['email']       ?? null,
            ':password'                => $data['password']    ?? null,
            ':provider'                => $data['provider']    ?? null,
            ':provider_id'             => $data['provider_id'] ?? null,
            ':avatar'                  => $data['avatar']      ?? 0,
            ':is_admin'                => $data['is_admin']    ?? 0,
            ':can_post'                => $data['can_post']    ?? 1,   // default 1 = everyone can post
            ':request_post_permission' => $data['request_post_permission'] ?? 0,
        ]);
        
        return $this->conn->lastInsertId();
    }

    public function findById($id) {
        $sql = "SELECT * FROM `User` WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM `User` WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByName($name) {
        $sql = "SELECT * FROM `User` WHERE name = :name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':name' => $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, array $data) {
        $fields = [];
        $params = [':id' => $id];
        $allowed = ['name', 'first_name', 'last_name', 'email', 'password', 'provider', 'provider_id', 'avatar', 'is_admin', 'can_post', 'request_post_permission'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `User` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $sql = "DELETE FROM `User` WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function getAll() {
        $sql = "SELECT * FROM `User` ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search($term) {
        $term = "%$term%";
        $sql = "SELECT * FROM `User` WHERE name LIKE :term OR email LIKE :term ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':term' => $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingRequests() {
        $sql = "SELECT * FROM `User` WHERE request_post_permission = 1 AND (can_post = 0 OR can_post IS NULL) ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllWithFilters($filter = null, $search = null, $orderBy = null) {
        $sql = "SELECT * FROM `User`";
        $params = [];
        $conditions = [];

        // Apply pending requests filter
        if ($filter === 'requesting') {
            $conditions[] = "request_post_permission = 1 AND (can_post = 0 OR can_post IS NULL)";
        }

        // Apply search filter
        if (!empty($search)) {
            $conditions[] = "(name LIKE :search OR email LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Apply ordering
        $orderClause = match($orderBy) {
            'name_asc' => 'ORDER BY name ASC',
            'name_desc' => 'ORDER BY name DESC',
            'id_asc' => 'ORDER BY id ASC',
            'id_desc' => 'ORDER BY id DESC',
            'role_asc' => 'ORDER BY is_admin ASC, name ASC',
            'role_desc' => 'ORDER BY is_admin DESC, name ASC',
            'permission_asc' => 'ORDER BY can_post ASC, name ASC',
            'permission_desc' => 'ORDER BY can_post DESC, name ASC',
            default => 'ORDER BY id DESC'
        };
        $sql .= " " . $orderClause;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}