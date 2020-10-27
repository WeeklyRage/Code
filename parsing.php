// Текущий скрипт отвечает за парсинг объявлений, о продаже легковых автомобилей, с сайта av.by и отправкой данных в базу данных сайта
// Парсинг реализован с использованием библиотеки PHPQuery
<?php 


require 'phpQuery.php'; //Подключаем библиотеку phpquery

$db_host = 'localhost';
$db_name = 'parser';
$db_user = 'root';
$db_password ='root';
//Подключаемся к бд
$connect = mysqli_connect($db_host, $db_user, $db_password, $db_name);	
mysqli_query($connect,"SET CHARACTER SET 'utf8'");


$url = 'https://cars.av.by/filter'; 


$file = file_get_contents($url);

$doc = phpQuery::newDocument($file);



ob_start();
//Цикл на получение ссылки 1го объявления
foreach ($doc->find('.main') as $key => $content ) {
	$content = pq($content);
	$text = $content->find(' .listing .listing__items .listing-item__wrap .listing-item__title a ')->attr('href');
	
	print_r($text);
	
	

}
$get_links = ob_get_contents();
ob_end_clean();


define('fullLink', 'https://cars.av.by');
//Так как мы не получаем полную ссылку, то канкатенируем полученные результат в цикле выше с константой fullLink, для получения полной ссылки объявления
$link = fullLink.$get_links;

$url_content = $link;    
$file_content = file_get_contents($url_content);

$doc_content = phpQuery::newDocument($file_content);

//Убираем ненужный символ для получения id объявления, что бы в дальнейшем получить номер телефона объявителя
$str = $link;
$tmp = explode('/', $str);
$str_res = end ($tmp);

//Делаем ссылку для получения номера телефона
$url_js_test = 'https://api.av.by/offers/'.$str_res.'/phones';


$phone2 = file_get_contents($url_js_test);

$doc_phone2 = phpQuery::newDocument($phone2);

ob_start();
//Собсвенно вытаскиваем номер телефона
$phoneData2 = json_decode($doc_phone2,true);
foreach ($phoneData2 as $key => $value2) {
	print_r("+375".$value2['number']);
}  
$get_phone = ob_get_contents();
ob_end_clean();

ob_start();
//Прогоняем через цикл для получения контента страницы с объявлением
foreach ($doc_content->find('.card  ') as $key => $Fullcontent ) {
	$Fullcontent = pq($Fullcontent);

	//Получение марки авто и оработка строки с удалением лишних символов и слов
	$marka = $Fullcontent->find('.card__header h1')->text();//Марка авто
	$maraka_delete = substr($marka, 0, strpos($marka, ',' ));//Обрезаем строку до запятой
	$marka_end =  strstr($maraka_delete, ' ');//Обрезаем строку до бервого пробела
	echo $marka_end."</br>";
	
	//Получение цены в долларах и оработка строки с удалением лишних символов и преобразованием в число
	$price = $Fullcontent->find('.card__price-secondary')->text();
	$trimmed = trim($price, "≈");//удаляем символ ≈
	$trimmed2 = trim($trimmed, "$");//Удаляем символ $
	$replace_space = preg_replace('/\s+/ui', '', $trimmed2);//Убираем пробелы в строке
	$end_price = (int)$replace_space; //преобразовываем строку в число
	echo $end_price."</br>";

	//В переменной $text хранятся данные о годе выпуска, коробке, объеме, типе топлива, пробег
	$text = $Fullcontent->find('.card__params')->text();
	$year_end = strstr($text, 'г', true);//Получаем из строки $text год выпуска

	$korobka_delete  = strstr($text, ' ');
	$korobka_end  = strstr($korobka_delete, ',', true); //Получаем из строки $text тип коропки 

	$capacity = strstr($korobka_delete, ',');
	$capacity2 = strstr($capacity, ' ');
	$capacity_end = strstr($capacity2, ',', true); //Получаем из строки $text объем

	$tip_topliva = strstr($capacity2, ',' );
	$tip_topliva2 = strstr($tip_topliva, ' ' );
	$tip_topliva_end = strstr($tip_topliva2, ',', true ); //Получаем из строки $text тип двигателя 

	$probeg = strstr($tip_topliva2, ',');
	$probeg2 = strstr($probeg, ' ');
	$probeg_end = strstr($probeg2, 'км', true); //Получаем из строки $text пробег 


	//В переменной $text12 хранится информация о типе кузова, типе привода и цвет
	$text12 = $Fullcontent->find('.card__description')->text();

	$kyzov = strstr($text12, ',', true); //Получаем тип кузова из строки $text12
	$privod = strstr($text12, ' ');
	$privod_end = strstr($privod, ',', true);//Получаем тип привода из строки $text12
	$color = strstr($privod, ',');
	$color_end = strstr($color, ' ');//Получаем цвет из строки $text12

	

	//В переменной $adress лежит информация об адресе
	$adress = $Fullcontent->find('.card__location')->text();

	echo $adress ."</br>";

	//В переменной $opis лежит информация об описании
	$opis = $Fullcontent->find('.card__comment-body')->text();
	echo $opis;

	// Полученные данные вносим в бд
	link = mysqli_query($connect, "INSERT INTO av (marka, year, car_mileage, fuel_type, capacity, body_type, color, transmission, drive_unit, adress, res_phone, price, opisanie, link) VALUES ('$marka_end', '$year_end', '$probeg_end', '$tip_topliva_end', '$capacity_end', '$kyzov', '$color_end', '$korobka_end', '$privod_end', '$adress', '$get_phone', '$end_price', '$opis', '$link')"); 
		
	
 }

?>

	
 

	
