//Скрипт делает автоматичекое создание сделок в crm, данные для поступления в crm мы берем из базы данных сайт

<?php

$db_host = 'localhost';
$db_name = 'parser';
$db_user = 'root';
$db_password ='root';

//Подключаемся к бд
$connect = mysqli_connect($db_host, $db_user, $db_password, $db_name);  
mysqli_query($connect,"SET CHARACTER SET 'utf8'");

//Делаем запрос к бд на получение данных, для отправки их в crm
$result = mysqli_query($connect, "SELECT * FROM av");
$myrow = mysqli_fetch_array($result);
do {
  $marka_end = $myrow['marka'];
  $year_end = $myrow['year'];
  $probeg_end = $myrow['car_mileage'];
  $tip_topliva_end = $myrow['fuel_type'];
  $capacity_end = $myrow['capacity'];
  $kyzov = $myrow['body_type'];
  $color_end = $myrow['color'];
  $korobka_end = $myrow['transmission'];
  $privod_end = $myrow['drive_unit'];
  $adress = $myrow['adress'];
  $get_phone = $myrow['res_phone'];
  $end_price = $myrow['price'];
  $opis = $myrow['opisanie'];
  $link = $myrow['link'];
}while($myrow = mysqli_fetch_array($result));

error_reporting(E_ALL);
ini_set('display_errors', 1);
//Присваиваем токен
define("SALESAP_TOKEN", "2ycfOZutBAs8SjBAqQ5syJMButU4hpw18CvUevllnWE");


$orders = array( 
  'data'=> array (
    'type'=>'orders'
  )
);

//Делаем запрос к crm для того, что бы  получить данные, которые уже имеются в crm
function sendToSalesap($entityType, $data) {
  $curl = curl_init();
  $url = "https://app.salesap.ru/api/v1/$entityType";

  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.api+json','Authorization: Bearer '.SALESAP_TOKEN));
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

  $out = curl_exec($curl);
  $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);   
  $response = json_decode($out, true);

  return $response;
}

$responseOrders = sendToSalesap('orders', $orders);

ob_start();
$links = $link;
// В цикле прописываем поиск на совпадения для последующей проверки. Сверять будем по ссылкам, которая хранится в переменнйо $links 
  foreach ($responseOrders['data'] as $key=>$test) {

     if (array_search($links, $test['attributes']['customs'])){
        echo $test['attributes']['customs']['custom-66302']  ;
     }
  }
$links2 = ob_get_contents();
ob_end_clean();
var_dump($links2);

//Создаем массив, в который передаем данные из бд
 $orders2 = array( 
  'data'=> array (
    'type'=>'orders',
    'attributes'=> array(
      'name'=>'Автомобиль',
      'amount' => $end_price,
      'customs'=> array(
         'custom-66290'=> $marka_end, //Марка автомобиля
         'custom-66291'=> $year_end ,//Год выпуска
         'custom-66292'=> $probeg_end,//Пробег
         'custom-66297'=> $tip_topliva_end,//Тип топлива
         'custom-66301'=> $capacity_end,//Объем
         'custom-66294'=> $kyzov,//Кузов
         'custom-66293'=> $korobka_end,//Коробка
         'custom-66296'=> $color_end,//Цвет
         'custom-66295'=> $privod_end,//Привод
         'custom-66303'=> $opis,//Описание
         'custom-66298'=> $adress,//Адрес
         'custom-66299'=> $get_phone,//Телефон
         'custom-66302'=> $links//Ссылка на объявление

      )
     
    )
  )
);

// Делаем запрос к CRM для отправки данных
function sendToSalesap2($entityType, $data) {
  $curl = curl_init();
  $url = "https://app.salesap.ru/api/v1/$entityType";

  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.api+json','Authorization: Bearer '.SALESAP_TOKEN));
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

  $out = curl_exec($curl);
  $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  $response = json_decode($out, true);

  return $response;
}

// Перед отправкой данных проверяем, имеется ли такая же сделка в crm, если да то не отправляем и ждем следующего запроса, если нет то отправляем данные
if ($links2==$links) {
  echo "yes";
}
else {
  echo "no";
  $responseOrder2 = sendToSalesap2('orders', $orders2);
  echo "<pre>";
  print_r($responseOrder2);
}


