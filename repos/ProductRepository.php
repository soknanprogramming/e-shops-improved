<?php

class ProductRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $sql = "SELECT p.*, pi.main_image, c.name as category_name, u.name as owner_name 
                FROM product p 
                LEFT JOIN product_image pi ON p.product_image_id = pi.id 
                LEFT JOIN category c ON p.category_id = c.id
                LEFT JOIN user u ON p.owner_id = u.id
                ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search($params = []) {
        $sql = "SELECT p.*, pi.main_image, c.name as category_name, u.name as owner_name 
                FROM product p 
                LEFT JOIN product_image pi ON p.product_image_id = pi.id 
                LEFT JOIN category c ON p.category_id = c.id
                LEFT JOIN user u ON p.owner_id = u.id
                WHERE 1=1";
        
        $args = [];

        if (!empty($params['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $args[':category_id'] = $params['category_id'];
        }

        if (!empty($params['min_price'])) {
            $sql .= " AND p.prices >= :min_price";
            $args[':min_price'] = $params['min_price'];
        }

        if (!empty($params['max_price'])) {
            $sql .= " AND p.prices <= :max_price";
            $args[':max_price'] = $params['max_price'];
        }

        if (!empty($params['has_discount'])) {
            $sql .= " AND p.discounts > 0";
        }

        if (!empty($params['name'])) {
            $searchTerm = '%' . $params['name'] . '%';
            $sql .= " AND (p.name LIKE :name OR p.description LIKE :name_desc OR p.location LIKE :name_location OR c.name LIKE :name_category OR u.name LIKE :name_owner)";
            $args[':name'] = $searchTerm;
            $args[':name_desc'] = $searchTerm;
            $args[':name_location'] = $searchTerm;
            $args[':name_category'] = $searchTerm;
            $args[':name_owner'] = $searchTerm;
        }

        if (!empty($params['location'])) {
            $sql .= " AND p.location LIKE :location";
            $args[':location'] = '%' . $params['location'] . '%';
        }

        if (!empty($params['seller'])) {
            $sql .= " AND u.name LIKE :seller";
            $args[':seller'] = '%' . $params['seller'] . '%';
        }

        if (!empty($params['liked_by_user_id'])) {
            $sql .= " AND p.id IN (SELECT product_id FROM product_likes WHERE user_id = :liked_by_user_id)";
            $args[':liked_by_user_id'] = $params['liked_by_user_id'];
        }

        if (!isset($params['include_hidden']) || $params['include_hidden'] !== true) {
            $sql .= " AND p.showed = 1";
        }

        $sortMap = [
            'oldest'     => 'p.id ASC',
            'newest'     => 'p.id DESC',
            'name_asc'   => 'p.name ASC',
            'name_desc'  => 'p.name DESC',
            'price_asc'  => 'p.prices ASC',
            'price_desc' => 'p.prices DESC',
            'owner_asc'  => 'u.name ASC',
            'owner_desc' => 'u.name DESC',
            'status_asc' => 'p.showed ASC',
            'status_desc'=> 'p.showed DESC',
        ];
        $sort = $params['sort'] ?? 'newest';
        $sql .= " ORDER BY " . ($sortMap[$sort] ?? 'p.id DESC');

        if (isset($params['limit']) && isset($params['offset'])) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($sql);
        
        foreach ($args as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        if (isset($params['limit']) && isset($params['offset'])) {
            $stmt->bindValue(':limit', (int) $params['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $params['offset'], PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSearch($params = []) {
        $sql = "SELECT COUNT(*) as total 
                FROM product p 
                LEFT JOIN category c ON p.category_id = c.id
                LEFT JOIN user u ON p.owner_id = u.id
                WHERE 1=1";
        
        $args = [];

        if (!empty($params['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $args[':category_id'] = $params['category_id'];
        }
        if (!empty($params['min_price'])) {
            $sql .= " AND p.prices >= :min_price";
            $args[':min_price'] = $params['min_price'];
        }
        if (!empty($params['max_price'])) {
            $sql .= " AND p.prices <= :max_price";
            $args[':max_price'] = $params['max_price'];
        }
        if (!empty($params['has_discount'])) {
            $sql .= " AND p.discounts > 0";
        }
        if (!empty($params['name'])) {
            $searchTerm = '%' . $params['name'] . '%';
            $sql .= " AND (p.name LIKE :name OR p.description LIKE :name_desc OR p.location LIKE :name_location OR c.name LIKE :name_category OR u.name LIKE :name_owner)";
            $args[':name'] = $searchTerm;
            $args[':name_desc'] = $searchTerm;
            $args[':name_location'] = $searchTerm;
            $args[':name_category'] = $searchTerm;
            $args[':name_owner'] = $searchTerm;
        }
        if (!empty($params['location'])) {
            $sql .= " AND p.location LIKE :location";
            $args[':location'] = '%' . $params['location'] . '%';
        }
        if (!empty($params['seller'])) {
            $sql .= " AND u.name LIKE :seller";
            $args[':seller'] = '%' . $params['seller'] . '%';
        }
        if (!empty($params['liked_by_user_id'])) {
            $sql .= " AND p.id IN (SELECT product_id FROM product_likes WHERE user_id = :liked_by_user_id)";
            $args[':liked_by_user_id'] = $params['liked_by_user_id'];
        }
        if (!isset($params['include_hidden']) || $params['include_hidden'] !== true) {
            $sql .= " AND p.showed = 1";
        }

        $stmt = $this->conn->prepare($sql);

        foreach ($args as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getByCategoryId($categoryId) {
        $sql = "SELECT p.*, pi.main_image, c.name as category_name, u.name as owner_name 
                FROM product p 
                LEFT JOIN product_image pi ON p.product_image_id = pi.id 
                LEFT JOIN category c ON p.category_id = c.id
                LEFT JOIN user u ON p.owner_id = u.id
                WHERE p.category_id = :category_id
                ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByOwnerId($ownerId) {
        $sql = "SELECT p.*, pi.main_image, c.name as category_name
                FROM product p
                LEFT JOIN product_image pi ON p.product_image_id = pi.id
                LEFT JOIN category c ON p.category_id = c.id
                WHERE p.owner_id = :owner_id
                ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':owner_id' => $ownerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByOwnerIdWithSearch($ownerId, $search = '') {
        $sql = "SELECT p.*, pi.main_image, c.name as category_name
                FROM product p
                LEFT JOIN product_image pi ON p.product_image_id = pi.id
                LEFT JOIN category c ON p.category_id = c.id
                WHERE p.owner_id = :owner_id";
        
        $params = [':owner_id' => $ownerId];
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search OR c.name LIKE :search OR p.location LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT p.*, 
                       pi.main_image, pi.image1, pi.image2, pi.image3, pi.image4, pi.image5,
                       c.name as category_name, 
                       u.name as owner_name,
                       up.phone1, up.phone2, up.user_image as owner_image
                FROM product p 
                LEFT JOIN product_image pi ON p.product_image_id = pi.id 
                LEFT JOIN category c ON p.category_id = c.id
                LEFT JOIN user u ON p.owner_id = u.id
                LEFT JOIN user_profile up ON p.profile_id = up.id
                WHERE p.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        // ── Step 1: Insert image row ─────────────────────────────────────────
        $sqlImg = "INSERT INTO product_image (main_image, image1, image2, image3, image4, image5) 
                   VALUES (:main_image, :image1, :image2, :image3, :image4, :image5)";
        $stmtImg = $this->conn->prepare($sqlImg);
        $stmtImg->execute([
            ':main_image' => $data['image'],
            ':image1'     => $data['image1'] ?? null,
            ':image2'     => $data['image2'] ?? null,
            ':image3'     => $data['image3'] ?? null,
            ':image4'     => $data['image4'] ?? null,
            ':image5'     => $data['image5'] ?? null,
        ]);
        $imageId = $this->conn->lastInsertId();

        // ── Step 2: Get or create user_profile row ───────────────────────────
        $stmtProfile = $this->conn->prepare("SELECT id FROM user_profile WHERE user_id = :uid");
        $stmtProfile->execute([':uid' => $data['owner_id']]);
        $profileData = $stmtProfile->fetch(PDO::FETCH_ASSOC);

        if ($profileData) {
            $profileId = $profileData['id'];
        } else {
            $stmtCreateProfile = $this->conn->prepare(
                "INSERT INTO user_profile (user_id, phone1) VALUES (:uid, :phone)"
            );
            $stmtCreateProfile->execute([':uid' => $data['owner_id'], ':phone' => '012345678']);
            $profileId = $this->conn->lastInsertId();
        }

        // ── Step 3: Create placeholder liked row ─────────────────────────────
        // The Product table requires liked_id (NOT NULL FK → liked.id).
        // We insert a placeholder row per product to satisfy the constraint.
        $stmtLiked = $this->conn->prepare(
            "INSERT INTO liked (user_id) VALUES (:uid)"
        );
        $stmtLiked->execute([':uid' => $data['owner_id']]);
        $likedId = $this->conn->lastInsertId();

        // ── Step 4: Create placeholder comment row ───────────────────────────
        $stmtComment = $this->conn->prepare(
            "INSERT INTO comment (user_id, comment) VALUES (:uid, '')"
        );
        $stmtComment->execute([':uid' => $data['owner_id']]);
        $commentId = $this->conn->lastInsertId();

        // ── Step 5: Insert the product itself ────────────────────────────────
        $sql = "INSERT INTO product 
                    (name, prices, discounts, category_id, owner_id, product_image_id,
                     location, description, showed, profile_id, liked_id, comment_id) 
                VALUES 
                    (:name, :prices, :discounts, :category_id, :owner_id, :product_image_id,
                     :location, :description, 1, :profile_id, :liked_id, :comment_id)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':name'             => $data['name'],
            ':prices'           => $data['prices'],
            ':discounts'        => $data['discounts'] ?? 0,
            ':category_id'      => $data['category_id'],
            ':owner_id'         => $data['owner_id'],
            ':product_image_id' => $imageId,
            ':location'         => $data['location'],
            ':description'      => $data['description'],
            ':profile_id'       => $profileId,
            ':liked_id'         => $likedId,
            ':comment_id'       => $commentId,
        ]);

        return $this->conn->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE product SET 
                name = :name, 
                prices = :prices, 
                discounts = :discounts, 
                category_id = :category_id, 
                location = :location, 
                description = :description 
                WHERE id = :id AND owner_id = :owner_id";
        
        $stmt = $this->conn->prepare($sql);
        $success = $stmt->execute([
            ':name'        => $data['name'],
            ':prices'      => $data['prices'],
            ':discounts'   => $data['discounts'],
            ':category_id' => $data['category_id'],
            ':location'    => $data['location'],
            ':description' => $data['description'],
            ':id'          => $id,
            ':owner_id'    => $data['owner_id']
        ]);

        if (!$success) {
            return false;
        }

        if ($stmt->rowCount() === 0) {
            $check = $this->conn->prepare("SELECT id FROM product WHERE id = :id AND owner_id = :owner_id");
            $check->execute([':id' => $id, ':owner_id' => $data['owner_id']]);
            $exists = $check->fetchColumn();
            if ($exists === false) {
                return false;
            }
        }

        $stmtGetImg = $this->conn->prepare("SELECT product_image_id FROM product WHERE id = :id");
        $stmtGetImg->execute([':id' => $id]);
        $imgId = $stmtGetImg->fetchColumn();

        if ($imgId) {
            $imageUpdates = [];
            $imageParams = [':id' => $imgId];

            if (!empty($data['image'])) {
                $imageUpdates[] = "main_image = :main_image";
                $imageParams[':main_image'] = $data['image'];
            }
            for ($i = 1; $i <= 5; $i++) {
                if (!empty($data['image'.$i])) {
                    $imageUpdates[] = "image$i = :image$i";
                    $imageParams[":image$i"] = $data['image'.$i];
                }
            }

            if (!empty($imageUpdates)) {
                $sqlImg = "UPDATE product_image SET " . implode(', ', $imageUpdates) . " WHERE id = :id";
                $stmtImg = $this->conn->prepare($sqlImg);
                $stmtImg->execute($imageParams);
            }
        }

        return true;
    }

    public function delete($id, $ownerId = null) {
        $sqlGet = "SELECT pi.* FROM product p 
                   JOIN product_image pi ON p.product_image_id = pi.id 
                   WHERE p.id = :id";
        
        $params = [':id' => $id];
        if ($ownerId !== null) {
            $sqlGet .= " AND p.owner_id = :owner_id";
            $params[':owner_id'] = $ownerId;
        }

        $stmtGet = $this->conn->prepare($sqlGet);
        $stmtGet->execute($params);
        $images = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if (!$images) return false;

        $sqlDel = "DELETE FROM product WHERE id = :id";
        $delParams = [':id' => $id];
        if ($ownerId !== null) {
            $sqlDel .= " AND owner_id = :owner_id";
            $delParams[':owner_id'] = $ownerId;
        }

        $stmtDel = $this->conn->prepare($sqlDel);
        
        if ($stmtDel->execute($delParams)) {
            $sqlDelImg = "DELETE FROM product_image WHERE id = :id";
            $stmtDelImg = $this->conn->prepare($sqlDelImg);
            $stmtDelImg->execute([':id' => $images['id']]);
            return $images;
        }
        
        return false;
    }

    public function toggleVisibility($id, $showed) {
        $sql = "UPDATE product SET showed = :showed WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':showed' => $showed, ':id' => $id]);
    }
}
