<?php
// bdtsc-ietms/includes/Department.php

class Department {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
public function getDepartmentById(int $id) { // 'int' መሆኑን ግለጽ
        // ...
    }
    // ሁሉንም ዲፓርትመንቶች ለማምጣት
    public function getAll() {
        return $this->pdo->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll();
    }

    // አንድን ዲፓርትመንት በ ID ለመፈለግ
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM departments WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    // አዲስ ለመጨመር
    public function create($name, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO departments (dept_name, description) VALUES (?, ?)");
        return $stmt->execute([$name, $description]);
    }

    // መረጃ ለማዘመን
    public function update($id, $name, $description) {
        $stmt = $this->pdo->prepare("UPDATE departments SET dept_name = ?, description = ? WHERE id = ?");
        return $stmt->execute([$name, $description, (int)$id]);
    }

    // ለማጥፋት
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM departments WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }
}