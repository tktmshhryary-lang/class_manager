<?php
session_start();
$host='127.0.0.1'; $db='class_manager'; $user='root'; $pass='';
$message=''; $error='';
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `class_manager` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `class_manager`");
    // If the app marker is missing, this is a first install or a legacy/incomplete database.
    // In that case rebuild the small class database so old broken tables cannot interfere.
    $markerExists = false;
    try {
        $pdo->query("SELECT 1 FROM app_meta LIMIT 1");
        $markerExists = true;
    } catch (PDOException $ignored) {}

    if (!$markerExists) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        foreach (['discipline','homework_records','homework','attendance','students','users','settings','app_meta'] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $pdo->exec("CREATE TABLE app_meta (
            id TINYINT UNSIGNED PRIMARY KEY,
            app_name VARCHAR(100) NOT NULL,
            app_version VARCHAR(20) NOT NULL,
            installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE settings (
            id TINYINT UNSIGNED PRIMARY KEY,
            teacher_name VARCHAR(150) NOT NULL,
            grade VARCHAR(100) NOT NULL,
            school_year VARCHAR(50) NOT NULL,
            school_name VARCHAR(200) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE students (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            student_code VARCHAR(50) NULL UNIQUE,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE attendance (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_attendance (student_id,attendance_date),
            CONSTRAINT fk_att_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE homework (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            due_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE homework_records (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            homework_id INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            status ENUM('done','not_done','incomplete') NOT NULL DEFAULT 'not_done',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_homework_student(homework_id,student_id),
            CONSTRAINT fk_hr_homework FOREIGN KEY(homework_id) REFERENCES homework(id) ON DELETE CASCADE,
            CONSTRAINT fk_hr_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE discipline (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            record_date DATE NOT NULL,
            score DECIMAL(5,2) NOT NULL DEFAULT 100,
            activity DECIMAL(5,2) NOT NULL DEFAULT 100,
            note VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_discipline(student_id,record_date),
            CONSTRAINT fk_disc_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        $s=$pdo->prepare("INSERT INTO app_meta(id,app_name,app_version) VALUES(1,?,?)");
        $s->execute(['کلاس من','1.0']);

        $s=$pdo->prepare("INSERT INTO settings(id,teacher_name,grade,school_year,school_name) VALUES(1,?,?,?,?)");
        $s->execute(['اسما نوری‌نژاد','پایه ششم','۱۴۰۵-۱۴۰۶','دبستان دخترانه آیات']);

        $s=$pdo->prepare("INSERT INTO users(username,password_hash) VALUES(?,?)");
        $s->execute(['admin',password_hash('admin',PASSWORD_DEFAULT)]);
        $s=$pdo->prepare("INSERT INTO students(name,student_code) VALUES(?,?)");
        foreach([['علی رضایی','1001'],['محمد احمدی','1002'],['امیر کریمی','1003'],['سارا محمدی','1004'],['نگار حسینی','1005']] as $st)$s->execute($st);
        $s=$pdo->prepare("INSERT INTO homework(title,due_date) VALUES(?,?)");
        $s->execute(['تمرین نمونه ریاضی',date('Y-m-d',strtotime('+7 days'))]);
        $s->execute(['مطالعه درس علوم',date('Y-m-d',strtotime('+5 days'))]);
    } else {
        // Already installed: do not erase class data. Only make sure the login account exists.
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id TINYINT UNSIGNED PRIMARY KEY,
            teacher_name VARCHAR(150) NOT NULL,
            grade VARCHAR(100) NOT NULL,
            school_year VARCHAR(50) NOT NULL,
            school_name VARCHAR(200) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $sc=(int)$pdo->query("SELECT COUNT(*) FROM settings WHERE id=1")->fetchColumn();
        if(!$sc){$s=$pdo->prepare("INSERT INTO settings(id,teacher_name,grade,school_year,school_name) VALUES(1,?,?,?,?)");$s->execute(['اسما نوری‌نژاد','پایه ششم','۱۴۰۵-۱۴۰۶','دبستان دخترانه آیات']);}
        $count=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE username='admin'")->fetchColumn();
        if(!$count){
            $s=$pdo->prepare("INSERT INTO users(username,password_hash) VALUES(?,?)");
            $s->execute(['admin',password_hash('admin',PASSWORD_DEFAULT)]);
        }
    }

    $message='راه‌اندازی با موفقیت انجام شد. دیتابیس و جداول آماده‌اند.';
} catch(Exception $ex) {
    $error='خطا در راه‌اندازی: '.$ex->getMessage();
}
?>
<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>راه‌اندازی | کلاس من</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login-page"><div class="login-card setup-card"><div class="brand-mark">CM</div><div class="brand-title">راه‌اندازی کلاس من</div>
<?php if($message): ?><div class="alert success"><?=e($message)?></div><div class="setup-info"><p>اکنون سامانه آماده است.</p><p>نام کاربری: <b>admin</b></p><p>رمز عبور: <b>admin</b></p><a class="btn primary full" href="login.php">ورود به سامانه</a></div><?php else: ?><div class="alert error"><?=e($error)?></div><p class="muted">Apache و MySQL را در XAMPP روشن کنید و دوباره صفحه را باز کنید.</p><?php endif; ?></div></body></html>