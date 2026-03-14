<?php
session_start();

// Константа DPI для целевого экрана (1.28" 240x240)
define('TARGET_DPI', 265);

if (isset($_GET['reset'])) unset($_SESSION['fonts']);

if (isset($_POST["get-font"])) {
    if (!isset($_POST['size']) || !is_numeric($_POST['size']) || $_POST['size'] < 3 || $_POST['size'] > 200) exit();
    $size = intval($_POST['size']);

    if (!isset($_POST['font']) || empty($_POST['font'])) exit();
    $font = escapeshellarg("fonts/" . $_POST['font']);

    // Третий аргумент для fontconvert — DPI целевого экрана
    exec("./fontconvert $font $size " . TARGET_DPI, $output, $retval);
    if ($retval != 0) exit();

    // Определяем оригинальное базовое имя (то, что используется в массивах)
    $originalBaseName = '';
    foreach ($output as $line) {
        if (preg_match('/const\s+GFXfont\s+([a-zA-Z0-9_]+)\s+PROGMEM\s*=\s*{/', $line, $matches)) {
            $originalBaseName = $matches[1];
            break;
        }
    }

    // Если задано custom_name и оно валидно, заменяем все вхождения оригинального имени
    $customName = '';
    if (isset($_POST['custom_name']) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $_POST['custom_name'])) {
        $customName = $_POST['custom_name'];
    }

    if (!empty($customName) && !empty($originalBaseName)) {
        // Заменяем во всех строках вывода
        foreach ($output as &$line) {
            $line = str_replace($originalBaseName, $customName, $line);
        }
        unset($line);
        // Имя файла для скачивания
        $filename = $customName . '.h';
    } else {
        // Оригинальное имя файла (как было)
        $filename = $output[count($output) - 6];
        $filename = str_replace("const GFXfont ", "", $filename);
        $filename = str_replace(" PROGMEM = {", ".h", $filename);
    }

    header("Content-Disposition: attachment; filename=\"$filename\"");
    foreach ($output as $line) echo "$line\n";
    exit();
}

if (!isset($_SESSION['fonts'])) $_SESSION['fonts'] = array();

// Delete fonts from session variable if the disk file is not there anymore
foreach ($_SESSION['fonts'] as $index => $font) {
    if (!file_exists("fonts/user/$font")) unset($_SESSION['fonts'][$index]);
}

$select_font = "";
if (isset($_POST["submit-file"])) {
    $target_dir = "fonts/user/";
    $filename = basename($_FILES["fileToUpload"]["name"]);
    $target_file = $target_dir . $filename;
    $select_font = "user/$filename";

    if (strtolower(substr($target_file, -4)) == ".ttf") {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            if (!in_array($filename, $_SESSION['fonts'])) {
                array_push($_SESSION['fonts'], $filename);
                if (count($_SESSION['fonts']) > 5) array_shift($_SESSION['fonts']);
            }
        }
    }
}

// --- Получаем список системных шрифтов из папки fonts/ (исключая user/) ---
$system_fonts = glob("fonts/*.ttf");
$system_fonts = array_map('basename', $system_fonts);
$system_fonts = array_filter($system_fonts, function($f) {
    return strpos($f, 'user/') === false;
});
sort($system_fonts);
if (empty($system_fonts)) $system_fonts = ['FreeSans.ttf']; // fallback

// Выбираем первый системный шрифт как выбранный по умолчанию, если нет сохранённого пользовательского
$default_system_font = reset($system_fonts);
?>

<html>
<head>
    <title>TrueType to Adafruit GFX</title>
    <style>
        body {
            background-color: #000000;
            color: #ffffff;
            margin: 100px;
            margin-top: 100px;
            margin-left: 100px;
            font-family: Verdana, sans-serif;
        }
        a {
            text-decoration: none;
            font-weight: bold;
            color: #8080FF;
        }
        td {
            vertical-align: top;
        }
        table {
            width: 960px;
        }
        td#first {
            margin: 0px;
            padding-top: 14px;
            padding-left:14px;
            background-image:url('GC9A01.png');
            background-repeat:no-repeat;
            width: 487px;
            height: 578px;
        }
        td#second {
            width: 270px;
        }
        td#third {
            width: 210px;
        }
        #textfield {
            width: 200px;
        }
        #sizefield {
            width: 35px;
            text-align: center;
        }
        #custom_name {
            width: 200px;
            margin-bottom: 10px;
        }
        #get-font {
            width: 200px;
            height: 30px;
            font-size: 20px;
            font-weight: bold;
        }
        select {
            background-color: #333;
            color: white;
            border: 1px solid #8080FF;
            padding: 2px;
            margin-bottom: 10px;
        }
        .note {
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>

<body onload = 'setFont()'>
    &nbsp;<br>
    <table>
        <tr>
            <td colspan=3>
                <h2>Подготовка шрифтов для экрана GC9A01</h2>
                <h2>TrueType > Adafruit GFX</h2>
                &nbsp;<br>
                &nbsp;<br>
            </td>
        </tr>
        <tr>
            <td id="first">
                <img id="image" src="image.php?dpi=<?php echo TARGET_DPI; ?>">
            </td>
            <td id="second">
                <form action="" method="post" enctype="multipart/form-data">

                    <!-- Системные шрифты (выпадающий список) -->
                    <h3>Имеющиеся шрифты</h3>
                    <select id="system-font-select" name="font" onchange="updateImage()">
                        <?php foreach ($system_fonts as $sf): ?>
                            <option value="<?php echo htmlspecialchars($sf); ?>"
                                <?php
                                // Если нет выбранного пользовательского шрифта, отмечаем первый системный
                                if (empty($select_font) && $sf === $default_system_font) echo 'selected';
                                ?>>
                                <?php echo htmlspecialchars(str_replace('.ttf', '', $sf)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Пользовательские шрифты (радиокнопки) -->
                    <h3>Загружаемые шрифты</h3>
                    <?php
                    if (empty($_SESSION['fonts'])) {
                        echo "<p>No uploaded fonts yet.</p>";
                    } else {
                        foreach ($_SESSION['fonts'] as $font) {
                            $displayName = str_replace(".TTF", "", str_replace(".ttf", "", $font));
                            $value = "user/$font";
                            $checked = ($select_font === $value) ? 'checked' : '';
                            echo "<input type=\"radio\" name=\"font\" value=\"$value\" $checked onChange=\"updateImage()\"> $displayName<br>\n";
                        }
                    }
                    ?>

                    &nbsp;<br>
                    <input type="submit" value="Загрузить" name="submit-file" onClick="return validateUpload();"> <input type="file" name="fileToUpload" id="fileToUpload">
                </td>
                <td id="third">
                    <h3>Размер шрифта</h3>
                    <input type="text" name="size" id="sizefield" value="20" onInput="updateImage()"> пунктов

                    &nbsp;<br>
                    &nbsp;<br>

                    <h3>Текст для проверки</h3>
                    <input type="text" name="text" id="textfield" value="23:45" onInput="updateImage()">

                    &nbsp;<br>
                    &nbsp;<br>

                    <!-- Новое поле: имя результирующего файла -->
                    <h3>Имя для шрифта</h3>
                    <input type="text" name="custom_name" id="custom_name" placeholder="Новое имя" pattern="[a-zA-Z_][a-zA-Z0-9_]*" title="Латинские буквы, цифры и подчёркивание, начинается с буквы или подчёркивания">
                    <div class="note">Если пусто - имя шрифта</div>

                    &nbsp;<br>
                    &nbsp;<br>

                    <input type="submit" id="get-font" value="Скачать .h" name="get-font">

                </form>
            </td>
        </tr>
        <tr>
            <td colspan=3>
                <p>Текст после всего</p>
            </td>
        </tr>
    </table>

    <script>
        function font() {
            // Определяем выбранный шрифт среди радиокнопок и select
            var radios = document.getElementsByName('font');
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].checked) {
                    return radios[i].value;
                }
            }
            // Если ничего не выбрано, берём значение из select
            var select = document.getElementById('system-font-select');
            if (select) {
                return select.value;
            }
            return "";
        }

        function updateImage() {
            var fontValue = font();
            if (!fontValue) return; // предотвращаем пустой запрос
            document.getElementById("image").src = "image.php?font=" + encodeURIComponent(fontValue) +
                "&size=" + document.getElementById("sizefield").value +
                "&text=" + encodeURIComponent(document.getElementById("textfield").value) +
                "&dpi=<?php echo TARGET_DPI; ?>" +
                "#" + new Date().getTime();
        }

        function setFont() {
            // Восстанавливаем выбор пользовательского шрифта после загрузки (если был)
            // Это уже сделано через PHP при выводе радиокнопок с checked
            updateImage();
        }

        function validateUpload() {
            var file = document.getElementById("fileToUpload").value;
            var reg = /(.*?)\.(ttf|TTF)$/;
            if(!file.match(reg)) {
                alert("You can only upload a TrueType font (.ttf or .TTF extension)");
                return false;
            }
        }
    </script>
</body>
</html>
