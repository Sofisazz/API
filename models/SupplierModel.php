<?php
class SupplierModel {
    private $conn;
    private $table_name = "suppliers";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Получить всех поставщиков
    public function getAll() {
        $query = "SELECT id, company_name, contact_name, phone, email, created_at, updated_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Получить одного поставщика по ID
    public function getById($id) {
        $query = "SELECT id, company_name, contact_name, phone, email, created_at, updated_at FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    // Создать нового поставщика
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " SET company_name=:company_name, contact_name=:contact_name, phone=:phone, email=:email";
        $stmt = $this->conn->prepare($query);

        // Санитизация данных
        $company_name = htmlspecialchars(strip_tags($data['company_name']));
        $contact_name = htmlspecialchars(strip_tags($data['contact_name']));
        $phone = htmlspecialchars(strip_tags($data['phone']));
        $email = htmlspecialchars(strip_tags($data['email']));

        // Привязка параметров
        $stmt->bindParam(":company_name", $company_name);
        $stmt->bindParam(":contact_name", $contact_name);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":email", $email);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Обновить поставщика
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET company_name=:company_name, contact_name=:contact_name, phone=:phone, email=:email WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        // Санитизация данных
        $company_name = htmlspecialchars(strip_tags($data['company_name']));
        $contact_name = htmlspecialchars(strip_tags($data['contact_name']));
        $phone = htmlspecialchars(strip_tags($data['phone']));
        $email = htmlspecialchars(strip_tags($data['email']));

        // Привязка параметров
        $stmt->bindParam(":company_name", $company_name);
        $stmt->bindParam(":contact_name", $contact_name);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Удалить поставщика
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Проверить существование поставщика
    public function exists($id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>