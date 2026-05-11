<?php
// includes/User.php
class User {
    private $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    // ሁሉንም ተጠቃሚዎች ከዲፓርትመንት ስም ጋር ለማምጣት
    public function getAllUsersWithDept() {
        $sql = "SELECT u.*, d.dept_name FROM users u 
                LEFT JOIN departments d ON u.dept_id = d.id 
                ORDER BY d.dept_name ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    // አንድን ተጠቃሚ በ ID ለመለየት
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // አዲስ ተጠቃሚ ለመመዝገብ
    public function createUser($full_name, $email, $password, $role, $dept_id) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, password, user_role, dept_id, status) VALUES (?, ?, ?, ?, ?, 'Active')");
        return $stmt->execute([$full_name, $email, $hashed, $role, $dept_id]);
    }

    // መረጃ ለማዘመን (Update)
    public function updateUser($id, $full_name, $email, $role, $dept_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE users SET full_name = ?, email = ?, user_role = ?, dept_id = ?, status = ? WHERE id = ?");
        return $stmt->execute([$full_name, $email, $role, $dept_id, $status, $id]);
    }

    // ስታተስ ለመቀየር (Toggle)
    public function toggleStatus($id, $new_status) {
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$new_status, $id]);
    }

    // የይለፍ ቃል ለመቀየር
    public function resetPassword($id, $new_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed, $id]);
    }
}