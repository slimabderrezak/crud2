<?php
// config.php - Configuration de la base de données
class Database {
    private $host = 'localhost';
    private $db_name = '';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Erreur de connexion : " . $e->getMessage();
        }
        return $this->conn;
    }
}

// User.php - Classe pour gérer les utilisateurs
class User {
    private $conn;
    private $table = 'crud2';

    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $telephone;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE - Créer un nouvel utilisateur
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        
        // Lier les paramètres
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenom', $this->prenom);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':telephone', $this->telephone);
        
        return $stmt->execute();
    }

    // READ - Lire tous les utilisateurs
    public function read() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // READ - Lire un utilisateur spécifique
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->email = $row['email'];
            $this->telephone = $row['telephone'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
        }
    }

    // UPDATE - Mettre à jour un utilisateur
    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, updated_at = NOW()
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Lier les paramètres
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenom', $this->prenom);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    // DELETE - Supprimer un utilisateur
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    // Vérifier si l'email existe
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email";
        if($this->id) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        if($this->id) {
            $stmt->bindParam(':id', $this->id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Compter les utilisateurs
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}

// index.php - Interface principale
session_start();
include_once 'config.php';
include_once 'User.php';

$database = new Database();
$db = $database->connect();
$user = new User($db);

$message = '';
$messageType = '';

// Gestion des actions
if($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'create':
            $user->nom = $_POST['nom'];
            $user->prenom = $_POST['prenom'];
            $user->email = $_POST['email'];
            $user->telephone = $_POST['telephone'];
            
            if($user->emailExists()) {
                $message = "Cet email est déjà utilisé !";
                $messageType = "error";
            } else {
                if($user->create()) {
                    $message = "Utilisateur créé avec succès !";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de la création !";
                    $messageType = "error";
                }
            }
            break;
            
        case 'update':
            $user->id = $_POST['id'];
            $user->nom = $_POST['nom'];
            $user->prenom = $_POST['prenom'];
            $user->email = $_POST['email'];
            $user->telephone = $_POST['telephone'];
            
            if($user->emailExists()) {
                $message = "Cet email est déjà utilisé !";
                $messageType = "error";
            } else {
                if($user->update()) {
                    $message = "Utilisateur modifié avec succès !";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de la modification !";
                    $messageType = "error";
                }
            }
            break;
            
        case 'delete':
            $user->id = $_POST['id'];
            if($user->delete()) {
                $message = "Utilisateur supprimé avec succès !";
                $messageType = "success";
            } else {
                $message = "Erreur lors de la suppression !";
                $messageType = "error";
            }
            break;
    }
}

// Récupérer tous les utilisateurs
$stmt = $user->read();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les données pour modification
$editUser = null;
if(isset($_GET['edit'])) {
    $user->id = $_GET['edit'];
    $user->readOne();
    $editUser = $user;
}

// Statistiques
$totalUsers = $user->count();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système CRUD PHP - Gestion d'utilisateurs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .content {
            padding: 30px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }

        .form-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            margin-right: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-edit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .users-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .no-users {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        @media (max-width: 768px) {
            .stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .users-table {
                font-size: 14px;
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Système CRUD PHP</h1>
            <p>Gestion complète des utilisateurs avec MySQL</p>
        </div>

        <div class="content">
            <?php if($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($users, function($u) { return !empty($u['email']); })); ?></div>
                    <div class="stat-label">Emails</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($users, function($u) { return !empty($u['telephone']); })); ?></div>
                    <div class="stat-label">Téléphones</div>
                </div>
            </div>

            <div class="form-section">
                <h2><?php echo $editUser ? 'Modifier l\'utilisateur' : 'Ajouter un nouvel utilisateur'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                    <?php if($editUser): ?>
                        <input type="hidden" name="id" value="<?php echo $editUser->id; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="nom">Nom :</label>
                        <input type="text" id="nom" name="nom" required value="<?php echo $editUser ? htmlspecialchars($editUser->nom) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom">Prénom :</label>
                        <input type="text" id="prenom" name="prenom" required value="<?php echo $editUser ? htmlspecialchars($editUser->prenom) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email :</label>
                        <input type="email" id="email" name="email" required value="<?php echo $editUser ? htmlspecialchars($editUser->email) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone :</label>
                        <input type="tel" id="telephone" name="telephone" value="<?php echo $editUser ? htmlspecialchars($editUser->telephone) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn">
                        <?php echo $editUser ? 'Modifier' : 'Ajouter'; ?>
                    </button>
                    
                    <?php if($editUser): ?>
                        <a href="index.php" class="btn btn-cancel">Annuler</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="form-section">
                <h2>Liste des utilisateurs</h2>
                
                <?php if(count($users) > 0): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($u['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo $u['telephone'] ? htmlspecialchars($u['telephone']) : 'Non renseigné'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $u['id']; ?>" class="btn btn-edit">Modifier</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-delete">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-users">
                        Aucun utilisateur trouvé. Ajoutez votre premier utilisateur !
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
/*
-- Script SQL pour créer la base de données et la table
-- Exécutez ce script dans phpMyAdmin ou votre interface MySQL

CREATE DATABASE IF NOT EXISTS crud2;
USE crud2;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Insérer quelques données d'exemple (optionnel)
INSERT INTO users (nom, prenom, email, telephone) VALUES
('Dupont', 'Jean', 'jean.dupont@email.com', '0123456789'),
('Martin', 'Marie', 'marie.martin@email.com', '0987654321'),
('Bernard', 'Pierre', 'pierre.bernard@email.com', '0147258369');
*/
?>