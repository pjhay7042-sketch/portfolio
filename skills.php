<?php


require_once __DIR__ . '/db.php';

if (empty($_POST)) {
    parse_str(file_get_contents('php://input'), $_POST);
}

$VALID_LEVELS = ['Beginner', 'Basic', 'Intermediate', 'Advanced', 'Expert'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':   listSkills($pdo);   break;
    case 'create': createSkill($pdo);  break;
    case 'update': updateSkill($pdo);  break;
    case 'delete': deleteSkill($pdo);  break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown or missing action.']);
}

function respond(bool $success, array $payload = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success], $payload));
}

function validateSkillInput(): array {
    global $VALID_LEVELS;
    $name     = trim($_POST['name']     ?? '');
    $category = trim($_POST['category'] ?? '');
    $level    = trim($_POST['level']    ?? '');

    if ($name === '') {
        respond(false, ['message' => 'Skill name is required.'], 422);
        exit;
    }
    if (strlen($name) > 100) {
        respond(false, ['message' => 'Skill name must be 100 characters or fewer.'], 422);
        exit;
    }
    if ($category === '') $category = 'Other';
    if (!in_array($level, $VALID_LEVELS, true)) $level = 'Basic';

    return compact('name', 'category', 'level');
}

function listSkills(PDO $pdo): void {
    $rows = $pdo->query("SELECT id, name, category, level, created_at FROM skills ORDER BY id ASC")->fetchAll();
    respond(true, ['skills' => $rows]);
}

function createSkill(PDO $pdo): void {
    $d = validateSkillInput();
    $stmt = $pdo->prepare("INSERT INTO skills (name, category, level) VALUES (?, ?, ?)");
    $stmt->execute([$d['name'], $d['category'], $d['level']]);
    respond(true, ['skill' => ['id' => (int)$pdo->lastInsertId()] + $d]); }
function updateSkill(PDO $pdo): void { $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { respond(false, ['message' => 'Skill id is required.'], 422); return; }
    $d = validateSkillInput(); $pdo->prepare("UPDATE skills SET name = ?, category = ?, level = ? WHERE id = ?")->execute([$d['name'], $d['category'], $d['level'], $id]);
    respond(true, ['skill' => ['id' => $id] + $d]); }

function deleteSkill(PDO $pdo): void { $id = (int)($_POST['id'] ?? 0); if ($id <= 0) { respond(false, ['message' => 'Skill id is required.'], 422); return; }
$pdo->prepare("DELETE FROM skills WHERE id = ?")->execute([$id]); respond(true); }