<form method="post" action="index.php" enctype="multipart/form-data">
    <label for="inputfile"></label>
    <input type="file" id="inputfile" name="inputfile" accept="application/pdf"></br>
    <input type="submit" value="Загрузить" name='load'>
</form>
<form action="index.php" method="post">
    <p><b>Внесите или исправьте лимиты:</b></p>
    <p><textarea  rows="10" cols="60" name="text"><?=file_get_contents('limit.txt')?></textarea></p>
    <p><input type="submit" value="Отправить" name="save"></p>
</form>
<?php
require_once 'vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
if (isset($_POST['load']) and (!empty($_FILES['inputfile']['tmp_name']))) {
    $pdf = $parser->parseFile($_FILES['inputfile']['tmp_name']);
    }
else{
    $pdf = $parser->parseFile('document.pdf');
}

if (isset($_POST['save'])) {
    // Открыть текстовый файл
    $f = fopen("limit.txt", "w");
// Записать текст
    fwrite($f, $_POST['text']);
// Закрыть текстовый файл
    fclose($f);
    header("Location: ".$_SERVER['REQUEST_URI']);
}

$i = 0;
$it = [];


$text = $pdf->getText();
$a = 0;
$b = 0;
$c = 0;

$abon = preg_match_all("/(?<=Абонент\s)\d{11}/", $text, $arr);
$sum = preg_match_all("/(?<=Итого начисления)(.+?)р/", $text, $arr2);
$period = preg_match("/(?<=Счет за\s).*\d/", $text, $mount);
$itogo = 0;
    foreach ($arr2[1] as $key){
        $phone = $arr[0][$i++];
        $a =  str_replace(chr(9), '', $key);
        $b = str_replace(' ', '', $a);
        $c = str_replace(',', '.', $b);
        $itogo += floatval($c);
        $it[$phone] = $c;
    }

$lines = file('limit.txt');
$i = 1;

//Функция перевода первого символа в заглавный (кирилица)
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($str, $enc = 'utf-8') {
        return
            mb_strtoupper(mb_substr($str, 0, 1, $enc), $enc) .
            mb_substr($str, 1, mb_strlen($str, $enc), $enc);
    }
}
///

echo '<table border="1" style="font-size: 12px" align="center">';
echo '<caption style="font-size: 15px; font-weight: bold" > ' . mb_ucfirst($mount[0]) . 'г.' . '</caption>';
echo "<tr><th>№</th><th>ФИО</th><th>Телефон</th><th>Лимит</th><th>Расход</th><th>Перерасход</th></tr>";
asort($lines);
    foreach ($lines as $value){
    $pos = strpos($value, '7');
    $phone = substr($value, $pos, 11);
    $FIO = substr($value, 0, $pos);
    $limit = substr($value, $pos+11, -1);
    if(array_key_exists($phone, $it)){
        echo '<tr>';
        echo '<td align="right">'. $i++ . '</td>' . '<td >' .$FIO . '</td>' . '<td align="center" width="150">' . $phone . '</td>' . '<td align="center" width="50">' . $limit . '</td> ' . '<td align="center" width="75">' .$it[$phone] . '</td>';
        if ((float)$limit-(float)$it[$phone] >= 0){
            echo '<td align="center" width="50"></td></tr>';
        }
        else {
            echo '<td align="center" width="50" style="color: red">' . ((float)$limit-(float)$it[$phone])*-1 .'</td></tr>';
        }
        unset($it[$phone]);
    }
}
    echo '</table>';
    //Вызов JS для печати только таблицы
echo '<a href="javascript:void(0);" onclick="printPage();">Print</a>';


echo '<pre>';
    echo '<br>Номера не вошедшие в список, на контроль: <br>' ;

print_r($it);
echo '<br>Сумма по детализации: ';echo $itogo;
///JS печатает только таблицу
?>
<script type="text/javascript">
    function printPage(){
        var tableData = '<table border="1" style="font-size: 12px" align="center">'+document.getElementsByTagName('table')[0].innerHTML+'</table>';
        var data = '<button onclick="window.print()"></button>'+tableData;
        myWindow=window.open('','','width=800,height=600');
        myWindow.innerWidth = screen.width;
        myWindow.innerHeight = screen.height;
        myWindow.screenX = 0;
        myWindow.screenY = 0;
        myWindow.document.write(data);
        myWindow.focus();
    };
</script>
