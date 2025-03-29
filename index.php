<?php
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "form_oops";
    protected $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
}

class User extends Database {
    public function getAllUsers() {
        return $this->conn->query("SELECT * FROM users ORDER BY id DESC");
    }

    public function getUser($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createUser($first_name, $last_name, $email, $phone, $address, $age) {
        $phone = preg_replace('/\D/', '', $phone); // Remove non-numeric characters
        $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, phone, address, age) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $age);
        return $stmt->execute();
    }

    public function updateUser($id, $first_name, $last_name, $email, $phone, $address, $age) {
        $phone = preg_replace('/\D/', '', $phone); 
        $stmt = $this->conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, age=? WHERE id=?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $address, $age, $id);
        return $stmt->execute();
    }

    public function deleteUser($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}

$userObj = new User();
$editUser = isset($_GET['edit']) ? $userObj->getUser($_GET['edit']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $userObj->createUser($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['age']);
    } elseif (isset($_POST['update'])) {
        $userObj->updateUser($_POST['id'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['age']);
    } elseif (isset($_POST['delete'])) {
        $userObj->deleteUser($_POST['id']);
    }
    header("Location: index.php");
    exit();
}

$users = $userObj->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">

    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">👤 User Management</h1>

        <!-- Create User Button -->
        <button onclick="document.getElementById('userForm').classList.toggle('hidden')" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg w-full font-semibold shadow-md transition-all">
            ➕ Add New User
        </button>

        <!-- User Form -->
        <div id="userForm" class="mt-6 bg-gray-100 p-6 rounded-lg shadow-md <?php echo $editUser ? '' : 'hidden'; ?>">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">
                <?php echo $editUser ? "✏️ Edit User" : "📝 Add User"; ?>
            </h2>
            <form method="POST" class="space-y-4">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                <?php endif; ?>
                <input type="text" name="first_name" placeholder="First Name" class="w-full border p-3 rounded-lg" required value="<?= $editUser['first_name'] ?? '' ?>">
                <input type="text" name="last_name" placeholder="Last Name" class="w-full border p-3 rounded-lg" required value="<?= $editUser['last_name'] ?? '' ?>">
                <input type="email" name="email" placeholder="Email" class="w-full border p-3 rounded-lg" required value="<?= $editUser['email'] ?? '' ?>">
                <input type="text" name="phone" placeholder="Phone Number" class="w-full border p-3 rounded-lg" required value="<?= $editUser['phone'] ?? '' ?>">
                <input type="text" name="address" placeholder="Address" class="w-full border p-3 rounded-lg" required value="<?= $editUser['address'] ?? '' ?>">
                <input type="number" name="age" placeholder="Age" class="w-full border p-3 rounded-lg" required value="<?= $editUser['age'] ?? '' ?>">

                <button type="submit" name="<?php echo $editUser ? "update" : "create"; ?>" 
                        class="w-full bg-<?php echo $editUser ? "green" : "blue"; ?>-600 hover:bg-<?php echo $editUser ? "green" : "blue"; ?>-700 text-white font-semibold px-4 py-2 rounded-lg transition-all">
                    <?php echo $editUser ? "✅ Update" : "🚀 Save"; ?>
                </button>
                
                <?php if ($editUser): ?>
                    <a href="index.php" class="text-red-600 block text-center mt-2 font-semibold hover:underline">❌ Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users List -->
        <h2 class="text-2xl font-semibold text-gray-700 mt-8">📋 User List</h2>
        <div class="space-y-4 mt-4">
            <?php while ($user = $users->fetch_assoc()): ?>
                <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200 hover:shadow-xl transition-all">
                    <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']) ?></h3>
                    <p class="text-gray-600">📧 <?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-gray-600">📞 <?= htmlspecialchars($user['phone']) ?></p>
                    <p class="text-gray-600">🏡 <?= htmlspecialchars($user['address']) ?></p>
                    <p class="text-gray-600">🎂 Age: <?= $user['age'] ?></p>

                    <!-- Buttons -->
                    <div class="mt-4 flex items-center space-x-4">
                        <a href="index.php?edit=<?= $user['id'] ?>" class="px-4 py-2 bg-blue-500 text-white rounded-lg">✏️ Edit</a>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete" class="px-4 py-2 bg-red-500 text-white rounded-lg">🗑️ Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</body>
</html>
