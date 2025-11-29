<?php
// ===== index.php =====
session_start();

// ===== Classes Definition =====
abstract class Pokemon {
    protected $name;
    protected $type;
    protected $level;
    protected $hp;
    protected $maxHp;
    protected $attack;
    protected $defense;
    protected $speed;
    protected $specialMove;
    
    public function __construct($name, $type, $level, $hp, $attack, $defense, $speed, $specialMove) {
        $this->name = $name;
        $this->type = $type;
        $this->level = $level;
        $this->hp = $hp;
        $this->maxHp = $hp;
        $this->attack = $attack;
        $this->defense = $defense;
        $this->speed = $speed;
        $this->specialMove = $specialMove;
    }
    
    public function getInfo() {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'level' => $this->level,
            'hp' => $this->hp,
            'maxHp' => $this->maxHp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'speed' => $this->speed,
            'specialMove' => $this->specialMove
        ];
    }
    
    abstract public function train($trainingType, $intensity);
    abstract public function specialMove();
    
    public function getLevel() { return $this->level; }
    public function getHp() { return $this->hp; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getAttack() { return $this->attack; }
    public function getDefense() { return $this->defense; }
    public function getSpeed() { return $this->speed; }
    public function getSpecialMoveName() { return $this->specialMove['name']; }
    
    public function setLevel($level) { $this->level = $level; }
    public function setHp($hp) { $this->hp = min($hp, $this->maxHp); }
    public function setAttack($attack) { $this->attack = $attack; }
    public function setDefense($defense) { $this->defense = $defense; }
    public function setSpeed($speed) { $this->speed = $speed; }
}

class WaterPokemon extends Pokemon {
    public function train($trainingType, $intensity) {
        $levelGain = intval($intensity / 10) + 1;
        $hpGain = intval($intensity / 5) + 2;
        $result = [];
        
        $result['levelBefore'] = $this->level;
        $result['hpBefore'] = $this->hp;
        $result['attackBefore'] = $this->attack;
        $result['defenseBefore'] = $this->defense;
        $result['speedBefore'] = $this->speed;
        
        switch($trainingType) {
            case 'Attack':
                $this->attack += intval($intensity * 0.15);
                $this->level += $levelGain;
                $this->hp += $hpGain;
                break;
            case 'Defense':
                $this->defense += intval($intensity * 0.15);
                $this->level += $levelGain;
                $this->hp += $hpGain;
                break;
            case 'Speed':
                $this->speed += intval($intensity * 0.12);
                $this->level += $levelGain;
                $this->hp += $hpGain;
                break;
        }
        
        $this->hp = min($this->hp, $this->maxHp);
        
        $result['levelAfter'] = $this->level;
        $result['hpAfter'] = $this->hp;
        $result['attackAfter'] = $this->attack;
        $result['defenseAfter'] = $this->defense;
        $result['speedAfter'] = $this->speed;
        $result['trainingType'] = $trainingType;
        $result['intensity'] = $intensity;
        $result['timestamp'] = date('Y-m-d H:i:s');
        
        return $result;
    }
    
    public function specialMove() {
        $move = $this->specialMove;
        $damage = intval($this->attack * 1.5) + rand(10, 30);
        return [
            'name' => $move['name'],
            'description' => $move['description'],
            'damage' => $damage,
            'effect' => $move['effect']
        ];
    }
}

class Poliwag extends WaterPokemon {
    public function __construct() {
        $specialMove = [
            'name' => 'Bubble Beam',
            'description' => 'Menembakkan gelembung yang mengenai target beberapa kali.',
            'effect' => 'Dapat mengurangi kecepatan lawan'
        ];
        parent::__construct('Poliwag', 'Water', 5, 20, 12, 11, 10, $specialMove);
    }
}

class TrainingSession {
    private $sessions = [];
    private $dataFile = 'data/training_history.json';
    
    public function __construct() {
        if (!file_exists('data')) {
            mkdir('data', 0755, true);
        }
        $this->loadSessions();
    }
    
    public function addSession($sessionData) {
        $this->sessions[] = $sessionData;
        $this->saveSessions();
    }
    
    public function getSessions() {
        return $this->sessions;
    }
    
    private function saveSessions() {
        file_put_contents($this->dataFile, json_encode($this->sessions, JSON_PRETTY_PRINT));
    }
    
    private function loadSessions() {
        if (file_exists($this->dataFile)) {
            $this->sessions = json_decode(file_get_contents($this->dataFile), true) ?? [];
        }
    }
}

// Initialize Pokemon
if (!isset($_SESSION['poliwag'])) {
    $_SESSION['poliwag'] = new Poliwag();
}

if (!isset($_SESSION['trainingSession'])) {
    $_SESSION['trainingSession'] = new TrainingSession();
}

$poliwag = $_SESSION['poliwag'];
$trainingSession = $_SESSION['trainingSession'];

// Handle Training Form
$trainingResult = null;
if ($_SERVER['REQUEST_METHOD'] ?? false) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'train') {
        $trainingType = $_POST['trainingType'] ?? '';
        $intensity = intval($_POST['intensity'] ?? 50);
        
        $trainingResult = $poliwag->train($trainingType, $intensity);
        $trainingSession->addSession($trainingResult);
    }
}

// Handle page navigation
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Load HTML View
include 'view.html';
?>
